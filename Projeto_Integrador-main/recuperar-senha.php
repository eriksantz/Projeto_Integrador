<?php
session_start();
$mensagem = '';
if(isset($_SESSION['recupera_msg'])){
    $mensagem = $_SESSION['recupera_msg'];
    unset($_SESSION['recupera_msg']);
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <title>Recuperar Senha</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="login_cadastro.css">
    <link rel="icon" type="image/png" sizes="16x16" href="imagens/fav_icon.png">
</head>
<body>
    <div class="container">
        <div class="left">
            <!-- Conteúdo decorativo -->
        </div>
        <div class="right">
            <form action="processa-recuperacao.php" method="POST">
                <h2>Recuperar Senha</h2>
                <p class="sub">Digite seu e-mail e o tipo de conta para enviarmos um link de recuperação.</p>
                <?php if ($mensagem) echo "<div>$mensagem</div>"; ?>
                
                <label for="tipo_usuario">Sua conta é de:</label>
                <select name="tipo_usuario" required>
                    <option value="Cliente">Cliente</option>
                    <option value="Gestor">Gestor</option>
                </select>

                <label for="email">Email</label>
                <input type="email" name="email" placeholder="Digite o e-mail da sua conta" required>
                <button type="submit">Gerar Link de Recuperação</button>
                 <div class="link">
                    <p>Lembrou a senha? <a href="login.php">Faça login</a></p>
                </div>
            </form>
        </div>
    </div>
</body>
</html>
