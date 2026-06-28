<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Acesso Patrocinado - Wi-Fi</title>
    <link href="/src/bootstrap.min.css" rel="stylesheet">
    <style>
        body {
            background: linear-gradient(135deg, #1e3c72 0%, #2a5298 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        .ad-container {
            max-width: 500px;
            width: 100%;
        }
        .card {
            border-radius: 15px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.3);
        }
        .midia-box {
            position: relative;
            min-height: 300px;
            display: flex;
            align-items: center;
            justify-content: center;
            background: #000;
            border-radius: 8px;
            overflow: hidden;
        }
        .midia-box img {
            width: 100%;
            height: auto;
            aspect-ratio: 3/4;
            object-fit: contain;
        }
        .midia-box video {
            width: 100%;
            height: auto;
            max-height: 70vh;
            pointer-events: none; 
        }
        #countdown {
            font-size: 1.2rem;
            font-weight: bold;
            color: #d9534f;
        }
        .btn-som {
            position: absolute;
            bottom: 15px;
            right: 15px;
            background: rgba(0, 0, 0, 0.7);
            color: #fff;
            border: 2px solid #fff;
            border-radius: 20px;
            padding: 5px 15px;
            font-size: 0.9rem;
            font-weight: bold;
            cursor: pointer;
            z-index: 10;
        }
        /* NOVO: Estilização da barra de progresso do vídeo */
        .video-progress {
            position: absolute;
            bottom: 22px;
            left: 15px;
            width: calc(100% - 150px); /* Calcula o espaço para não sobrepor o botão de som */
            z-index: 10;
            cursor: pointer;
            height: 6px;
            -webkit-appearance: none;
            appearance: none;
            background: rgba(255, 255, 255, 0.5);
            border-radius: 5px;
            outline: none;
        }
        .video-progress::-webkit-slider-thumb {
            -webkit-appearance: none;
            appearance: none;
            width: 15px;
            height: 15px;
            border-radius: 50%;
            background: #198754; /* Verde sucesso */
            cursor: pointer;
            box-shadow: 0 0 5px rgba(0,0,0,0.5);
        }
        .video-progress::-moz-range-thumb {
            width: 15px;
            height: 15px;
            border-radius: 50%;
            background: #198754;
            cursor: pointer;
            border: none;
            box-shadow: 0 0 5px rgba(0,0,0,0.5);
        }
    </style>
