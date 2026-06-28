<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - Administrativo</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body {
            background: linear-gradient(135deg, #1e3c72 0%, #2a5298 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
        }
        .login-card {
            border-radius: 15px;
            box-shadow: 0 10px 25px rgba(0,0,0,0.3);
        }
    </style>
</head>
<body>
<div class="container">
    <div class="row justify-content-center">
        <div class="col-md-4">
            <div class="card login-card p-4 border-0">
                <h3 class="text-center fw-bold text-dark mb-4">Painel Admin</h3>
                
                <?php if (isset($_GET['erro'])): ?>
                    <div class="alert alert-danger text-center py-2 small">Usuário ou senha incorretos!</div>
                <?php endif; ?>

                <form action="/admin/login/auth" method="POST">
                    <div class="mb-3">
                        <label class="form-label small fw-bold">Usuário</label>
                        <input type="text" name="user" class="form-control" required autocomplete="off">
                    </div>
                    <div class="mb-4">
                        <label class="form-label small fw-bold">Senha</label>
                        <input type="password" name="pass" class="form-control" required>
                    </div>
                    <div class="d-grid">
                        <button type="submit" class="btn btn-primary fw-bold py-2" style="background: #1e3c72; border: 0;">Entrar no Painel</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
</body>
</html>