<?php
session_start();

if (!isset($_SESSION['usuario_id']) || $_SESSION['tipo_usuario'] !== 'Gestor') {
    header('Location: ../login.html');
    exit;
}

require_once '../conexao.php';

$cliente_id = $_POST['cliente_id'];
$titulo = $_POST['titulo'];
$descricao = $_POST['descricao'];
$data_publicacao = $_POST['data_publicacao'];
$redes_sociais = $_POST['redes_sociais'];

$imagem = null;
if (isset($_FILES['imagem']) && $_FILES['imagem']['error'] === UPLOAD_ERR_OK) {
    $ext = pathinfo($_FILES['imagem']['name'], PATHINFO_EXTENSION);
    $nome_arquivo = uniqid() . '.' . $ext;
    move_uploaded_file($_FILES['imagem']['tmp_name'], '../uploads/' . $nome_arquivo);
    $imagem = $nome_arquivo;
}

$gestor_id = $_SESSION['usuario_id'];

$sql = "INSERT INTO postagens (cliente_id, gestor_id, titulo, descricao, data_publicacao, redes_sociais, imagem) 
        VALUES (?, ?, ?, ?, ?, ?, ?)";

$stmt = $pdo->prepare($sql);
$stmt->execute([$cliente_id, $gestor_id, $titulo, $descricao, $data_publicacao, $redes_sociais, $imagem]);

header('Location: painel_gestor.php');
exit;
?>
