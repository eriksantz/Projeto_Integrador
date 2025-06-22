<?php

session_start();
require_once '../conexao.php';


if (!isset($_GET['id']) || !isset($_GET['tipo'])) {
    http_response_code(400);
    exit('Parâmetros inválidos.');
}

$usuario_id = filter_var($_GET['id'], FILTER_VALIDATE_INT);
$tipo_usuario = filter_var($_GET['tipo'], FILTER_SANITIZE_STRING);

if (!$usuario_id || !in_array($tipo_usuario, ['gestor', 'cliente'])) {
    http_response_code(400);
    exit('Parâmetros inválidos.');
}


$tabela = $tipo_usuario === 'gestor' ? 'gestores' : 'clientes';


$stmt = $pdo->prepare("SELECT nome, foto_perfil FROM $tabela WHERE id = ?");
$stmt->execute([$usuario_id]);
$usuario = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$usuario) {
    http_response_code(404); // Not Found
    exit('Usuário não encontrado.');
}

// Verifica se o usuário tem uma foto de perfil definida
if (!empty($usuario['foto_perfil']) && file_exists($usuario['foto_perfil'])) {
    
    // -- OPÇÃO 1: O usuário TEM uma foto --
    $caminho_foto = $usuario['foto_perfil'];
    $info = getimagesize($caminho_foto);
    $mime = $info['mime'];

    // Define o cabeçalho HTTP para o tipo de imagem correto
    header("Content-Type: $mime");
    
    // Envia o conteúdo do arquivo da imagem para o navegador
    readfile($caminho_foto);

} else {

    
    // -- OPÇÃO 2: O usuário NÃO TEM foto, vamos gerar uma com as iniciais --
    
    // Função para obter as iniciais (sem alteração)
    function getInitials($name) {
        $words = explode(' ', trim($name));
        $initials = '';
        if (isset($words[0])) $initials .= mb_substr($words[0], 0, 1);
        if (count($words) > 1) $initials .= mb_substr($words[count($words) - 1], 0, 1);
        return strtoupper($initials);
    }

    $iniciais = getInitials($usuario['nome']);
    
    header("Content-Type: image/png");
    $imagem = imagecreatetruecolor(100, 100);
    $cor_fundo = imagecolorallocate($imagem, 132, 58, 243); 
    $cor_texto = imagecolorallocate($imagem, 255, 255, 255); 
    imagefill($imagem, 0, 0, $cor_fundo);

    $caminho_fonte = __DIR__ . '/../font/PlusJakartaSans-Bold.ttf'; // Usando o caminho mais robusto
    $tamanho_fonte = 30;
    
    // 1. Calcula as dimensões da "caixa" que envolve o texto
    $bbox = imagettfbbox($tamanho_fonte, 0, $caminho_fonte, $iniciais);
    
    // 2. Calcula a largura e altura exatas do texto
    $largura_texto = $bbox[2] - $bbox[0];
    $altura_texto = $bbox[1] - $bbox[7];

    // 3. Calcula as coordenadas X e Y para centralizar
    // X: (largura da imagem - largura do texto) / 2
    $x = (100 - $largura_texto) / 2;
    // Y: (altura da imagem - altura do texto) / 2 + altura do texto
    $y = (100 - $altura_texto) / 2 + $altura_texto;

    // Desenha o texto na imagem com as novas coordenadas
    imagettftext($imagem, $tamanho_fonte, 0, $x, $y, $cor_texto, $caminho_fonte, $iniciais);

    imagepng($imagem);
    imagedestroy($imagem);
}

exit;
?>