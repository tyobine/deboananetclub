<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Tempo Esgotado - Portal Hotspot</title>
    <link href="/src/bootstrap.min.css" rel="stylesheet">
    <style>
        body { 
            background: linear-gradient(135deg, #f093fb 0%, #f5576c 100%); 
            min-height: 100vh; 
            display: flex; 
            align-items: center; 
            justify-content: center; 
        }
        .expired-container { 
            max-width: 600px; 
            width: 100%; 
        }
        .card { 
            border-radius: 15px; 
            box-shadow: 0 10px 30px rgba(0,0,0,0.3); 
        }
        .expired-icon { 
            font-size: 5rem; 
            color: #f5576c; 
            line-height: 1;
        }
    </style>
</head>
<body>
    <div class="expired-container px-3">
        <div class="card">
            <div class="card-body text-center p-5">
                <div class="expired-icon mb-4">⏱</div>
                <h2 class="mb-4">O tempo acabou!</h2>
                <div class="alert alert-warning">
                    <strong>Sua sessão de internet chegou ao fim.</strong>
                </div>
                <p class="lead mb-4">Não se preocupe! Para continuar conectado, basta escolher um novo plano.</p>
                <a href="/inicio" class="btn btn-primary btn-lg w-100">Ver Planos de Internet</a>
            </div>
        </div>
    </div>
</body>
</html>