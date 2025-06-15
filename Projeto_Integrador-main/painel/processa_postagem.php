<?php
session_start();

if (!isset($_SESSION['usuario_id']) || $_SESSION['tipo_usuario'] !== 'Gestor') {
    header('Location: ../login.html');
    exit;
}

require_once '../conexao.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $cliente_id = intval($_POST['cliente_id']);
    $titulo = trim($_POST['titulo']);
    $descricao = trim($_POST['descricao']);
    $data_publicacao = !empty($_POST['data_publicacao']) ? $_POST['data_publicacao'] : null;
    $redes_sociais = trim($_POST['redes_sociais']);
    $imagem = null;

    // Processamento do upload da imagem
    if (isset($_FILES['imagem']) && $_FILES['imagem']['error'] === UPLOAD_ERR_OK) {
        $nome_arquivo = $_FILES['imagem']['name'];
        $tmp = $_FILES['imagem']['tmp_name'];
        $extensao = strtolower(pathinfo($nome_arquivo, PATHINFO_EXTENSION));

        $permitidas = ['jpg', 'jpeg', 'png', 'gif'];
        if (in_array($extensao, $permitidas)) {
            $novo_nome = uniqid() . '.' . $extensao;
            $destino = '../uploads/' . $novo_nome;

            if (!is_dir('../uploads')) {
                mkdir('../uploads', 0755, true);
            }

            if (move_uploaded_file($tmp, $destino)) {
                $imagem = $novo_nome;
            }
        }
    }

    // Inserção no banco
    $sql = "INSERT INTO postagens (cliente_id, titulo, descricao, imagem, data_publicacao, redes_sociais) 
            VALUES (?, ?, ?, ?, ?, ?)";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([
        $cliente_id,
        $titulo,
        $descricao,
        $imagem,
        $data_publicacao,
        $redes_sociais
    ]);

    header("Location: postagens_cliente.php?cliente_id=" . $cliente_id);
    exit;
} else {
    header('Location: painel_gestor.php');
    exit;
}
?>
