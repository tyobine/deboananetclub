<?php
// controllers/AdminController.php

require_once __DIR__ . '/../models/Database.php';
require_once __DIR__ . '/../config/config.php';

class AdminController {

    // Inicia a sessão automaticamente para toda a classe
    public function __construct() {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
    }
    
    // Trava de segurança para métodos restritos
    private function proteger() {
        if (!isset($_SESSION['admin_logado']) || $_SESSION['admin_logado'] !== true) {
            header("Location: /admin/login");
            exit;
        }
    }

    // ==========================================
    // 🔐 MÉTODOS DE LOGIN / AUTENTICAÇÃO
    // ==========================================
    public function loginTela() {
        if (isset($_SESSION['admin_logado']) && $_SESSION['admin_logado'] === true) {
            header("Location: /admin/dashboard");
            exit;
        }
        require_once __DIR__ . '/../views/admin/login.php';
    }

    public function loginAutenticar() {
        $usuarioDigitado = $_POST['user'] ?? '';
        $senhaDigitada = $_POST['pass'] ?? '';

        if ($usuarioDigitado === ADMIN_USER && $senhaDigitada === ADMIN_PASS) {
            $_SESSION['admin_logado'] = true;
            header("Location: /admin/dashboard");
            exit;
        } else {
            header("Location: /admin/login?erro=1");
            exit;
        }
    }

    public function logout() {
        session_destroy();
        header("Location: /admin/login");
        exit;
    }
    
