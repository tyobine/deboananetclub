<?php
// views/admin/header.php
$uri = $_SERVER['REQUEST_URI'];
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Painel Admin - Portal Hotspot</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <style>
        body { background-color: #f8f9fa; }
        .navbar-brand { font-weight: 700; }
    </style>
</head>
<body>

    <nav class="navbar navbar-dark bg-dark mb-4 shadow-sm">
        <div class="container">
            <span class="navbar-brand text-white"><i class="fa-solid fa-wifi text-success"></i> Admin Hotspot</span>
            <div>
                <a href="/admin/dashboard" class="btn <?= (strpos($uri, 'dashboard') !== false || $uri == '/admin') ? 'btn-success' : 'btn-outline-light' ?> btn-sm me-1">
                    <i class="fa-solid fa-gauge"></i> Dashboard
                </a>
                
                <a href="/admin/plans" class="btn <?= strpos($uri, 'plans') !== false ? 'btn-success' : 'btn-outline-light' ?> btn-sm me-1">
                    <i class="fa-solid fa-box"></i> Planos
                </a>
                
                <a href="/admin/transactions" class="btn <?= strpos($uri, 'transactions') !== false ? 'btn-success' : 'btn-outline-light' ?> btn-sm me-1">
                    <i class="fa-solid fa-list-check"></i> Transações
                </a>
                
                <a href="/admin/anuncio" class="btn <?= strpos($uri, 'anuncio') !== false ? 'btn-success' : 'btn-outline-light' ?> btn-sm me-1">
                    <i class="fa-solid fa-tv"></i> Anúncios
                </a>
                
                <a href="/logs.php" class="btn <?= strpos($uri, 'logs') !== false ? 'btn-success' : 'btn-outline-light' ?> btn-sm me-1">
                    <i class="fa-solid fa-terminal"></i> Logs
                </a>
                
                <a href="/admin/logout" class="btn btn-danger btn-sm">
                    <i class="fa-solid fa-power-off"></i> Sair
                </a>
            </div>
        </div>
    </nav>