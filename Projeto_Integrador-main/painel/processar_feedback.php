<?php
session_start();
require '../conexao.php';

// 1. Validação de Segurança Básica
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    // Se não for um POST, não faz nada
    header('Location: painel_cliente.php');
    exit;
}

if (!isset($_SESSION['usuario_id']) || $_SESSION['tipo_usuario'] !== 'Cliente') {
    // Se não for um cliente logado, volta pro login
    header('Location: ../login.php');
    exit;
}

// 2. Coleta e Validação dos Dados do Formulário
if (!isset($_POST['postagem_id'], $_POST['acao'])) {
    // Se faltar informação essencial, volta pro painel
    header('Location: painel_cliente.php');
    exit;
}

$cliente_id_logado = $_SESSION['usuario_id'];
$postagem_id = intval($_POST['postagem_id']);
$novo_status = $_POST['acao']; // 'Aprovado', 'Revisar' ou 'Reprovado'
$feedback_texto = trim($_POST['feedback_texto']);

// Valida se a ação é uma das permitidas
$acoes_permitidas = ['Aprovado', 'Revisar', 'Reprovado'];
if (!in_array($novo_status, $acoes_permitidas)) {
    // Ação inválida
    header('Location: painel_cliente.php');
    exit;
}

// 3. Validação de Dono: O cliente só pode alterar a própria postagem
try {
    $sql_valida = "SELECT id FROM postagens WHERE id = ? AND cliente_id = ?";
    $stmt_valida = $pdo->prepare($sql_valida);
    $stmt_valida->execute([$postagem_id, $cliente_id_logado]);

    if ($stmt_valida->rowCount() === 0) {
        // Tentativa de alterar postagem de outro cliente!
        // Apenas redirecionamos para o painel sem dar erro, por segurança.
        header('Location: painel_cliente.php');
        exit;
    }

    // 4. Se tudo estiver OK, atualiza o Banco de Dados
    $sql_update = "UPDATE postagens SET status = ?, feedback_cliente = ? WHERE id = ?";
    $stmt_update = $pdo->prepare($sql_update);
    $stmt_update->execute([$novo_status, $feedback_texto, $postagem_id]);

    // 5. Redireciona o cliente de volta para o painel, onde ele verá o status atualizado
    header('Location: painel_cliente.php');
    exit;

} catch (PDOException $e) {

    header('Location: painel_cliente.php');
    exit;
}
?>