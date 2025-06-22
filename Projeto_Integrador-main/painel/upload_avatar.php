<?php
session_start();

// Garante que apenas um usuário logado possa acessar
if (!isset($_SESSION['usuario_id'])) {
    header('Location: ../login.php');
    exit;
}

require_once '../conexao.php';

// Verifica se um arquivo foi enviado e se não há erros
if (isset($_FILES['avatar_file']) && $_FILES['avatar_file']['error'] === UPLOAD_ERR_OK) {

    $file = $_FILES['avatar_file'];

    // 1. VALIDAÇÃO DE SEGURANÇA
    // Limite de tamanho (ex: 2MB)
    $max_size = 2 * 1024 * 1024; 
    if ($file['size'] > $max_size) {
        die("Erro: O arquivo é muito grande. O limite é de 2MB.");
    }

    // Valida o tipo de arquivo (MIME type) para garantir que é uma imagem
    $allowed_types = ['image/jpeg', 'image/png', 'image/gif'];
    $file_type = mime_content_type($file['tmp_name']);
    if (!in_array($file_type, $allowed_types)) {
        die("Erro: Tipo de arquivo inválido. Apenas JPG, PNG e GIF são permitidos.");
    }

    // 2. PREPARAÇÃO DO NOVO NOME E CAMINHO
    $usuario_id = $_SESSION['usuario_id'];
    $tipo_usuario = strtolower($_SESSION['tipo_usuario']);
    $tabela = $tipo_usuario === 'gestor' ? 'gestores' : 'clientes';

    // Pega a extensão do arquivo
    $extension = pathinfo($file['name'], PATHINFO_EXTENSION);
    
    // Cria um nome de arquivo único para evitar conflitos
    $novo_nome_arquivo = $tipo_usuario . '_' . $usuario_id . '_' . time() . '.' . $extension;

    // Define o diretório de destino. Certifique-se de que esta pasta exista!
    $diretorio_destino = '../uploads/avatars/';
    $caminho_completo = $diretorio_destino . $novo_nome_arquivo;

    // 3. SALVAMENTO E ATUALIZAÇÃO DO BANCO
    
    // Antes de salvar o novo, verifica se há um antigo para deletar
    $stmt = $pdo->prepare("SELECT foto_perfil FROM $tabela WHERE id = ?");
    $stmt->execute([$usuario_id]);
    $resultado = $stmt->fetch();
    if ($resultado && !empty($resultado['foto_perfil']) && file_exists($resultado['foto_perfil'])) {
        unlink($resultado['foto_perfil']); // Deleta o arquivo antigo do servidor
    }

    // Move o arquivo enviado para o diretório de destino
    if (move_uploaded_file($file['tmp_name'], $caminho_completo)) {
        // Se moveu com sucesso, atualiza o caminho no banco de dados
        $stmt_update = $pdo->prepare("UPDATE $tabela SET foto_perfil = ? WHERE id = ?");
        $stmt_update->execute([$caminho_completo, $usuario_id]);

        // Redireciona de volta para o perfil com uma mensagem de sucesso
        header('Location: perfil.php?upload=sucesso');
        exit;
    } else {
        die("Erro ao salvar o arquivo.");
    }
} else {
    // Redireciona com erro se nenhum arquivo foi enviado ou se houve erro no upload
    header('Location: perfil.php?upload=erro');
    exit;
}
?>