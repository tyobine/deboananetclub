<?php
// controllers/HotspotController.php

require_once __DIR__ . '/../models/Database.php';
require_once __DIR__ . '/../models/MercadoPago.php';

class HotspotController {

    private function obterIpCliente() {
        return $_SERVER['HTTP_CF_CONNECTING_IP'] ?? $_SERVER['HTTP_X_FORWARDED_FOR'] ?? $_SERVER['REMOTE_ADDR'];
    }

    public function index() {
        $db = new Database();
        
        $mac_url = strtoupper(urldecode($_GET['mac'] ?? ''));
        $ip = $_GET['ip'] ?? '';
        
        if (empty($ip)) {
            $ip = $this->obterIpCliente();
        }

        $mac = '';
        if (!empty($mac_url)) {
            setcookie('mac_cliente', $mac_url, time() + (86400 * 30), "/");
            $mac = $mac_url;
        } elseif (!empty($_COOKIE['mac_cliente'])) {
            $mac = $_COOKIE['mac_cliente'];
        } else {
            $ultimoAcesso = $db->getRow("SELECT mac_address FROM acessos_pix WHERE ip_address = ? ORDER BY id DESC LIMIT 1", [$ip]);
            if ($ultimoAcesso) {
                $mac = $ultimoAcesso['mac_address'];
                setcookie('mac_cliente', $mac, time() + (86400 * 30), "/");
            }
        }

        if (empty($mac)) {
            $mikrotikGateway = defined('MK_HOTSPOT_IP') ? MK_HOTSPOT_IP : 'api.deboananet.club';
            header("Location: http://{$mikrotikGateway}/login");
            exit;
        }

        $sessaoAtiva = null;
        $tempoRestante = 0;

        if (!empty($mac)) {
            $acesso = $db->getRow("
                SELECT a.id, a.expira_em, p.name as plano_nome 
                FROM acessos_pix a
                LEFT JOIN planos p ON a.plano_id = p.id
                WHERE a.mac_address = ? AND a.status = 'ativo' 
                ORDER BY a.id DESC LIMIT 1
            ", [$mac]);

            if ($acesso) {
                date_default_timezone_set('America/Fortaleza');
                $agora = time();
                $expiracao = strtotime($acesso['expira_em']);
                
                if ($expiracao > $agora) {
                    $sessaoAtiva = $acesso;
                    $tempoRestante = $expiracao - $agora;
                } else {
                    $db->query("UPDATE acessos_pix SET status = 'expirado' WHERE id = ?", [$acesso['id']]);
                }
            }
        }
        
        $plans = $db->getAll("SELECT * FROM planos WHERE ativo = 1 ORDER BY price_cents ASC");

        require_once __DIR__ . '/../views/login.php';
    }

    public function gerarCobranca() {
        $db = new Database();
        
        $plano_id = $_REQUEST['plan_id'] ?? null;
        $mac = strtoupper(urldecode($_REQUEST['mac'] ?? ''));
        $ip = $_REQUEST['ip'] ?? '';

        if (empty($ip)) {
            $ip = $this->obterIpCliente();
        }

        if (empty($mac)) {
            $mac = $_COOKIE['mac_cliente'] ?? '';
        }

        if (empty($mac)) {
            $ultimoAcesso = $db->getRow("SELECT mac_address FROM acessos_pix WHERE ip_address = ? ORDER BY id DESC LIMIT 1", [$ip]);
            if ($ultimoAcesso) {
                $mac = $ultimoAcesso['mac_address'];
            }
        }

        if (empty($mac)) {
            $mikrotikGateway = defined('MK_HOTSPOT_IP') ? MK_HOTSPOT_IP : 'api.deboananet.club';
            header("Location: http://{$mikrotikGateway}/login");
            exit;
        }

        if (!$plano_id) {
            header("Location: /inicio?error=1");
            exit;
        }

        $plano = $db->getRow("SELECT * FROM planos WHERE id = ?", [$plano_id]);

        if (!$plano) {
            header("Location: /inicio?error=1");
            exit;
        }

        if (intval($plano['price_cents']) === 0) {
            $ultimoGratis = $db->getRow("
                SELECT expira_em FROM acessos_pix 
                WHERE mac_address = ? AND plano_id = ? 
                ORDER BY id DESC LIMIT 1
            ", [$mac, $plano_id]);

            if ($ultimoGratis && !empty($ultimoGratis['expira_em'])) {
                date_default_timezone_set('America/Fortaleza');
                $hora_liberacao = strtotime($ultimoGratis['expira_em']) + 3600; 
                
                if (time() < $hora_liberacao) {
                    $min_restantes = ceil(($hora_liberacao - time()) / 60);
                    echo "<div style='font-family: Arial, sans-serif; text-align: center; padding: 40px; background: #f8d7da; color: #721c24; height: 100vh;'>";
                    echo "<h2>⏳ Limite Atingido</h2>";
                    echo "<p>Você já utilizou o plano grátis recentemente.</p>";
                    echo "<p>Aguarde mais <b>{$min_restantes} minutos</b> para usar o grátis novamente, ou <b>compre um plano pago</b> para navegar agora mesmo com velocidade máxima!</p>";
                    echo "<br><a href='/inicio' style='padding: 10px 20px; background: #dc3545; color: white; text-decoration: none; border-radius: 5px; font-weight: bold;'>Ver Planos Pagos</a>";
                    echo "</div>";
                    exit;
                }
            }

            require_once __DIR__ . '/../views/publicidade.php';
            exit; 
        }

        $mp = new MercadoPago();
        $dadosPix = $mp->criarPix($plano['price_cents'], $mac, $ip, $plano_id, $plano['name']);

        if ($dadosPix && isset($dadosPix['id'])) {
            $db->query("
                INSERT INTO acessos_pix (txid, status, ip_address, mac_address, plano_id, expira_em) 
                VALUES (?, 'pendente', ?, ?, ?, NULL)
            ", [$dadosPix['id'], $ip, $mac, $plano_id]);

            $qr_code = $dadosPix['point_of_interaction']['transaction_data']['qr_code'];
            $qr_code_img = $dadosPix['point_of_interaction']['transaction_data']['qr_code_base64'];
            $txid = $dadosPix['id'];
            
            $plano_horas = $plano['duration_minutes'] / 60;
            $valor = $plano['price_cents'] / 100;

            require_once __DIR__ . '/../views/pagamento.php';
        } else {
            echo "<h1>Erro ao gerar o PIX junto ao Mercado Pago. Tente novamente.</h1>";
        }
    }

    public function liberarGratisConfirmado() {
        $plano_id = $_REQUEST['plan_id'] ?? null;
        $mac = strtoupper(urldecode($_REQUEST['mac'] ?? ''));
        $ip = $_REQUEST['ip'] ?? '';
        
        $whatsapp_raw = $_REQUEST['whatsapp'] ?? '';
        $whatsapp_numero = preg_replace('/[^0-9]/', '', $whatsapp_raw);
        if (empty($whatsapp_numero)) {
            $whatsapp_numero = null;
        }

        if (!$plano_id || empty($mac)) {
            header("Location: /inicio?error=1");
            exit;
        }

        $db = new Database();
        $plano = $db->getRow("SELECT * FROM planos WHERE id = ?", [$plano_id]);

        if (!$plano || intval($plano['price_cents']) !== 0) {
            header("Location: /inicio?error=1");
            exit;
        }

        $jaExiste = $db->getRow("
            SELECT id FROM acessos_pix 
            WHERE mac_address = ? AND plano_id = ? AND status = 'ativo' AND expira_em > NOW()
            ORDER BY id DESC LIMIT 1
        ", [$mac, $plano_id]);

        if ($jaExiste) {
            header("Location: /inicio?mac=$mac");
            exit;
        }

        $ultimoGratis = $db->getRow("
            SELECT expira_em FROM acessos_pix 
            WHERE mac_address = ? AND plano_id = ? 
            ORDER BY id DESC LIMIT 1
        ", [$mac, $plano_id]);

        if ($ultimoGratis && !empty($ultimoGratis['expira_em'])) {
            date_default_timezone_set('America/Fortaleza');
            $hora_liberacao = strtotime($ultimoGratis['expira_em']) + 3600; 
            
            if (time() < $hora_liberacao) {
                header("Location: /inicio?mac=$mac");
                exit;
            }
        }

        $txid_gratis = "PUB-" . time() . "-" . rand(1000, 9999);
        date_default_timezone_set('America/Fortaleza');
        $expiracao = date('Y-m-d H:i:s', strtotime("+" . $plano['duration_minutes'] . " minutes"));

        // AGORA SALVAMOS O WHATSAPP DIRETO NO BANCO DE DADOS JUNTO COM O ACESSO
        $db->query("
            INSERT INTO acessos_pix (txid, status, ip_address, mac_address, whatsapp, plano_id, expira_em) 
            VALUES (?, 'ativo', ?, ?, ?, ?, ?)
        ", [$txid_gratis, $ip, $mac, $whatsapp_numero, $plano_id, $expiracao]);

        require_once __DIR__ . '/../models/Mikrotik.php';
        $mk = new Mikrotik();
        
        $liberouNoRouter = $mk->liberarAcessoTempo($mac, intval($plano['duration_minutes']), 'plano_gratis');

        if ($liberouNoRouter) {
            header("Location: /sucesso?mac=$mac");
            exit;
        } else {
            echo "<h1>Erro ao comunicar com o roteador para liberar o seu acesso gratuito.</h1>";
            exit;
        }
    }

    public function checarStatus() {
        $txid = $_GET['txid'] ?? '';
        
        $db = new Database();
        $transacao = $db->getRow("SELECT status, mac_address FROM acessos_pix WHERE txid = ?", [$txid]);
        
        header('Content-Type: application/json');
        echo json_encode([
            'status' => $transacao['status'] ?? 'nao_encontrado',
            'mac' => $transacao['mac_address'] ?? ''
        ]);
        exit;
    }
}
?>