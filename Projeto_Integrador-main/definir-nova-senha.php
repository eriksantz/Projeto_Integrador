<?php
session_start();
require 'conexao.php';

if (!isset($_GET['token'])) {
    exit('Token não fornecido.');
}

$token = $_GET['token'];

// Tenta encontrar o token na tabela de gestores
$stmt_g = $pdo->prepare("SELECT id FROM gestores WHERE token_recuperacao = ? AND token_expira_em > NOW()");
$stmt_g->execute([$token]);
$usuario_g = $stmt_g->fetch();

// Tenta encontrar o token na tabela de clientes
$stmt_c = $pdo->prepare("SELECT id FROM clientes WHERE token_recuperacao = ? AND token_expira_em > NOW()");
$stmt_c->execute([$token]);
$usuario_c = $stmt_c->fetch();

if (!$usuario_g && !$usuario_c) {
    exit('Token inválido ou expirado. Por favor, solicite um novo link de recuperação.');
}

// O token é válido. Mostra o formulário para redefinir a senha.
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <title>Definir Nova Senha</title>
     <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="login_cadastro.css">
</head>
<body>
    <div class="container">
        <div class="left"></div>
        <div class="right">
            <form action="processa-nova-senha.php" method="POST">
                <h2>Defina sua Nova Senha</h2>
                <input type="hidden" name="token" value="<?php echo htmlspecialchars($token); ?>">
                <label for="nova_senha">Nova Senha</label>
                <input type="password" name="nova_senha" required>
                <label for="confirmar_senha">Confirmar Nova Senha</label>
                <input type="password" name="confirmar_senha" required>
                <button type="submit">Redefinir Senha</button>
            </form>
        </div>
    </div>
</body>
</html>
