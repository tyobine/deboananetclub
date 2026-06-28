<?php
// views/status.php
// Garante que o PHP calculou o tempo corretamente antes de renderizar
$agora = time();
$expiracao = strtotime($dadosAcesso['expira_em']);
$tempoRestante = max(0, $expiracao - $agora);
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Status da Conexão</title>
    <link href="/src/bootstrap.min.css" rel="stylesheet">
    <style>
        body {
            background: linear-gradient(135deg, #11998e 0%, #38ef7d 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        .status-container {
            max-width: 500px;
            width: 100%;
        }
        .card {
            border-radius: 15px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.3);
            border: none;
        }
        .timer-box {
            background: #f8f9fa;
            border-radius: 10px;
            padding: 20px;
            margin: 20px 0;
            border: 2px solid #e9ecef;
        }
        .timer-display {
            font-size: 3.5rem;
            font-weight: bold;
            color: #2c3e50;
            font-family: monospace;
            line-height: 1;
        }
        .pulse-icon {
            animation: pulse 2s infinite;
        }
        @keyframes pulse {
            0% { transform: scale(1); opacity: 1; }
            50% { transform: scale(1.1); opacity: 0.7; }
            100% { transform: scale(1); opacity: 1; }
        }
    </style>
</head>
<body>
    <div class="status-container px-3">
        <div class="card text-center p-4">
            <div class="card-body">
                <div class="mb-3">
                    <svg class="text-success pulse-icon" width="60" height="60" fill="currentColor" viewBox="0 0 16 16">
                        <path d="M15.384 6.115a.485.485 0 0 0-.047-.736A12.44 12.44 0 0 0 8 3 12.44 12.44 0 0 0 .663 5.379a.485.485 0 0 0-.048.736.52.52 0 0 0 .668.05A11.45 11.45 0 0 1 8 4c2.507 0 4.827.802 6.716 2.164.205.148.49.13.668-.049z"/>
                        <path d="M13.229 8.271a.482.482 0 0 0-.063-.745A9.46 9.46 0 0 0 8 5.5a9.46 9.46 0 0 0-5.166 2.026.48.48 0 0 0-.063.745.52.52 0 0 0 .652.065A8.46 8.46 0 0 1 8 6.5c1.86 0 3.58.6 4.977 1.636.2.15.494.132.668-.047z"/>
                        <path d="M11.53 10.53a.48.48 0 0 0-.07-.745A6.47 6.47 0 0 0 8 8c-1.523 0-2.931.527-4.06 1.425a.48.48 0 0 0-.07.745.52.52 0 0 0 .62.083A5.47 5.47 0 0 1 8 9c1.29 0 2.476.446 3.41 1.196.196.156.49.136.668-.047z"/>
                        <path d="M8 12.5a1.5 1.5 0 1 1 0-3 1.5 1.5 0 0 1 0 3z"/>
                    </svg>
                </div>

                <h2 class="mb-1 text-success">Internet Ativa!</h2>
                <p class="text-muted">Você já pode navegar livremente.</p>

                <div class="timer-box">
                    <p class="small text-uppercase fw-bold text-secondary mb-2">Tempo Restante</p>
                    <div class="timer-display" id="clock">00:00:00</div>
                </div>

                <div class="text-start mt-4 px-2">
                    <p class="mb-1"><strong>Plano:</strong> <?php echo htmlspecialchars($dadosAcesso['plano_nome']); ?></p>
                    <p class="mb-1"><strong>Duração:</strong> <?php echo $dadosAcesso['duration_minutes']; ?> minutos</p>
                    <p class="mb-0 text-muted small">MAC: <?php echo htmlspecialchars($dadosAcesso['mac_address']); ?></p>
                </div>
            </div>
        </div>
    </div>

    <script>
        // Pega o tempo calculado pelo PHP
        let tempoRestante = <?php echo $tempoRestante; ?>;
        
        function formatarTempo(segundos) {
            let h = Math.floor(segundos / 3600);
            let m = Math.floor((segundos % 3600) / 60);
            let s = segundos % 60;
            
            // Adiciona um zero à esquerda se for menor que 10
            return (h < 10 ? "0" + h : h) + ":" + 
                   (m < 10 ? "0" + m : m) + ":" + 
                   (s < 10 ? "0" + s : s);
        }

        function atualizarRelogio() {
            if (tempoRestante <= 0) {
                // O tempo acabou! Força o redirecionamento para a página de expirado
                window.location.href = '/expirado';
            } else {
                // Atualiza o visual
                document.getElementById('clock').innerText = formatarTempo(tempoRestante);
                tempoRestante--;
            }
        }

        // Executa a primeira vez instantaneamente e depois a cada 1 segundo (1000ms)
        atualizarRelogio();
        setInterval(atualizarRelogio, 1000);
    </script>
</body>
</html>