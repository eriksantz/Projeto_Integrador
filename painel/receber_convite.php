<?php
session_start();

if (!isset($_SESSION['usuario_id']) || strtolower($_SESSION['tipo_usuario']) !== 'cliente') {

    header('Location: ../login.html');
    exit;
}

require '../conexao.php';

$mensagem = '';
$usuario_id = $_SESSION['usuario_id'];

// Verifica se o cliente já tem convite vinculado
$sql_verifica = "SELECT convites.codigo, convites.data_criacao 
                 FROM clientes 
                 JOIN convites ON clientes.convite_id = convites.id
                 WHERE clientes.id = ? AND clientes.convite_id IS NOT NULL";
$stmt_verifica = $pdo->prepare($sql_verifica);
$stmt_verifica->execute([$usuario_id]);
$convite_vinculado = $stmt_verifica->fetch();

// Caso o cliente envie o código
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['codigo'])) {
    $codigo = trim($_POST['codigo']);

    // Verificar se o código existe e não foi usado
    $sql = "SELECT id, usado FROM convites WHERE codigo = ?";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$codigo]);
    $convite = $stmt->fetch();

    if ($convite) {
        if ($convite_vinculado) {
            $mensagem = '<span style="color: orange;">Você já possui um convite vinculado.</span>';
        } elseif ($convite['usado']) {
            $mensagem = '<span style="color: red;">Este convite já foi utilizado.</span>';
        } else {
            // Vincular convite ao cliente
            $sql = "UPDATE clientes SET convite_id = ? WHERE id = ?";
            $stmt = $pdo->prepare($sql);
            $stmt->execute([$convite['id'], $usuario_id]);

            // Marcar convite como usado
            $sql = "UPDATE convites SET usado = 1 WHERE id = ?";
            $stmt = $pdo->prepare($sql);
            $stmt->execute([$convite['id']]);

            $mensagem = '<span style="color: green;">Convite vinculado com sucesso!</span>';

            // Atualizar variável para exibir abaixo
            $stmt_verifica->execute([$usuario_id]);
            $convite_vinculado = $stmt_verifica->fetch();
        }
    } else {
        $mensagem = '<span style="color: red;">Código de convite inválido.</span>';
    }
}

$nome = $_SESSION['nome'];
?>

<!DOCTYPE html>
<html lang="pt-br">

<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Vincular Convite</title>
    <link rel="icon" type="image/png" sizes="16x16" href="../imagens/fav_icon.png">
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;600&display=swap" rel="stylesheet" />
    <link href="https://fonts.googleapis.com/css2?family=Material+Symbols+Rounded" rel="stylesheet" />
    <link rel="stylesheet" href="receber_convite.css">
</head>

<body>
    <div class="container">
        <aside class="sidebar expanded" id="sidebar">
            <div class="logo">
                <img src="../imagens/logo_GAD_PAINEL.png" alt="Logo" style="width: 80%; height: auto;" />
            </div>
            <nav class="menu-wrapper">
                <div class="menu">
                    <a href="painel_cliente.php" class="menu-item"><span class="material-symbols-rounded">space_dashboard</span><span class="text">Visão Geral</span></a>
                    <a href="receber_convite.php" class="menu-item active"><span class="material-symbols-rounded">forward_to_inbox</span><span class="text">Convites</span></a>
                    <a href="#" class="menu-item"><span class="material-symbols-rounded">person</span><span class="text">Perfil</span></a>
                    <a href="#" class="menu-item"><span class="material-symbols-rounded">settings</span><span class="text">Configurações</span></a>
                    <a href="#" class="menu-item"><span class="material-symbols-rounded">help</span><span class="text">Ajuda</span></a>
                </div>
                <div style="margin-top: auto; width: 100%;">
                    <a href="logout.php" class="menu-item logout">
                        <span class="material-symbols-rounded">exit_to_app</span>
                        <span class="text">Sair</span>
                    </a>
                </div>
            </nav>
        </aside>

        <main class="main-content">
            <header class="header">
                <button id="toggleBtn" class="toggle-btn"><span class="material-symbols-rounded" id="iconToggle">chevron_left</span></button>
                <h1>Vincular Convite</h1>
                <div class="user-info"><span><?= htmlspecialchars($nome) ?></span>
                    <div class="avatar"></div>
                </div>
            </header>

            <section class="modal-section">
                <span class="material-symbols-rounded">email</span>
                <?php if ($mensagem): ?>
                    <p class="success-message"><?= $mensagem ?></p>
                <?php endif; ?>
                <?php if (!$convite_vinculado): ?>
                    <div class="button-container" style="flex-direction: column; gap: 1rem;">
                        <form method="POST" action="">
                            <input type="text" name="codigo" placeholder="Digite o código de convite" required style="padding: 0.8rem 1rem; border-radius: 0.8rem; border: 1px solid #ccc; width: 300px;">
                            <button type="submit">Vincular Convite</button>
                        </form>
                    </div>
                <?php else: ?>
                    <p><strong>Seu convite vinculado:</strong> <?= htmlspecialchars($convite_vinculado['codigo']) ?></p>
                    <p><strong>Data de Criação:</strong> <?= date('d/m/Y H:i', strtotime($convite_vinculado['data_criacao'])) ?></p>
                <?php endif; ?>
            </section>
        </main>
    </div>

    <script>
    const toggleBtn = document.getElementById('toggleBtn');
    const sidebar = document.getElementById('sidebar');
    const iconToggle = document.getElementById('iconToggle');

    function updateIcon() {
        iconToggle.textContent = sidebar.classList.contains('collapsed') ? 'menu' : 'chevron_left';
    }

    const isCollapsed = localStorage.getItem('sidebarCollapsed') === 'true';
    if (isCollapsed) {
        sidebar.classList.remove('expanded');
        sidebar.classList.add('collapsed');
    } else {
        sidebar.classList.remove('collapsed');
        sidebar.classList.add('expanded');
    }

    updateIcon();

    toggleBtn.addEventListener('click', () => {
        sidebar.classList.toggle('collapsed');
        sidebar.classList.toggle('expanded');

        const currentlyCollapsed = sidebar.classList.contains('collapsed');
        localStorage.setItem('sidebarCollapsed', currentlyCollapsed);
        updateIcon();
    });
    </script>
</body>

</html>
