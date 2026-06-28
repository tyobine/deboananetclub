<?php 
require_once __DIR__ . '/header.php'; 

// Lê o que está ativo no momento para deixar a caixa já marcada na tela
$selecionados_atuais = [];
if ($atual_tipo === 'rotativo') {
    $selecionados_atuais = json_decode($atual_url, true);
    if (!is_array($selecionados_atuais)) $selecionados_atuais = [];
} elseif (!empty($atual_url)) {
    $selecionados_atuais = [$atual_url];
}
$total_ativos = count($selecionados_atuais);
?>

<style>
    .media-preview { width: 100%; height: 140px; object-fit: cover; border-radius: 6px; background: #111; }
    .media-card { transition: transform 0.2s; border: 1px solid #ddd; }
    .media-card:hover { transform: scale(1.02); box-shadow: 0 4px 15px rgba(0,0,0,0.1); }
    .box-ativo { border: 2px solid #198754; background-color: #f8fff9; }
</style>

<div class="container mb-5">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h3 class="text-secondary mb-0"><i class="fa-solid fa-rectangle-ad"></i> Gerenciador de Publicidade</h3>
    </div>

    <?php if(isset($_GET['sucesso'])): ?>
        <div class="alert alert-success alert-dismissible fade show shadow-sm">
            <strong>Sucesso!</strong> As configurações de exibição foram atualizadas.
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    <?php endif; ?>

    <div class="row g-4">
        
        <div class="col-md-4">
            
            <div class="card shadow-sm mb-4">
                <div class="card-header bg-dark text-white fw-bold">
                    <i class="fa-solid fa-chart-pie"></i> Status Atual
                </div>
                <div class="card-body text-center p-4">
                    <?php if ($total_ativos == 0): ?>
                        <h5 class="text-danger fw-bold">Nenhum Anúncio Ativo</h5>
                        <p class="small text-muted">A internet grátis não está exibindo patrocinadores.</p>
                    <?php elseif ($total_ativos == 1): ?>
                        <i class="fa-solid fa-anchor fa-3x mb-3 text-success"></i>
                        <h5 class="text-success fw-bold">Fixo (Exclusivo)</h5>
                        <p class="small text-muted mb-0">Um único anúncio sendo exibido para 100% dos clientes.</p>
                    <?php else: ?>
                        <i class="fa-solid fa-shuffle fa-3x mb-3 text-warning"></i>
                        <h5 class="text-warning fw-bold">Rotativo Ativo</h5>
                        <p class="small text-muted mb-0">Sorteando entre <b><?php echo $total_ativos; ?> anúncios</b> diferentes.</p>
                    <?php endif; ?>
                </div>
            </div>

            <div class="card shadow-sm border-0">
                <div class="card-header bg-white fw-bold text-primary">
                    <i class="fa-solid fa-cloud-arrow-up"></i> Enviar Novo Arquivo
                </div>
                <div class="card-body">
                    <form action="/admin/anuncio/upload" method="POST" enctype="multipart/form-data">
                        <div class="mb-3">
                            <label class="form-label small text-muted">Formatos: PNG, JPG ou MP4 (Máx 20MB)</label>
                            <input type="file" name="arquivo_upload" class="form-control" accept="image/*,video/mp4" required>
                        </div>
                        <button type="submit" class="btn btn-primary w-100 fw-bold">Fazer Upload</button>
                    </form>
                </div>
            </div>
        </div>

        <div class="col-md-8">
            <div class="card shadow-sm border-0 h-100">
                
                <form id="formSelecao" action="/admin/anuncio/salvar" method="POST">

                    <div class="card-header bg-white border-bottom p-3 d-flex justify-content-between align-items-center flex-wrap">
                        <h5 class="mb-0 fw-bold text-dark"><i class="fa-solid fa-images text-primary"></i> Biblioteca de Mídias</h5>
                        
                        <button type="submit" class="btn btn-success fw-bold shadow-sm mt-2 mt-md-0">
                            <i class="fa-solid fa-check-double"></i> Salvar Tudo
                        </button>
                    </div>

                    <div class="card-body bg-light">
                        <p class="text-muted small mb-4">Marque as mídias que deseja exibir e (opcionalmente) insira o link para qual o cliente será redirecionado se ele tocar na imagem.</p>
                        
                        <div class="row g-3">
                            <?php if (empty($arquivos)): ?>
                                <div class="text-center w-100 py-5 text-muted">
                                    <i class="fa-solid fa-folder-open fa-3x mb-3 text-secondary opacity-50"></i>
                                    <h5>Nenhum arquivo na biblioteca.</h5>
                                </div>
                            <?php else: ?>
                                <?php foreach($arquivos as $index => $arq): ?>
                                    <?php 
                                        $is_video = str_ends_with(strtolower($arq), '.mp4'); 
                                        $caminho_relativo = "/uploads/" . $arq;
                                        $is_ativo = in_array($caminho_relativo, $selecionados_atuais);
                                    ?>
                                    
                                    <div class="col-6 col-md-6 col-lg-4">
                                        <div class="card media-card h-100 p-2 position-relative <?php echo $is_ativo ? 'box-ativo' : ''; ?>">
                                            
                                            <?php if($is_video): ?>
                                                <video class="media-preview" muted><source src="<?php echo $caminho_relativo; ?>"></video>
                                            <?php else: ?>
                                                <img src="<?php echo $caminho_relativo; ?>" class="media-preview">
                                            <?php endif; ?>
                                            
                                            <div class="small text-truncate text-center mt-2 mb-1 text-secondary fw-bold" title="<?php echo $arq; ?>"><?php echo $arq; ?></div>
                                            
                                            <div class="mb-2 px-1">
                                                <input type="url" name="links[<?php echo $caminho_relativo; ?>]" class="form-control form-control-sm text-center" placeholder="Link (Ex: WhatsApp)" value="<?php echo htmlspecialchars($atual_link_arr[$caminho_relativo] ?? ''); ?>">
                                            </div>

                                            <div class="d-flex justify-content-between align-items-center mt-auto border-top pt-2">
                                                
                                                <div class="form-check form-switch ms-2 mb-0">
                                                    <input class="form-check-input" type="checkbox" role="switch" name="selecionados[]" value="<?php echo $caminho_relativo; ?>" id="chk_<?php echo $index; ?>" <?php echo $is_ativo ? 'checked' : ''; ?>>
                                                    <label class="form-check-label fw-bold text-success" for="chk_<?php echo $index; ?>">Exibir</label>
                                                </div>

                                                <button type="submit" name="arquivo" value="<?php echo htmlspecialchars($arq); ?>" formaction="/admin/anuncio/delete" formmethod="POST" class="btn btn-outline-danger btn-sm" onclick="return confirm('Apagar este arquivo do servidor?');" title="Excluir arquivo">
                                                    <i class="fa-solid fa-trash-can"></i>
                                                </button>
                                            </div>

                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </div>
                    </div>
                </form>
                </div>
        </div>

    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>