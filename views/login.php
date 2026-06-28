<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Portal Hotspot - Wi-Fi</title>
    <link href="/src/bootstrap.min.css" rel="stylesheet">
    <style>
        body {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 20px 0;
        }
        .portal-container {
            max-width: 900px;
            width: 100%;
        }
        .card {
            border-radius: 15px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.3);
            border: none;
        }
        .plan-card {
            transition: transform 0.3s;
            cursor: pointer;
        }
        .plan-card:hover {
            transform: translateY(-10px);
        }
        .price {
            font-size: 2.5rem;
            font-weight: bold;
            color: #667eea;
        }
        .timer-box {
            background: #f8f9fa;
            border-radius: 10px;
            padding: 15px;
            border: 2px solid #e9ecef;
        }
        .timer-display {
            font-size: 3.5rem;
            font-weight: bold;
            color: #2c3e50;
            font-family: monospace;
            line-height: 1;
        }
    </style>
</head>
<body>
    <div class="portal-container px-3">
        
        <?php if (isset($sessaoAtiva) && $sessaoAtiva && $tempoRestante > 0): ?>
            <div class="row justify-content-center mb-5">
                <div class="col-md-8">
                    <div class="card text-center p-4 border-success" style="border-width: 3px;">
                        <h2 class="text-success mb-2">Conectado com Sucesso!</h2>
                        <p class="text-muted">Plano ativo: <strong><?php echo htmlspecialchars($sessaoAtiva['plano_nome']); ?></strong></p>
                        
                        <div class="timer-box my-3">
                            <p class="small text-uppercase fw-bold text-secondary mb-1">Tempo Restante</p>
                            <div class="timer-display" id="relogio-principal">00:00:00</div>
                        </div>
                        
                        <?php
                            // Força o login transparente via HTTPS no MikroTik caso o utilizador tenha sido desligado pelo router
                            $mikrotikGateway = defined('MK_HOTSPOT_IP') ? MK_HOTSPOT_IP : 'api.deboananet.club';
                            $linkReconexao = "https://{$mikrotikGateway}/login?username=" . urlencode($mac) . "&password=" . urlencode($mac) . "&dst=https://www.google.com/";
                        ?>
                        <a href="<?php echo $linkReconexao; ?>" class="btn btn-dark btn-lg w-100 mt-2">
                            <i class="bi bi-globe"></i> Começar a Navegar
                        </a>
                    </div>
                </div>
            </div>

            <script>
                let tempoRestante = <?php echo (int)$tempoRestante; ?>;
                
                function formatarTempo(segundos) {
                    let h = Math.floor(segundos / 3600);
                    let m = Math.floor((segundos % 3600) / 60);
                    let s = segundos % 60;
                    return (h < 10 ? "0" + h : h) + ":" + (m < 10 ? "0" + m : m) + ":" + (s < 10 ? "0" + s : s);
                }

                function atualizarRelogio() {
                    if (tempoRestante <= 0) {
                        // O tempo acabou! Recarrega a página para esconder o relógio e mostrar o aviso de erro
                        window.location.reload();
                    } else {
                        document.getElementById('relogio-principal').innerText = formatarTempo(tempoRestante);
                        tempoRestante--;
                    }
                }
                atualizarRelogio();
                setInterval(atualizarRelogio, 1000);
            </script>
            
            <hr class="text-white mb-5">
            <div class="text-center mb-4">
                <h3 class="text-white">Quer adicionar mais tempo?</h3>
                <p class="text-white-50">Compre um novo plano abaixo para acumular ou renovar seu acesso.</p>
            </div>
        <?php else: ?>
            <div class="text-center mb-4">
                <h1 class="text-white display-4">Bem-vindo!</h1>
                <p class="text-white">Escolha um plano e conecte-se à internet</p>
            </div>
        <?php endif; ?>

        <?php if (isset($_GET['error'])): ?>
            <div class="alert alert-danger alert-dismissible fade show text-center" role="alert">
                Erro ao processar. Por favor, tente novamente.
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>

        <div class="row g-4 justify-content-center">
            <?php if (!empty($plans)): ?>
                <?php foreach ($plans as $plan): ?>
                    <div class="col-md-4">
                        <div class="card plan-card h-100">
                            <div class="card-body text-center">
                                <h3 class="card-title"><?php echo htmlspecialchars($plan['name']); ?></h3>
                                <div class="price my-4">
                                    R$ <?php echo number_format($plan['price_cents'] / 100, 2, ',', '.'); ?>
                                </div>
                                <p class="text-muted mb-4">
                                    <strong><?php echo $plan['duration_minutes']; ?> minutos</strong> de internet
                                </p>
                                
                                <form method="POST" action="/gerar-pix">
                                    <input type="hidden" name="plan_id" value="<?php echo $plan['id']; ?>">
                                    <input type="hidden" name="horas" value="<?php echo $plan['duration_minutes'] / 60; ?>">
                                    <input type="hidden" name="valor" value="<?php echo number_format($plan['price_cents'] / 100, 2, '.', ''); ?>">
                                    
                                    <input type="hidden" name="ip" value="<?php echo htmlspecialchars(isset($ip) ? $ip : ''); ?>">
                                    <input type="hidden" name="mac" value="<?php echo htmlspecialchars(isset($mac) ? $mac : ''); ?>">
                                    
                                    <button type="submit" class="btn btn-primary btn-lg w-100">
                                        Escolher Plano
                                    </button>
                                </form>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php else: ?>
                <div class="col-12">
                    <div class="alert alert-warning text-center">
                        Nenhum plano cadastrado no momento.
                    </div>
                </div>
            <?php endif; ?>
        </div>

        <div class="text-center mt-4 pb-4">
            <small class="text-white-50">Pagamento seguro via PIX - Mercado Pago</small>
        </div>
    </div>

    <script src="/src/bootstrap.bundle.min.js"></script>
</body>
</html>