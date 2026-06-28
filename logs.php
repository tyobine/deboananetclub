<?php
// public_html/logs.php - Visualizador de Logs em Tempo Real

require_once __DIR__ . '/config/config.php';

// 🔒 TRAVA DE SEGURANÇA VIA SESSÃO
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
if (!isset($_SESSION['admin_logado']) || $_SESSION['admin_logado'] !== true) {
    header("Location: /admin/login");
    exit;
}

$arquivoLog = __DIR__ . '/webhook_log.txt';

if (isset($_GET['limpar']) && $_GET['limpar'] == '1') {
    file_put_contents($arquivoLog, "--- LOG LIMPO EM " . date('d/m/Y H:i:s') . " ---\n");
    header("Location: logs.php");
    exit;
}

$conteudoLog = file_exists($arquivoLog) ? file_get_contents($arquivoLog) : "Arquivo de log ainda não foi criado.";
?>

<?php include __DIR__ . '/views/admin/header.php'; ?>

<style>
    body { background-color: #1e1e1e !important; }
    .header-log {
        display: flex;
        justify-content: space-between;
        align-items: center;
        margin-bottom: 20px;
        border-bottom: 1px solid #333;
        padding-bottom: 15px;
    }
    .terminal-box {
        background-color: #000000;
        border: 1px solid #333;
        border-radius: 5px;
        padding: 15px;
        height: 68vh;
        overflow-y: auto;
        white-space: pre-wrap;
        word-wrap: break-word;
        font-size: 14px;
        line-height: 1.5;
        box-shadow: inset 0 0 10px rgba(0,0,0,0.8);
        font-family: 'Courier New', Courier, monospace;
    }
    .log-line {
        border-bottom: 1px dashed #111;
        padding: 3px 0;
        color: #d4d4d4;
    }
    .log-line:hover { background-color: #0c0c0c; }
    .success { color: #4CAF50; font-weight: bold; }
    .error { color: #F44336; font-weight: bold; }
    .warning { color: #FFEB3B; font-weight: bold; }
    .info { color: #03A9F4; font-weight: bold; }
</style>

<div class="container mt-2">
    <div class="header-log">
        <div>
            <h4 class="m-0 text-white"><i class="fa-solid fa-terminal text-success"></i> Monitor de Processos ao Vivo</h4>
            <small class="text-muted font-monospace">A página pisca e se atualiza sozinha de 5 em 5 segundos...</small>
        </div>
        <div>
            <a href="logs.php?limpar=1" class="btn btn-danger btn-sm fw-bold shadow-sm" onclick="return confirm('Tem certeza que deseja apagar o histórico de logs atual?');">
                <i class="fa-solid fa-trash-can"></i> Limpar Histórico
            </a>
        </div>
    </div>

    <div class="terminal-box" id="terminal">
<?php 
$linhas = explode("\n", htmlspecialchars($conteudoLog));
foreach ($linhas as $linha) {
    if (trim($linha) === '') continue;
    
    if (stripos($linha, 'SUCESSO') !== false || stripos($linha, 'approved') !== false || stripos($linha, '200') !== false) {
        $linha = "<span class='success'>$linha</span>";
    } elseif (stripos($linha, 'ERRO') !== false || stripos($linha, 'FALHA') !== false || stripos($linha, 'inválida') !== false) {
        $linha = "<span class='error'>$linha</span>";
    } elseif (stripos($linha, 'AVISO') !== false || stripos($linha, 'estornado') !== false) {
        $linha = "<span class='warning'>$linha</span>";
    } elseif (stripos($linha, 'RECEBEU WEBHOOK') !== false || stripos($linha, 'BUSCA MAC') !== false) {
        $linha = "<span class='info'>$linha</span>";
    }
    
    echo "<div class='log-line'>$linha</div>";
}
?>
    </div>
</div>

<script>
    // Desce a barra de rolagem até a última linha salva
    var terminal = document.getElementById("terminal");
    terminal.scrollTop = terminal.scrollHeight;
    
    // Auto-update inteligente de 5s
    setTimeout(function() { window.location.reload(); }, 5000);
</script>

<?php include __DIR__ . '/views/admin/footer.php'; ?>