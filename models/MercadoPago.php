<?php
// models/MercadoPago.php

require_once __DIR__ . '/../config/config.php';

class MercadoPago {
    
    // Método privado genérico para fazer pedidos à API
    private function request($endpoint, $method = 'GET', $data = null, $customIdempotencyKey = null) {
        $url = "https://api.mercadopago.com/v1/" . $endpoint;
        $ch = curl_init($url);
        
        $headers = [
            "Authorization: Bearer " . MP_TOKEN,
            "Content-Type: application/json"
        ];

        // MÁGICA: Enviar a chave de idempotência apenas em métodos de criação (POST/PUT)
        if (($method === 'POST' || $method === 'PUT') && $customIdempotencyKey !== null) {
            $headers[] = "X-Idempotency-Key: " . $customIdempotencyKey;
        }

        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);

        if ($method === 'POST') {
            curl_setopt($ch, CURLOPT_POST, true);
            curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
        }

        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        return json_decode($response, true);
    }

    // Método para gerar o PIX com pontuação MÁXIMA no Mercado Pago
    public function criarPix($valor_cents, $mac, $ip, $plano_id, $descricao) {
        // 1. Converte os cêntimos para formato decimal exigido pelo MP (Ex: 500 -> 5.00)
        $valor_decimal = $valor_cents / 100;

        // 2. Hash de Idempotência: Combina MAC, Plano e uma janela de 10 segundos
        $idempotencyKey = md5($mac . '_' . $plano_id . '_' . floor(time() / 10));

        // 3. Expiração de 30 minutos (Não prende o sistema caso o cliente desista)
        date_default_timezone_set('America/Fortaleza'); 
        $date_of_expiration = date('Y-m-d\TH:i:s.000-03:00', strtotime('+30 minutes'));

        // 4. URL de Notificação Dinâmica: Garante que o Webhook chegue no endereço exato do seu site
        $protocolo = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? "https" : "http";
        $dominio = $_SERVER['HTTP_HOST'] ?? 'seudominio.com.br';
        $notificationUrl = "{$protocolo}://{$dominio}/webhook";
        
        // 5. Referência Externa: Exigência do MP para cruzar dados
        $externalReference = "hotspot_" . uniqid();

        $dados = [
            "transaction_amount" => (float)$valor_decimal,
            "description" => "Acesso Hotspot: " . $descricao,
            "payment_method_id" => "pix",
            "date_of_expiration" => $date_of_expiration,
            "external_reference" => $externalReference,
            "notification_url" => $notificationUrl,
            
            // 6. Dados do Comprador (Preenchemos de forma genérica para subir o score)
            "payer" => [
                "email" => "cliente_hotspot@seuprovedor.com.br",
                "first_name" => "Cliente",
                "last_name" => "Hotspot"
            ],
            
            // 7. Descrição dos Itens (Exigência do antifraude)
            "additional_info" => [
                "items" => [
                    [
                        "id" => (string)$plano_id,
                        "title" => "Plano de Internet Wi-Fi",
                        "description" => $descricao,
                        "category_id" => "virtual_goods", // Categoria para serviços digitais
                        "quantity" => 1,
                        "unit_price" => (float)$valor_decimal
                    ]
                ]
            ],
            
            // 8. Metadados do MikroTik (Fundamentais para o nosso Webhook saber o que liberar)
            "metadata" => [
                "mac_address" => $mac,
                "ip_address" => $ip,
                "plano_id" => $plano_id
            ]
        ];

        return $this->request("payments", "POST", $dados, $idempotencyKey);
    }

    // Método que consulta se o pagamento já foi aprovado
    public function consultarPagamento($paymentId) {
        return $this->request("payments/{$paymentId}", "GET");
    }

    // A Mágica de Segurança: Devolve o dinheiro na hora se o router estiver offline
    public function estornarPix($paymentId) {
        // Estornos também recebem um Idempotency Key único
        return $this->request("payments/{$paymentId}/refunds", "POST", [], uniqid('refund_'));
    }
}