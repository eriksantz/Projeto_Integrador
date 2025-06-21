<?php
session_start();
require 'conexao.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    exit('Acesso inválido');
}

$email = $_POST['email'];
$tipo_usuario = strtolower($_POST['tipo_usuario']);
$tabela = ($tipo_usuario === 'gestor') ? 'gestores' : 'clientes';

$stmt = $pdo->prepare("SELECT id FROM $tabela WHERE email = ?");
$stmt->execute([$email]);
$usuario = $stmt->fetch();

if (!$usuario) {
    $_SESSION['recupera_msg'] = '<div class="error-card">Nenhuma conta encontrada com este e-mail e tipo de usuário.</div>';
    header('Location: recuperar-senha.php');
    exit;
}

// Gera um token seguro e único
$token = bin2hex(random_bytes(32));
$expira_em = date('Y-m-d H:i:s', time() + 3600); // Token válido por 1 hora

$sql_update = "UPDATE $tabela SET token_recuperacao = ?, token_expira_em = ? WHERE id = ?";
$stmt_update = $pdo->prepare($sql_update);
$stmt_update->execute([$token, $expira_em, $usuario['id']]);

// --- MENSAGEM MELHORADA ---
$link = "http://localhost/Projeto_Integrador-main/definir-nova-senha.php?token=$token";

// Cria uma mensagem mais limpa e profissional
$mensagem = "<div class='success-card'>"
          . "Link de recuperação gerado com sucesso!<br>"
          . "<small>Em um ambiente real, este link seria enviado para seu e-mail.</small><br><br>"
          // O link agora tem um texto amigável e abre em uma nova aba
          . "<strong><a href='$link' target='_blank'>Clique aqui para redefinir sua senha</a></strong>"
          . "</div>";

$_SESSION['recupera_msg'] = $mensagem;

header('Location: recuperar-senha.php');
exit;
