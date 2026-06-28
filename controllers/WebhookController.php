<?php
// controllers/WebhookController.php

class WebhookController {
    
    private function log($mensagem) {
        file_put_contents(__DIR__ . '/../webhook_log.txt', date('Y-m-d H:i:s') . " - " . $mensagem . "\n", FILE_APPEND);
    }

    public function receberNotificacao() {
        try {
            date_default_timezone_set('America/Fortaleza');
            
            $json = file_get_contents('php://input');
            $headers = getallheaders();
            
            $jsonLimpo = str_replace(["\r", "\n"], "", $json);
            $this->log("RECEBEU WEBHOOK: " . $jsonLimpo);
            
            // Validação de Assinatura Avançada do Mercado Pago
            $signatureHeader = $headers['x-signature'] ?? $headers['X-Signature'] ?? '';
            $requestId = $headers['x-request-id'] ?? $headers['X-Request-Id'] ?? '';
            
            if (defined('MP_WEBHOOK_SECRET') && !empty($signatureHeader)) {
                preg_match('/ts=(\d+),v1=([a-f0-9]+)/', $signatureHeader, $matches);
                if (count($matches) === 3) {
                    $ts = $matches[1];
                    $v1 = $matches[2];
                    $manifest = "id:$requestId;request-parts:;ts:$ts;";
                    $expectedSignature = hash_hmac('sha256', $manifest, MP_WEBHOOK_SECRET);
                    
                    if (!hash_equals($expectedSignature, $v1)) {
                        $this->log("🚨 ALERTA: Assinatura inválida bloqueada.");
                        http_response_code(403);
                        die('Assinatura Inválida');
                    }
                }
            }

            $dados = json_decode($json, true);
            $paymentId = $dados['data']['id'] ?? ($_GET['id'] ?? null);

            if ($paymentId) {
                $this->log("ID DO PAGAMENTO: " . $paymentId);
            }

            // Resposta rápida para o Mercado Pago (Evita Retries pesados)
            if (function_exists('fastcgi_finish_request')) {
                echo "OK";
                session_write_close();
                fastcgi_finish_request();
            } else {
                ob_start();
                echo "OK";
                header("Connection: close");
                header("Content-Length: " . ob_get_length());
                http_response_code(200);
                ob_end_flush();
                flush();
            }

            if ($paymentId) {
                require_once __DIR__ . '/../models/Database.php';
                require_once __DIR__ . '/../models/MercadoPago.php';
                require_once __DIR__ . '/../models/Mikrotik.php';

                $mp = new MercadoPago();
                $pagamento = $mp->consultarPagamento($paymentId);
                
                $statusMp = $pagamento['status'] ?? 'desconhecido';
                $this->log("STATUS MP: " . $statusMp);
                
                if (isset($pagamento['status']) && $pagamento['status'] == 'approved') {
                    $txid = $paymentId;
                    $mac = strtoupper($pagamento['metadata']['mac_address'] ?? '00:00:00:00:00:00');
                    $plano_id = $pagamento['metadata']['plano_id'] ?? 0;

                    $db = new Database();
                    $transacao = $db->query("SELECT * FROM acessos_pix WHERE txid = ?", [$txid])->fetch();

                    if ($transacao && $transacao['status'] == 'pendente') {
                        // Trava Atômica de Duplicidade
                        $stmt = $db->query("UPDATE acessos_pix SET status = 'processando' WHERE txid = ? AND status = 'pendente'", [$txid]);
                        
                        if ($stmt->rowCount() > 0) {
                            $plano = $db->query("SELECT * FROM planos WHERE id = ?", [$plano_id])->fetch();
                            $minutosComprados = $plano['duration_minutes'] ?? 60;

                            // 🧠 LÓGICA DE ACÚMULO DE TEMPO
                            $agora = time();
                            $minutosTotais = $minutosComprados;
                            $tempoBaseParaCalculo = $agora;

                            // Verifica se o cliente já tem um plano ATIVO e com tempo SOBRANDO
                            $sessaoAtiva = $db->query("SELECT expira_em FROM acessos_pix WHERE mac_address = ? AND status = 'ativo' AND expira_em > NOW() ORDER BY id DESC LIMIT 1", [$mac])->fetch();

                            if ($sessaoAtiva) {
                                $expiracaoAtual = strtotime($sessaoAtiva['expira_em']);
                                
                                if ($expiracaoAtual > $agora) {
                                    // Pega os minutos que sobraram e soma com os novos
                                    $minutosSobra = floor(($expiracaoAtual - $agora) / 60);
                                    $minutosTotais = $minutosComprados + $minutosSobra;
                                    
                                    // A nova data de expiração começa a contar a partir do fim do plano antigo
                                    $tempoBaseParaCalculo = $expiracaoAtual;
                                    
                                    $this->log("➕ ACÚMULO DE TEMPO: Cliente tinha {$minutosSobra} min sobrando. Somando com {$minutosComprados} min novos. Total: {$minutosTotais} min.");
                                }
                            }

                            // Calcula a data e hora exata em que vai expirar no banco de dados
                            $expira_em = date('Y-m-d H:i:s', $tempoBaseParaCalculo + ($minutosComprados * 60));

                            $this->log("LIBERANDO HOTSPOT VIA TIME LIMIT: $minutosTotais min totais para o MAC: $mac");
                            
                            $mk = new Mikrotik();
                            if ($mk->liberarAcessoTempo($mac, $minutosTotais)) {
                                $this->log("SUCESSO: MIKROTIK ADICIONOU USUÁRIO!");
                                $db->query("UPDATE acessos_pix SET status = 'ativo', expira_em = ? WHERE txid = ?", [$expira_em, $txid]);
                            } else {
                                $this->log("FALHA NO MIKROTIK. ENVIANDO ESTORNO...");
                                $mp->estornarPix($txid);
                                $db->query("UPDATE acessos_pix SET status = 'estornado' WHERE txid = ?", [$txid]);
                            }
                        }
                    } else if ($transacao) {
                         $this->log("AVISO: Transação {$txid} ignorada (Status no banco: {$transacao['status']}). Provavelmente já foi processada.");
                    }
                }
            }
        } catch (\Throwable $e) {
            $this->log("❌ ERRO FATAL: " . $e->getMessage());
        }
    }
}
?>