<?php
// index.php (Fica solto na raiz do projeto)

require_once 'controllers/HotspotController.php';
require_once 'controllers/WebhookController.php';
require_once 'controllers/AdminController.php';

// Captura a URL acessada. Se não houver, o padrão é 'inicio'
$url = $_GET['url'] ?? 'inicio';

// Remove barras residuais do início ou final para evitar erros de rota
$url = trim($url, '/');

// Se a URL ficou vazia (ex: acessou só o domínio principal), força para inicio
if (empty($url)) {
    $url = 'inicio';
}

switch ($url) {
    // ==========================================
    // ÁREA DO CLIENTE (HOTSPOT)
    // ==========================================
    case 'inicio':
        $controller = new HotspotController();
        $controller->index();
        break;
        
    case 'gerar-pix':
        $controller = new HotspotController();
        $controller->gerarCobranca();
        break;
        
    case 'webhook':
        $controller = new WebhookController();
        $controller->receberNotificacao();
        break;

    case 'status':
        // Redireciona a antiga tela de status para a nova tela inicial
        header("Location: /inicio");
        exit;
    
    case 'checar-status':
        $controller = new HotspotController();
        $controller->checarStatus(); // Rota consultada pelo JavaScript do pagamento
        break;
        
    case 'sucesso':
        require_once 'views/sucesso.php'; 
        break;

    case 'liberar-gratis-confirmado':
        $controller = new HotspotController();
        $controller->liberarGratisConfirmado();
        break;
        
    case 'expirado':
        require_once 'views/expirado.php'; 
        break;

    // ==========================================
    // ÁREA DO ADMINISTRADOR
    // ==========================================
    
    case 'admin':
        // Atalho rápido: se digitar apenas /admin, joga pro dashboard
        header("Location: /admin/dashboard");
        exit;

    case 'admin/dashboard':
        $controller = new AdminController();
        $controller->dashboard();
        break;

    case 'admin/plans':
        $controller = new AdminController();
        $controller->planos();
        break;
        
    case 'admin/plans/create':
        $controller = new AdminController();
        $controller->criarPlano();
        break;
        
    case 'admin/plans/update':
        $controller = new AdminController();
        $controller->atualizarPlano();
        break;

    case 'admin/plans/toggle':
        $controller = new AdminController();
        $controller->toggleStatus();
        break;
        
    case 'admin/plans/delete':
        $controller = new AdminController();
        $controller->deletarPlano();
        break;
        
    case 'admin/transactions':
        $controller = new AdminController();
        $controller->transacoes();
        break;

    // 📺 ROTAS DE PUBLICIDADE NO ADMIN
    case 'admin/anuncio':
        $controller = new AdminController();
        $controller->gerenciarAnuncio();
        break;

    case 'admin/anuncio/salvar':
        $controller = new AdminController();
        $controller->salvarAnuncio();
        break;

    case 'admin/anuncio/upload':
        $controller = new AdminController();
        $controller->uploadMidia();
        break;

    case 'admin/anuncio/delete':
        $controller = new AdminController();
        $controller->deletarMidia();
        break;

    case 'admin/login':
        $controller = new AdminController();
        $controller->loginTela();
        break;

    case 'admin/login/auth':
        $controller = new AdminController();
        $controller->loginAutenticar();
        break;

    case 'admin/logout':
        $controller = new AdminController();
        $controller->logout();
        break;

    // ==========================================
    // PÁGINA NÃO ENCONTRADA (404)
    // ==========================================
    default:
        http_response_code(404);
        echo "<h1>404 - Página não encontrada</h1>";
        break;
}
?>