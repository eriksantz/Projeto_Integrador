<?php
session_start();
require_once '../conexao.php';

if (!isset($_SESSION['usuario_id']) || $_SESSION['tipo_usuario'] !== 'Gestor') {
    header('Location: ../login.php');
    exit;
}

$id = intval($_GET['id'] ?? 0);
$gestor_id_logado = $_SESSION['usuario_id'];

$sql = $pdo->prepare("SELECT imagem, cliente_id FROM postagens WHERE id = ? AND gestor_id = ?");
$sql->execute([$id, $gestor_id_logado]);
$postagem = $sql->fetch();

if ($postagem) {
    if ($postagem['imagem'] && file_exists('../uploads/' . $postagem['imagem'])) {
        unlink('../uploads/' . $postagem['imagem']);
    }

    $delete = $pdo->prepare("DELETE FROM postagens WHERE id = ?");
    $delete->execute([$id]);

    header("Location: postagens_cliente.php?cliente_id=" . $postagem['cliente_id']);
    exit;
}

header('Location: painel_gestor.php');
exit;
?>
