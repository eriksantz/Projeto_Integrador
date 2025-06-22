<?php
session_start();

// --- Lógica de Flash Message (PASSO 1) ---
// Primeiro, verificamos se há mensagens de erro ou sucesso para exibir.
$mensagem_erro = '';
if (isset($_SESSION['erro_login'])) {
    $mensagem_erro = $_SESSION['erro_login'];
    unset($_SESSION['erro_login']);
}

$mensagem_sucesso = '';
if (isset($_SESSION['sucesso_redefinir'])) {
    $mensagem_sucesso = $_SESSION['sucesso_redefinir'];
    unset($_SESSION['sucesso_redefinir']);
}


// --- Lógica de Redirecionamento (PASSO 2 - CORRIGIDO) ---
// Agora, só redirecionamos se o usuário estiver logado E se não houver
// uma mensagem de erro importante para ser exibida.
// Isso permite que a página de login mostre "Conta bloqueada" sem entrar em loop.
if (isset($_SESSION['usuario_id']) && empty($mensagem_erro)) {
    $dashboard = (strtolower($_SESSION['tipo_usuario']) === 'gestor') ? 'painel_gestor.php' : 'painel_cliente.php';
    header("Location: painel/$dashboard");
    exit;
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <title>Login</title>
    <link rel="icon" type="image/png" sizes="16x16" href="imagens/fav_icon.png">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="login_cadastro.css"> <!-- Certifique-se que o nome do CSS está correto -->
    <style>
        /* Adicione este estilo para o card de sucesso */
        .success-card {
            padding: 15px; margin-bottom: 20px; border-radius: 8px; color: #155724;
            background-color: #d4edda; border: 1px solid #c3e6cb; text-align: center;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="left">
            <div class="green-bar"></div>
            <div class="left-content">
                <h1>Bem-vindo de volta</h1>
                <p>Acesse sua conta e continue sua jornada conosco.</p>
            </div>
        
            
        </div>
        <div class="right">
            <form action="processa_login.php" method="POST">
                <h2>👋 Seja bem-vindo de volta,<br>Faça login para continuar.</h2>
                <p class="sub">Insira seus dados e acesse a plataforma GAD.</p>
                
                <?php if ($mensagem_erro): ?>
                    <div class="error-card"><?php echo htmlspecialchars($mensagem_erro); ?></div>
                <?php endif; ?>

                <?php if ($mensagem_sucesso): ?>
                    <div class="success-card"><?php echo htmlspecialchars($mensagem_sucesso); ?></div>
                <?php endif; ?>

                <label for="tipo_usuario">Você está entrando como:</label>
                <select name="tipo_usuario" id="tipo_usuario" required>
                    <option value="Cliente">Cliente</option>
                    <option value="Gestor">Gestor</option>
                </select>

                <label for="email">Email</label>
                <input type="email" name="email" placeholder="Digite seu email" required>

                <label for="senha">Senha</label>
                <input type="password" name="senha" placeholder="Digite sua senha" required>
                
                <div class="form-link" style="text-align: right; margin-top: 5px; margin-bottom: 15px;">
                    <a href="recuperar-senha.php">Esqueceu sua senha?</a>
                </div>

                <button type="submit">Entrar</button>

                <div class="link">
                    <p>Não tem uma conta? <a href="cadastro.html">Crie aqui</a></p>
                </div>
            </form>
        </div>
    </div>
</body>
</html>
