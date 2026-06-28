<?php
// models/Mikrotik.php

require_once __DIR__ . '/../config/config.php';

class Mikrotik {
    
    private $ip;
    private $user;
    private $pass;

    public function __construct() {
        // Puxa as credenciais do seu arquivo de configuração global
        $this->ip   = defined('MK_HOST') ? MK_HOST : (defined('MK_HOTSPOT_IP') ? MK_HOTSPOT_IP : 'api.deboananet.club');
        $this->user = defined('MK_USER') ? MK_USER : 'admin';
        $this->pass = defined('MK_PASS') ? MK_PASS : '';
    }

    /**
     * Faz requisições HTTP para a API REST do RouterOS v7 respeitando a porta do config
     */
    private function requestREST($endpoint, $method = 'GET', $data = null) {
        // Puxa a porta configurada no config.php. Se não existir, assume a 80
        $porta = defined('MK_PORT') ? MK_PORT : '80';
        
        // Injeta a porta na URL (Fica ex: https://api.deboananet.club:8080/rest/...)
        $url = "https://{$this->ip}:{$porta}/rest{$endpoint}";
        
        $ch = curl_init($url);
        
        $headers = ["Content-Type: application/json"];
        
        curl_setopt($ch, CURLOPT_USERPWD, "{$this->user}:{$this->pass}");
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_TIMEOUT, 5); // Timeout curto para não prender o painel/webhook
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);

        // Desativa a verificação de SSL caso mude para https futuramente
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);

        if ($method === 'PUT' || $method === 'POST') {
            curl_setopt($ch, CURLOPT_CUSTOMREQUEST, $method);
            if ($data) curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
        } elseif ($method === 'DELETE') {
            curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'DELETE');
        }

        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $curlError = curl_error($ch); 
        curl_close($ch);

        if ($httpCode == 0) {
            $log = date('Y-m-d H:i:s') . " - FALHA CRÍTICA DE REDE: {$curlError} | Tentando acessar: {$url}\n";
            file_put_contents(__DIR__ . '/../webhook_log.txt', $log, FILE_APPEND);
        }

        return ['code' => $httpCode, 'body' => json_decode($response, true)];
    }

    /**
     * LIBERAÇÃO DE TEMPO COM PERFIL DINÂMICO VIA REST
     */
    public function liberarAcessoTempo($mac, $minutos, $profile = 'default') {
        // 1. Remove qualquer sessão ativa atual para forçar login novo
        $ativas = $this->requestREST('/ip/hotspot/active?mac-address=' . $mac);
        if ($ativas['code'] == 200 && !empty($ativas['body'])) {
            foreach ($ativas['body'] as $sessao) {
                if (isset($sessao['.id'])) {
                    $this->requestREST('/ip/hotspot/active/' . $sessao['.id'], 'DELETE');
                }
            }
        }

        // 2. Remove o usuário se ele já existir (evita erro "user already exists")
        $usuarios = $this->requestREST('/ip/hotspot/user?name=' . $mac);
        if ($usuarios['code'] == 200 && !empty($usuarios['body'])) {
            foreach ($usuarios['body'] as $user) {
                if (isset($user['.id'])) {
                    $this->requestREST('/ip/hotspot/user/' . $user['.id'], 'DELETE');
                }
            }
        }

        // 3. Formata o tempo no padrão do MikroTik (ex: 00:15:00)
        $horas = floor($minutos / 60);
        $restoMinutos = $minutos % 60;
        $limit_uptime = sprintf("%02d:%02d:00", $horas, $restoMinutos);

        // 4. Cria o usuário no router
        $dadosNovoUser = [
            'name'         => $mac,
            'password'     => $mac,
            'profile'      => $profile,
            'limit-uptime' => $limit_uptime,
            'comment'      => 'Liberado via Sistema Web - ' . date('d/m/Y H:i')
        ];

        // Usa PUT para criar um novo recurso na API REST
        $resposta = $this->requestREST('/ip/hotspot/user', 'PUT', $dadosNovoUser);

        // 201 Created ou 200 OK significa sucesso na API REST do RouterOS
        return ($resposta['code'] == 201 || $resposta['code'] == 200);
    }

    /**
     * CONTA QUANTOS CLIENTES ESTÃO CONECTADOS NO MOMENTO
     * (Função restaurada para o Dashboard do Admin funcionar)
     */
    public function contarUtilizadoresAtivos() {
        $resposta = $this->requestREST('/ip/hotspot/active', 'GET');
        
        if ($resposta['code'] == 200 && is_array($resposta['body'])) {
            return count($resposta['body']);
        }
        
        return 0; // Se houver erro ou ninguém conectado, retorna 0
    }

    /**
     * 🔗 FUNÇÃO REST: LIBERAR MAC (IP BINDING)
     * Utiliza o motor requestREST já existente para criar o bypass.
     */
    public function liberarMac($mac, $ip = '') {
        $dadosBinding = [
            'mac-address' => strtoupper($mac),
            'type'        => 'bypassed',
            'comment'     => 'Liberado via IP Binding - ' . date('d/m/Y H:i')
        ];

        // Dispara o PUT para criar o IP Binding
        $resposta = $this->requestREST('/ip/hotspot/ip-binding', 'PUT', $dadosBinding);

        if ($resposta['code'] == 201 || $resposta['code'] == 200) {
            return true;
        } else {
            // Em caso de falha, guarda no log para auditoria (HTTP 0 já vai ser pego pelo requestREST acima)
            if ($resposta['code'] != 0) {
                $log = date('Y-m-d H:i:s') . " - ERRO REST (IP BINDING) | HTTP: {$resposta['code']} | MAC: {$mac}\n";
                file_put_contents(__DIR__ . '/../webhook_log.txt', $log, FILE_APPEND);
            }
            return false;
        }
    }
}
?>