</head>
<body>
    <div class="ad-container px-3 text-center py-4">
        <div class="text-white mb-3">
            <h3 class="fw-bold">Acesso Patrocinado</h3>
            <p class="small">Wi-Fi gratuito oferecido pelo comércio local.</p>
        </div>

        <div class="card p-3">
            <div class="card-body p-1">
                
                <?php
                require_once __DIR__ . '/../models/Database.php';
                $db_pub = new Database();
                
                $q_tipo = $db_pub->getRow("SELECT valor FROM configuracoes WHERE chave = 'ad_tipo'");
                $q_url = $db_pub->getRow("SELECT valor FROM configuracoes WHERE chave = 'ad_url'");
                $q_link = $db_pub->getRow("SELECT valor FROM configuracoes WHERE chave = 'ad_link'"); 
                
                $tipo_anuncio = $q_tipo ? $q_tipo['valor'] : 'imagem';
                $link_midia_raw = $q_url ? $q_url['valor'] : '';
                
                $links_array = ($q_link && !empty($q_link['valor'])) ? json_decode($q_link['valor'], true) : [];
                if (!is_array($links_array)) $links_array = [];

                if ($tipo_anuncio === 'rotativo') {
                    $lista_midias = json_decode($link_midia_raw, true);
                    if (is_array($lista_midias) && count($lista_midias) > 0) {
                        $chave_sorteada = array_rand($lista_midias);
                        $link_midia = $lista_midias[$chave_sorteada];
                        $tipo_anuncio = str_ends_with(strtolower($link_midia), '.mp4') ? 'video' : 'imagem';
                    } else {
                        $link_midia = 'https://via.placeholder.com/1080x1440?text=Sem+Anuncio';
                        $tipo_anuncio = 'imagem';
                    }
                } else {
                    $link_midia = !empty($link_midia_raw) ? $link_midia_raw : 'https://via.placeholder.com/1080x1440?text=Sem+Anuncio';
                    if ($tipo_anuncio !== 'video' && $tipo_anuncio !== 'imagem') {
                        $tipo_anuncio = str_ends_with(strtolower($link_midia), '.mp4') ? 'video' : 'imagem';
                    }
                }

                $link_destino = (isset($links_array[$link_midia]) && !empty(trim($links_array[$link_midia]))) ? trim($links_array[$link_midia]) : '#';
                ?>

                <div class="mb-3 midia-box">
                    <div id="loading-spinner" class="spinner-border text-light" style="width: 3rem; height: 3rem;" role="status">
                        <span class="visually-hidden">A carregar...</span>
                    </div>

                    <a href="<?php echo htmlspecialchars($link_destino); ?>" <?php echo $link_destino !== '#' ? 'target="_blank"' : 'onclick="return false;"'; ?> style="display: block; width: 100%; text-decoration: none;">
                        <?php if ($tipo_anuncio === 'video'): ?>
                            <video id="ad-media" autoplay muted playsinline loop style="display: none; cursor: pointer;">
                                <source src="<?php echo htmlspecialchars($link_midia); ?>" type="video/mp4">
                            </video>
                        <?php else: ?>
                            <img id="ad-media" src="<?php echo htmlspecialchars($link_midia); ?>" alt="Patrocinador" style="display: none; cursor: pointer;">
                        <?php endif; ?>
                    </a>

                    <?php if ($tipo_anuncio === 'video'): ?>
                        <input type="range" id="video-progress" class="video-progress" value="0" min="0" max="100" step="0.1" style="display: none;">
                        <button id="btn-som" class="btn-som" style="display: none;">🔇 Ligar Som</button>
                    <?php endif; ?>
                </div>

                <div class="alert alert-info py-2 mb-2" id="status-box">
                    A carregar os detalhes do parceiro...
                </div>

                <form method="POST" action="/liberar-gratis-confirmado">
                    <input type="hidden" name="plan_id" value="<?php echo htmlspecialchars($plano_id ?? ''); ?>">
                    <input type="hidden" name="mac" value="<?php echo htmlspecialchars($mac ?? ''); ?>">
                    <input type="hidden" name="ip" value="<?php echo htmlspecialchars($ip ?? ''); ?>">
                    
                    <div class="text-start mb-3" id="whatsapp-box" style="display: none;">
                        <label class="form-label small fw-bold text-secondary mb-1">Para liberar a internet, informe seu WhatsApp:</label>
                        <input type="tel" name="whatsapp" id="whatsapp-input" class="form-control form-control-lg text-center fw-bold" placeholder="(88) 99999-9999" required autocomplete="tel">
                    </div>

                    <button type="submit" id="btn-liberar" class="btn btn-secondary btn-lg w-100 fw-bold" disabled>
                        ⏳ Aguarde...
                    </button>
                </form>
            </div>
        </div>
    </div>

    <script>
        (function() {
            var tempoRestante = 15;
            var countdownElement = null; 
            var btnLiberar = document.getElementById('btn-liberar');
            var statusBox = document.getElementById('status-box');
            var formLiberar = document.querySelector('form');
            var mediaElement = document.getElementById('ad-media');
            var spinner = document.getElementById('loading-spinner');
            var wppBox = document.getElementById('whatsapp-box');
            var wppInput = document.getElementById('whatsapp-input');
            
            // Elementos do vídeo
            var btnSom = document.getElementById('btn-som');
            var progressBar = document.getElementById('video-progress');
            
            var tempoEsgotado = false;
            
            wppInput.addEventListener('input', function(e) {
                var x = e.target.value.replace(/\D/g, '').match(/(\d{0,2})(\d{0,5})(\d{0,4})/);
                e.target.value = !x[2] ? x[1] : '(' + x[1] + ') ' + x[2] + (x[3] ? '-' + x[3] : '');
                verificarLiberacao();
            });

            function verificarLiberacao() {
                if (tempoEsgotado && wppInput.value.length >= 14) {
                    btnLiberar.removeAttribute('disabled');
                    btnLiberar.className = "btn btn-success btn-lg w-100 fw-bold";
                    btnLiberar.innerText = "🚀 Liberar Minha Internet!";
                } else if (tempoEsgotado) {
                    btnLiberar.setAttribute('disabled', 'true');
                    btnLiberar.className = "btn btn-secondary btn-lg w-100 fw-bold";
                    btnLiberar.innerText = "Insira o número acima";
                }
            }

            // Lógica de Controles de Vídeo (Som e Barra de Progresso)
            if (mediaElement && mediaElement.tagName === 'VIDEO') {
                
                if (btnSom) {
                    btnSom.addEventListener('click', function(e) {
                        e.preventDefault();
                        e.stopPropagation(); 
                        if (mediaElement.muted) {
                            mediaElement.muted = false;
                            btnSom.innerHTML = "🔊 Desligar Som";
                        } else {
                            mediaElement.muted = true;
                            btnSom.innerHTML = "🔇 Ligar Som";
                        }
                    });
                }

                if (progressBar) {
                    // Atualiza a barra sozinha conforme o vídeo vai tocando
                    mediaElement.addEventListener('timeupdate', function() {
                        if (mediaElement.duration) {
                            var percentage = (mediaElement.currentTime / mediaElement.duration) * 100;
                            progressBar.value = percentage;
                        }
                    });

                    // Ouve quando o utilizador arrastar a barra para trás/frente
                    progressBar.addEventListener('input', function(e) {
                        e.preventDefault();
                        e.stopPropagation(); 
                        var seekTime = (progressBar.value / 100) * mediaElement.duration;
                        mediaElement.currentTime = seekTime;
                    });
                }
            }

            function iniciarCronometro() {
                if (spinner) spinner.style.display = 'none';
                if (mediaElement) mediaElement.style.display = 'block';
                if (btnSom) btnSom.style.display = 'block'; 
                if (progressBar) progressBar.style.display = 'block'; // Mostra a barra junto com o vídeo
                
                if (statusBox) {
                    statusBox.innerHTML = 'Assista por <span id="countdown">15</span> seg...';
                    countdownElement = document.getElementById('countdown');
                }
                if (btnLiberar) {
                    btnLiberar.innerText = "⏳ Assista ao anúncio...";
                }

                var cronometro = setInterval(function() {
                    tempoRestante--;
                    if (tempoRestante > 0) {
                        if (countdownElement) countdownElement.innerText = tempoRestante;
                    } else {
                        clearInterval(cronometro);
                        tempoEsgotado = true;
                        
                        wppBox.style.display = 'block';
                        wppInput.focus();
                        
                        if (statusBox) {
                            statusBox.className = "alert alert-success py-2 mb-2";
                            statusBox.innerHTML = "<strong>Tempo concluído!</strong> Preencha os dados abaixo.";
                        }
                        
                        verificarLiberacao(); 
                    }
                }, 1000);
            }

            if (mediaElement) {
                var mediaCarregada = false;
                var eventoCarregamento = mediaElement.tagName === 'VIDEO' ? 'canplaythrough' : 'load';
                
                mediaElement.addEventListener(eventoCarregamento, function() {
                    if (!mediaCarregada) {
                        mediaCarregada = true;
                        iniciarCronometro();
                    }
                });

                setTimeout(function() {
                    if (!mediaCarregada) {
                        mediaCarregada = true;
                        iniciarCronometro();
                    }
                }, 6000);
            } else {
                iniciarCronometro();
            }

            if (formLiberar && btnLiberar) {
                formLiberar.addEventListener('submit', function(e) {
                    if (wppInput.value.length < 14) {
                        e.preventDefault();
                        alert("Por favor, introduza um número de WhatsApp válido.");
                        return;
                    }
                    btnLiberar.setAttribute('disabled', 'true');
                    btnLiberar.innerHTML = '<span class="spinner-border spinner-border-sm me-2" role="status" aria-hidden="true"></span> A conectar...';
                });
            }
        })();
    </script>
</body>
</html>