<?php
session_start();

// 1. CONTROLE DE ACESSO UNIFICADO
// Permite o acesso se o usuário estiver logado E for 'gestor' OU 'cliente'.
if (!isset($_SESSION['usuario_id']) || !in_array(strtolower($_SESSION['tipo_usuario']), ['gestor', 'cliente'])) {
    header('Location: ../login.php');
    exit;
}

require_once '../conexao.php';

// Armazena as informações da sessão em variáveis para facilitar o uso
$usuario_id = $_SESSION['usuario_id'];
$tipo_usuario = strtolower($_SESSION['tipo_usuario']);
$nome = $_SESSION['nome'];
$email = ''; // Será preenchido pela consulta ao BD
$dados_especificos = []; // Array para guardar os dados específicos de cada perfil

// 2. BUSCA DE DADOS (LÓGICA CONDICIONAL)
if ($tipo_usuario === 'gestor') {
    // Busca dados do GESTOR
    $stmt = $pdo->prepare("SELECT email, cnpj FROM gestores WHERE id = ?");
    $stmt->execute([$usuario_id]);
    $gestor_info = $stmt->fetch(PDO::FETCH_ASSOC);
    $email = $gestor_info['email'] ?? '';
    $dados_especificos['cnpj'] = $gestor_info['cnpj'] ?? 'Não informado';

    // Busca a lista de clientes vinculados ao gestor
    $stmt_clientes = $pdo->prepare("
        SELECT c.nome, c.email 
        FROM clientes c 
        JOIN convites co ON c.convite_id = co.id 
        WHERE co.gestor_id = ?
        ORDER BY c.nome
    ");
    $stmt_clientes->execute([$usuario_id]);
    $dados_especificos['clientes'] = $stmt_clientes->fetchAll(PDO::FETCH_ASSOC);
} else { // tipo_usuario === 'cliente'
    // Busca dados do CLIENTE e do seu gestor vinculado em uma única consulta
    $stmt = $pdo->prepare("
        SELECT 
            c.email, c.data_vinculacao,
            g.nome AS nome_gestor,
            g.email AS email_gestor,
            co.codigo AS codigo_convite
        FROM clientes c
        LEFT JOIN convites co ON c.convite_id = co.id
        LEFT JOIN gestores g ON co.gestor_id = g.id
        WHERE c.id = ?
    ");
    $stmt->execute([$usuario_id]);
    $cliente_info = $stmt->fetch(PDO::FETCH_ASSOC);

    $email = $cliente_info['email'] ?? '';
    if ($cliente_info && $cliente_info['nome_gestor']) {
        $dados_especificos = $cliente_info;
    } else {
        $dados_especificos = null; // Cliente não vinculado
    }
}

?>

<!DOCTYPE html>
<html lang="pt-br">

<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Perfil</title>
    <link rel="icon" type="image/png" sizes="16x16" href="../imagens/fav_icon.png">
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;600&display=swap" rel="stylesheet" />
    <link href="https://fonts.googleapis.com/css2?family=Material+Symbols+Rounded" rel="stylesheet" />
    <link rel="stylesheet" href="painel_gestor.css">
    <link rel="stylesheet" href="sidebar.css">
    <link rel="stylesheet" href="estilos_diversos/profile.css">
    <link rel="stylesheet" href="responsividade.css">
</head>

<body>
    <div class="container">
        <aside class="sidebar expanded" id="sidebar">
            <div class="logo">
                <img src="../imagens/logo_GAD_PAINEL.png" alt="Logo" style="width: 80%; height: auto;" />
            </div>
            <nav class="menu-wrapper">
                <div class="menu">
                    <?php if ($tipo_usuario === 'gestor'): ?>
                        <a href="painel_gestor.php" class="menu-item"><span class="material-symbols-rounded">space_dashboard</span><span class="text">Visão Geral</span></a>
                        <a href="criar_convite.php" class="menu-item"><span class="material-symbols-rounded">forward_to_inbox</span><span class="text">Convites</span></a>
                    <?php else: ?>
                        <a href="painel_cliente.php" class="menu-item"><span class="material-symbols-rounded">space_dashboard</span><span class="text">Postagens</span></a>
                        <a href="receber_convite.php" class="menu-item"><span class="material-symbols-rounded">forward_to_inbox</span><span class="text">Convites</span></a>
                    <?php endif; ?>

                    <a href="perfil.php" class="menu-item active"><span class="material-symbols-rounded">Person</span><span class="text">Perfil</span></a>
                    <a href="configuracoes.php" class="menu-item"><span class="material-symbols-rounded">settings</span><span class="text">Configurações</span></a>
                    
                </div>
                <div style="margin-top: auto; width: 100%;">
                    <a href="logout.php" class="menu-item logout">
                        <span class="material-symbols-rounded">exit_to_app</span>
                        <span class="text">Sair</span>
                    </a>
                </div>
            </nav>
        </aside>

        <div class="sidebar-overlay" id="sidebarOverlay"></div>
        <main class="main-content">
            <header class="header">
                <button id="toggleBtn" class="toggle-btn">
                    <span class="material-symbols-rounded" id="iconToggle">chevron_left</span>
                </button>
                <h1>Perfil</h1>
                <div class="user-info">
                    <span><?= htmlspecialchars($nome) ?></span>
                    <img
                        src="../avatar/gerar_avatar.php?id=<?= $usuario_id ?>&tipo=<?= $tipo_usuario ?>"
                        alt="Avatar de <?= htmlspecialchars($nome) ?>"
                        class="avatar">
                </div>
            </header>

            <div class="profile-container">
                <div class="profile-card">
                    <div class="profile-header">
                        <a href="javascript:void(0);" onclick="abrirModal()" title="Trocar Foto do Perfil" class="avatar-link">
                            <img
                                src="../avatar/gerar_avatar.php?id=<?= $usuario_id ?>&tipo=<?= $tipo_usuario ?>"
                                alt="Avatar de <?= htmlspecialchars($nome) ?>"
                                class="profile-avatar">
                        </a>
                        <div class="profile-info">
                            <h2><?= htmlspecialchars($nome) ?></h2>
                            <p><?= htmlspecialchars($email) ?></p>
                            <span class="badge"><?= ucfirst($tipo_usuario) ?></span>
                        </div>
                    </div>

                    <div class="profile-details">
                        <?php if ($tipo_usuario === 'gestor'): ?>
                            <h3>Minha Carteira de Clientes</h3>
                            <?php if (!empty($dados_especificos['clientes'])): ?>
                                <p>Você possui <strong><?= count($dados_especificos['clientes']) ?></strong> cliente(s) vinculado(s):</p>
                                <ul class="client-list">
                                    <?php foreach ($dados_especificos['clientes'] as $cliente): ?>
                                        <li>
                                            <span class="name"> <?= htmlspecialchars($cliente['nome']) ?></span>
                                            <span class="email"><?= htmlspecialchars($cliente['email']) ?></span>
                                        </li>
                                    <?php endforeach; ?>
                                </ul>
                            <?php else: ?>
                                <p>Você ainda não possui clientes vinculados.</p>
                            <?php endif; ?>
                            <br>
                            <h3>Informações Comerciais</h3>
                            <p><strong>CNPJ:</strong> <?= !empty($dados_especificos['cnpj']) ? htmlspecialchars($dados_especificos['cnpj']) : 'Não informado' ?></p>

                        <?php else:
                        ?>
                            <h3>Meu Vínculo</h3>
                            <?php if ($dados_especificos): ?>
                                <div class="details-grid">
                                    <div class="detail-item">
                                        <p><strong>Gestor:</strong> <?= htmlspecialchars($dados_especificos['nome_gestor']) ?></p>
                                        <p><strong>Contato do Gestor:</strong> <?= htmlspecialchars($dados_especificos['email_gestor']) ?></p>
                                    </div>
                                    <div class="detail-item">
                                        <p><strong>Código Utilizado:</strong> <?= htmlspecialchars($dados_especificos['codigo_convite']) ?></p>
                                        <p><strong>Data da Vinculação:</strong> <?= date('d/m/Y \à\s H:i', strtotime($dados_especificos['data_vinculacao'])) ?></p>
                                    </div>
                                </div>
                            <?php else: ?>
                                <p>Você ainda não está vinculado a um gestor. Vá para a página de "Convites" para se vincular.</p>
                            <?php endif; ?>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </main>
    </div>


<div id="avatarUploadModal" class="upload-modal-overlay">
    <div class="upload-modal-content">
        <button class="upload-modal-fechar" onclick="fecharModal()">
            <span class="material-symbols-rounded">close</span>
        </button>
        
        <div class="upload-modal-header">
            <div class="icon-wrapper">
                <span class="material-symbols-rounded">upload_file</span>
            </div>
            <h2>Alterar Foto do Perfil</h2>
            <p>Escolha uma imagem (JPG, PNG) de até 2MB.</p>
        </div>
        
        <form action="upload_avatar.php" method="POST" enctype="multipart/form-data">
            <input type="file" name="avatar_file" required accept="image/jpeg, image/png">
            
            <div class="upload-modal-botoes">
                <button type="button" class="upload-modal-btn-secundario" onclick="fecharModal()">Cancelar</button>
                <button type="submit" class="upload-modal-btn-primario">Salvar Foto</button>
            </div>
        </form>
    </div>
</div>
    <script src="sidebar.js"></script>                              
    <script>
        
    

    const modal = document.getElementById('avatarUploadModal');

function abrirModal() {
    modal.classList.add('visible');
}

function fecharModal() {
    modal.classList.remove('visible');
}

// Opcional: fechar o modal clicando fora dele
modal.addEventListener('click', function(event) {
    if (event.target === modal) {
        fecharModal();
    }
});
    </script>
</body>

</html>