    // ==========================================
    // 🧹 MÁGICA DA AUTO-LIMPEZA 
    // ==========================================
    private function atualizarExpirados($db) {
        date_default_timezone_set('America/Fortaleza');
        $db->query("
            UPDATE acessos_pix 
            SET status = 'expirado' 
            WHERE status = 'ativo' AND expira_em < NOW()
        ");
    }

    // ==========================================
    // 📊 DASHBOARD
    // ==========================================
    public function dashboard() {
        $this->proteger();
        $db = new Database();
        
        $this->atualizarExpirados($db); 
        
        $hoje = $db->getRow("
            SELECT SUM(p.price_cents) as total 
            FROM acessos_pix a 
            INNER JOIN planos p ON a.plano_id = p.id 
            WHERE a.status IN ('ativo', 'expirado') AND DATE(a.expira_em) = CURDATE()
        ");
        $faturamentoHoje = ($hoje['total'] ?? 0) / 100;

        $mes = $db->getRow("
            SELECT SUM(p.price_cents) as total 
            FROM acessos_pix a 
            INNER JOIN planos p ON a.plano_id = p.id 
            WHERE a.status IN ('ativo', 'expirado') AND MONTH(a.expira_em) = MONTH(CURDATE()) AND YEAR(a.expira_em) = YEAR(CURDATE())
        ");
        $faturamentoMes = ($mes['total'] ?? 0) / 100;

        require_once __DIR__ . '/../models/Mikrotik.php';
        $mk = new Mikrotik();
        $clientesAtivos = $mk->contarUtilizadoresAtivos();

        $vendas7Dias = $db->getAll("
            SELECT DATE_FORMAT(a.expira_em, '%d/%m') as dia, SUM(p.price_cents) as total
            FROM acessos_pix a
            INNER JOIN planos p ON a.plano_id = p.id
            WHERE a.status IN ('ativo', 'expirado')
            GROUP BY DATE(a.expira_em)
            ORDER BY DATE(a.expira_em) ASC 
            LIMIT 7
        ");

        $graficoDados = ['dias' => [], 'valores' => []];
        foreach ($vendas7Dias as $venda) {
            $graficoDados['dias'][] = $venda['dia'];
            $graficoDados['valores'][] = $venda['total'] / 100;
        }
        if (empty($graficoDados['dias'])) {
            $graficoDados['dias'] = [date('d/m')];
            $graficoDados['valores'] = [0];
        }

        require_once __DIR__ . '/../views/admin/dashboard.php';
    }
    
    // ==========================================
    // ⚙️ MÉTODOS DE PLANOS
    // ==========================================
    public function planos() {
        $this->proteger();
        $db = new Database();
        $plans = $db->getAll("SELECT * FROM planos ORDER BY id DESC");
        require_once __DIR__ . '/../views/admin/planos.php';
    }

    public function criarPlano() {
        $this->proteger();
        $db = new Database();
        
        $name = $_POST['name'] ?? '';
        $price = $_POST['price_cents'] ?? 0;
        $duration = $_POST['duration_minutes'] ?? 0;

        $db->query("INSERT INTO planos (name, price_cents, duration_minutes) VALUES (?, ?, ?)", [$name, $price, $duration]);
        header("Location: /admin/plans"); 
        exit;
    }

    public function atualizarPlano() {
        $this->proteger();
        $db = new Database();
        
        $id = $_POST['id'] ?? 0;
        $name = $_POST['name'] ?? '';
        $price = $_POST['price_cents'] ?? 0;
        $duration = $_POST['duration_minutes'] ?? 0;

        $db->query("UPDATE planos SET name = ?, price_cents = ?, duration_minutes = ? WHERE id = ?", [$name, $price, $duration, $id]);
        header("Location: /admin/plans"); 
        exit;
    }

    public function toggleStatus() {
        $this->proteger();
        $db = new Database();
        
        $id = $_POST['id'] ?? 0;
        
        $db->query("UPDATE planos SET ativo = NOT ativo WHERE id = ?", [$id]);
        header("Location: /admin/plans"); 
        exit;
    }

    public function deletarPlano() {
        $this->proteger();
        $db = new Database();
        
        $id = $_POST['id'] ?? 0;
        
        $db->query("DELETE FROM planos WHERE id = ?", [$id]);
        header("Location: /admin/plans"); 
        exit;
    }

    // ==========================================
    // 📺 GERENCIAMENTO DE ANÚNCIO (BIBLIOTECA)
    // ==========================================
    public function gerenciarAnuncio() {
        $this->proteger();
        $db = new Database();
        
        $q_tipo = $db->getRow("SELECT valor FROM configuracoes WHERE chave = 'ad_tipo'");
        $q_url = $db->getRow("SELECT valor FROM configuracoes WHERE chave = 'ad_url'");
        $q_link = $db->getRow("SELECT valor FROM configuracoes WHERE chave = 'ad_link'"); 
        
        $atual_tipo = $q_tipo ? $q_tipo['valor'] : 'imagem';
        $atual_url = $q_url ? $q_url['valor'] : '';
        
        // Converte o JSON salvo no banco para um Array PHP contendo todos os links específicos
        $atual_link_json = $q_link ? $q_link['valor'] : '{}';
        $atual_link_arr = json_decode($atual_link_json, true);
        if (!is_array($atual_link_arr)) $atual_link_arr = [];
        
        // Lê a pasta de uploads para montar a biblioteca de mídia
        $pasta_uploads = __DIR__ . '/../uploads/';
        if (!is_dir($pasta_uploads)) {
            mkdir($pasta_uploads, 0777, true);
        }
        $arquivos = array_diff(scandir($pasta_uploads), array('.', '..'));
        
        require_once __DIR__ . '/../views/admin/anuncio.php';
    }

    public function salvarAnuncio() {
        $this->proteger();
        $db = new Database();
        
        $selecionados = $_POST['selecionados'] ?? [];
        $links = $_POST['links'] ?? []; // Pega todos os links enviados no form

        if (count($selecionados) == 1) {
            $ad_url = $selecionados[0];
            $ad_tipo = str_ends_with(strtolower($ad_url), '.mp4') ? 'video' : 'imagem';
        } elseif (count($selecionados) > 1) {
            $ad_url = json_encode($selecionados);
            $ad_tipo = 'rotativo';
        } else {
            $ad_url = '';
            $ad_tipo = 'imagem';
        }

        $db->query("UPDATE configuracoes SET valor = ? WHERE chave = 'ad_tipo'", [$ad_tipo]);
        $db->query("UPDATE configuracoes SET valor = ? WHERE chave = 'ad_url'", [$ad_url]);

        // Salva todos os links específicos convertidos em JSON no banco de dados
        $ad_link_json = json_encode($links);
        $existe_link = $db->getRow("SELECT valor FROM configuracoes WHERE chave = 'ad_link'");
        if ($existe_link) {
            $db->query("UPDATE configuracoes SET valor = ? WHERE chave = 'ad_link'", [$ad_link_json]);
        } else {
            $db->query("INSERT INTO configuracoes (chave, valor) VALUES ('ad_link', ?)", [$ad_link_json]);
        }

        header("Location: /admin/anuncio?sucesso=salvo");
        exit;
    }

    public function uploadMidia() {
        $this->proteger();
        if (isset($_FILES['arquivo_upload']) && $_FILES['arquivo_upload']['error'] === UPLOAD_ERR_OK) {
            $extensao = pathinfo($_FILES['arquivo_upload']['name'], PATHINFO_EXTENSION);
            $nome_arquivo = 'midia_' . time() . '.' . $extensao;
            $caminho_destino = __DIR__ . '/../uploads/' . $nome_arquivo;
            
            move_uploaded_file($_FILES['arquivo_upload']['tmp_name'], $caminho_destino);
        }
        header("Location: /admin/anuncio?sucesso=upload");
        exit;
    }

    public function deletarMidia() {
        $this->proteger();
        $arquivo = $_POST['arquivo'] ?? '';
        if (!empty($arquivo)) {
            $caminho = __DIR__ . '/../uploads/' . basename($arquivo);
            if (file_exists($caminho)) {
                unlink($caminho);
            }
        }
        header("Location: /admin/anuncio?sucesso=delete");
        exit;
    }

    // ==========================================
    // 📜 TRANSAÇÕES
    // ==========================================
    public function transacoes() {
        $this->proteger();
        $db = new Database();
        
        $this->atualizarExpirados($db); 
        
        // Puxamos a nova coluna "a.whatsapp"
        $sql = "
            SELECT 
                a.id, a.txid, a.ip_address, a.mac_address, a.whatsapp, a.status, a.expira_em,
                p.name AS plan_name, p.price_cents AS amount_cents 
            FROM acessos_pix a 
            LEFT JOIN planos p ON a.plano_id = p.id 
            ORDER BY a.id DESC LIMIT 50
        ";
        
        $transactions = $db->getAll($sql);
        require_once __DIR__ . '/../views/admin/transacoes.php';
    }
}
?>