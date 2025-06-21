<?php
session_start();
require 'conexao.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    exit('Acesso inválido.');
}

$token = $_POST['token'];
$nova_senha = $_POST['nova_senha'];
$confirmar_senha = $_POST['confirmar_senha'];

if ($nova_senha !== $confirmar_senha) {
    // Idealmente, adicionar uma mensagem de erro na página
    exit('As senhas não correspondem.');
}

// Descobre a qual tabela e usuário o token pertence
$tabela = '';
$usuario_id = null;

$stmt_g = $pdo->prepare("SELECT id FROM gestores WHERE token_recuperacao = ? AND token_expira_em > NOW()");
$stmt_g->execute([$token]);
if ($stmt_g->fetch()) {
    $tabela = 'gestores';
    $stmt_g->execute([$token]);
    $usuario_id = $stmt_g->fetchColumn();
} else {
    $stmt_c = $pdo->prepare("SELECT id FROM clientes WHERE token_recuperacao = ? AND token_expira_em > NOW()");
    $stmt_c->execute([$token]);
    if ($stmt_c->fetch()) {
        $tabela = 'clientes';
        $stmt_c->execute([$token]);
        $usuario_id = $stmt_c->fetchColumn();
    }
}

if (!$tabela || !$usuario_id) {
    exit('Token inválido ou expirado.');
}

// Atualiza a senha e invalida o token
$hash_nova_senha = password_hash($nova_senha, PASSWORD_DEFAULT);
$sql = "UPDATE $tabela SET senha = ?, token_recuperacao = NULL, token_expira_em = NULL WHERE id = ?";
$stmt = $pdo->prepare($sql);
$stmt->execute([$hash_nova_senha, $usuario_id]);

$_SESSION['sucesso_redefinir'] = "Sua senha foi redefinida com sucesso! Você já pode fazer o login.";
header('Location: login.php');
exit;
