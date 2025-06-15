<?php
session_start();

if (!isset($_SESSION['usuario_id']) || $_SESSION['tipo_usuario'] !== 'Gestor') {
    header('Location: ../login.html');
    exit;
}

require_once '../conexao.php';

$nome = $_SESSION['nome'];
$usuario_id = $_SESSION['usuario_id'];
$tipo_usuario = strtolower($_SESSION['tipo_usuario']);
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
    <link rel="stylesheet" href="profile.css">
</head>

<body>
    <div class="container">
        <aside class="sidebar expanded" id="sidebar">
            <div class="logo">
                <img src="../imagens/logo_GAD_PAINEL.png" alt="Logo" style="width: 80%; height: auto;" />
            </div>
            <nav class="menu-wrapper">
                <div class="menu">
                    <a href="painel_gestor.php" class="menu-item"><span class="material-symbols-rounded">space_dashboard</span><span class="text">Visão Geral</span></a>
                    <a href="criar_convite.php" class="menu-item"><span class="material-symbols-rounded">forward_to_inbox</span><span class="text">Convites</span></a>
                    <a href="perfil.php" class="menu-item active"><span class="material-symbols-rounded">Person</span><span class="text">Perfil</span></a>
                    <a href="#" class="menu-item"><span class="material-symbols-rounded">settings</span><span class="text">Configurações</span></a>
                    <a href="#" class="menu-item"><span class="material-symbols-rounded">group</span><span class="text">Usuários</span></a>
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