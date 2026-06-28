<?php
// views/sucesso.php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../models/Database.php';

$mac_cliente = $_GET['mac'] ?? $_COOKIE['mac_cliente'] ?? '';
$mikrotikGateway = defined('MK_HOTSPOT_IP') ? MK_HOTSPOT_IP : 'api.deboananet.club';

$planName = 'Plano Ativo';
$duration = '--';
$expiresHuman = '--';

if (!empty($mac_cliente)) {
    $db = new Database();
    $acesso = $db->getRow("
        SELECT p.name, p.duration_minutes, a.expira_em 
        FROM acessos_pix a 
        JOIN planos p ON a.plano_id = p.id 
        WHERE a.mac_address = ? AND a.status = 'ativo' 
        ORDER BY a.id DESC LIMIT 1
    ", [$mac_cliente]);
    
    if ($acesso) {
        $planName = $acesso['name'];
        $duration = $acesso['duration_minutes'];
        $expiresHuman = date('d/m/Y H:i', strtotime($acesso['expira_em']));
    }
}

// Prepara a URL invisível de login para o MikroTik autenticar o dispositivo
$mac_urlencoded = urlencode($mac_cliente);
$destinoFinalMikrotik = urlencode("https://www.google.com/"); // Destino padrão exigido pelo router interno
$linkLoginInvisivel = "https://{$mikrotikGateway}/login?username={$mac_urlencoded}&password={$mac_urlencoded}&dst={$destinoFinalMikrotik}";

// Define a rota do seu próprio portal onde o contador está localizado
$urlInicioContador = "/inicio?mac=" . urlencode($mac_cliente);
?>
	
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Acesso Liberado - Portal Hotspot</title>
    <link href="/src/bootstrap.min.css" rel="stylesheet">
    <style>
        body { background: linear-gradient(135deg, #11998e 0%, #38ef7d 100%); min-height: 100vh; display: flex; align-items: center; justify-content: center; }
        .success-container { max-width: 600px; width: 100%; }
        .card { border-radius: 15px; box-shadow: 0 10px 30px rgba(0,0,0,0.3); }
        .success-icon { font-size: 5rem; color: #38ef7d; }
    </style>
</head>
<body>
    <div class="success-container px-3">
        <div class="card">
            <div class="card-body text-center p-5">
                <div class="success-icon mb-4">✓</div>
                <h2 class="mb-4">Acesso Liberado!</h2>
                <div class="alert alert-success"><strong>Sua internet já está ativa.</strong></div>
                <div class="mb-4">
                    <p class="text-muted">Plano Liberado:</p>
                    <h4><?php echo htmlspecialchars($planName); ?></h4>
                    <p class="text-muted">Duração: <?php echo $duration; ?> minutos</p>
                </div>
                <p class="lead mb-4" id="texto-status">Conectando ao roteador e preparando seu relógio...</p>
                <?php if ($expiresHuman != '--'): ?>
                    <p class="small text-muted">Seu acesso expira em <?php echo $expiresHuman; ?></p>
                <?php endif; ?>
            </div>
        </div>
    </div>
    
    <script>
        // 👻 AUTENTICAÇÃO FANTASMA
        // Faz a requisição de login em segundo plano para o MikroTik liberar o dispositivo
        fetch("<?php echo $linkLoginInvisivel; ?>", { mode: 'no-cors' })
            .then(() => {
                // Assim que o roteador autenticar silenciosamente, joga de volta para a Home com o contador
                document.getElementById('texto-status').innerHTML = "Pronto! Abrindo seu painel de tempo...";
                setTimeout(() => {
                    window.location.href = "<?php echo $urlInicioContador; ?>";
                }, 800);
            })
            .catch(() => {
                // Força o redirecionamento para a Home em caso de qualquer bloqueio de política de rede
                window.location.href = "<?php echo $urlInicioContador; ?>";
            });
    </script>
</body>
</html>