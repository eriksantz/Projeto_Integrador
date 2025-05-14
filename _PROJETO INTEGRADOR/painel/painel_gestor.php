<?php
session_start();

if (!isset($_SESSION['usuario_id']) || $_SESSION['tipo_usuario'] !== 'Gestor') {
    header('Location: ../login.html');
    exit;
}

$nome = $_SESSION['nome'];
?>

<!DOCTYPE html>
<html lang="pt-br">

<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Painel do Gestor</title>
    <link rel="icon" type="image/png" sizes="16x16" href="../imagens/fav_icon.png">
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;600&display=swap" rel="stylesheet" />
    <link href="https://fonts.googleapis.com/css2?family=Material+Symbols+Rounded" rel="stylesheet" />
    <style>
               * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Plus Jakarta Sans', sans-serif;
            background: #f5f6fa;
            color: #333;
        }

        .container {
            display: flex;
            min-height: 100vh;
        }

        .sidebar {
            background: #843af3;
            color: white;
            position: fixed;
            top: 0;
            left: 0;
            bottom: 0;
            width: 250px;
            padding: 1.5rem 0.5rem;
            display: flex;
            flex-direction: column;
            align-items: center;
            border-top-right-radius: 2rem;
            border-bottom-right-radius: 2rem;
            transition: width 0.3s ease;
            z-index: 10;
        }

        .sidebar.expanded {
            width: 250px;
            align-items: flex-start;
            padding-left: 1.5rem;
        }

        .sidebar.collapsed {
            width: 70px;
        }

        .logo {
            font-size: 1.8rem;
            font-weight: bold;
            margin-bottom: 2rem;
            transition: opacity 0.3s ease;
        }

        .sidebar.collapsed .logo {
            opacity: 0;
        }

        .sidebar.collapsed~.main-content {
            margin-left: 70px;
        }

        .menu-wrapper {
            display: flex;
            flex-direction: column;
            height: 100%;
            width: 100%;
        }

        .menu {
            display: flex;
            flex-direction: column;
            gap: 1rem;
        }

        .menu-item {
            display: flex;
            align-items: center;
            gap: 1rem;
            padding: 0.8rem 1rem;
            border-radius: 1rem;
            color: white;
            text-decoration: none;
            transition: background 0.2s, color 0.2s;
        }

        .menu-item:hover {
            background: rgba(255, 255, 255, 0.2);
        }

        .menu-item.active {
            background: white;
            color: #843af3;
            font-weight: 600;
        }

        .menu-item:not(.logout) {
            flex-grow: 1;
        }

        .menu-item.logout {
            padding-bottom: 0.7rem;
        }

        .material-symbols-rounded {
            font-size: 2rem;
            display: flex;
            align-items: center;
        }

        .text {
            transition: opacity 0.2s;
        }

        .sidebar.collapsed .text {
            opacity: 0;
            width: 0;
            overflow: hidden;
        }

        .main-content {
            flex: 1;
            display: flex;
            flex-direction: column;
            padding: 2rem;
            margin-left: 250px;
        }

        .header {
            display: flex;
            align-items: center;
            background: white;
            padding: 1rem 2rem;
            border-radius: 1rem;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.05);
            margin-bottom: 2rem;
        }

        .header h1 {
            flex: 1;
        }

        .header input {
            padding: 0.5rem 1rem;
            border-radius: 0.75rem;
            border: 1px solid #ccc;
            width: 300px;
            margin: 0 1rem;
        }

        .user-info {
            display: flex;
            align-items: center;
            gap: 1rem;
        }

        .avatar {
            width: 40px;
            height: 40px;
            background: #ccc;
            border-radius: 50%;
        }

        .toggle-btn {
            background: none;
            border: none;
            cursor: pointer;
            margin-right: 1rem;
            font-size: 1.5rem;
            z-index: 10;
            color: #843af3;
        }

        .menu-item .text {
            letter-spacing: 0.001em;
        }

        .clientes-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 1rem;
        }

        .cliente-card {
            background: white;
            padding: 1rem;
            border-radius: 1rem;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.05);
            cursor: pointer;
            transition: transform 0.2s;
        }

        .cliente-card:hover {
            transform: scale(1.02);
        }

        .sidebar.collapsed .menu-item {
            justify-content: center;
            padding-left: 0;
            padding-right: 0;
            gap: 0;
        }

        .sidebar.expanded .menu-item {
            justify-content: flex-start;
        }

        .modal-section {
            background: #fff;
            padding: 2rem;
            border-radius: 1rem;
            box-shadow: 0 4px 8px rgba(0, 0, 0, .1);
            display: flex;
            flex-direction: column;
            align-items: center;
            margin-top: 2rem
        }

        .modal-section .material-symbols-rounded {
            font-size: 3rem;
            color: #843af3;
            margin-bottom: 1.5rem
        }

        .modal-section .button-container {
            display: flex;
            justify-content: center;
            width: 100%
        }

        .modal-section button {
            padding: 1rem 2.5rem;
            font-size: 1rem;
            background: #843af3;
            color: #fff;
            border: none;
            border-radius: .8rem;
            cursor: pointer;
            transition: transform .15s
        }

        .modal-section button:hover {
            transform: scale(1.04)
        }

        .success-message {
            color: green;
            font-weight: 600;
            margin-top: 1rem
        }

        .convites-section {
            margin-top: 2rem;
        }

        .convites-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 1rem;
        }

        .convite-card {
            background: white;
            padding: 1rem;
            border-radius: 1rem;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.05);
            display: flex;
            flex-direction: column;
            justify-content: space-between;
        }

        .excluir-btn {
            text-align: center;
            margin-top: 1rem;
            padding: 0.5rem 1rem;
            background-color: #ff3b3b;
            color: white;
            text-decoration: none;
            border-radius: 0.8rem;
            transition: background 0.3s;
        }

        .excluir-btn:hover {
            background-color: #d32f2f;
        }
    </style>
</head>

<body>
    <div class="container">
        <aside class="sidebar expanded" id="sidebar">
            <div class="logo">
                <img src="../imagens/logo_GAD_PAINEL.png" alt="Logo" style="width: 80%; height: auto;" />
            </div>
            <nav class="menu-wrapper">
                <div class="menu">
                    <a href="painel_gestor.php" class="menu-item active"><span class="material-symbols-rounded">space_dashboard</span><span class="text">Visão Geral</span></a>
                    <a href="criar_convite.php" class="menu-item"><span class="material-symbols-rounded">forward_to_inbox</span><span class="text">Convites</span></a>
                    <a href="#" class="menu-item"><span class="material-symbols-rounded">Person</span><span class="text">Perfil</span></a>
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
                <h1>Visão Geral</h1>
                <div class="user-info">
                    <span><?= htmlspecialchars($nome) ?></span>
                    <div class="avatar"></div>
                </div>
            </header>

            <section class="clientes-grid">
                <?php
                // Simulação: array de clientes. Mais tarde isso virá do banco.
                $clientes = []; // Suponha que você buscou do banco e deu vazio

                if (empty($clientes)) {
                    echo '
        <div style="text-align: center; width: 100%; padding: 2rem;">
            <img src="../imagens/sem_cliente_feedback.png" alt="Sem clientes" style="width: 200px; opacity: 0.6;" />
            <p style="margin-top: 1rem; font-size: 1.1rem; color: #666;">
                Você ainda não vinculou nenhum cliente.<br>Crie um convite para começar.
            </p>
        </div>';
                } else {
                    foreach ($clientes as $cliente) {
                        echo '
            <div class="cliente-card" onclick="abrirPostagem(' . $cliente['id'] . ')">
                <h3>Cliente: ' . htmlspecialchars($cliente['nome']) . '</h3>
                <p>Última postagem: ' . $cliente['ultima_postagem'] . '</p>
                <p>Status: ' . $cliente['status'] . '</p>
            </div>';
                    }
                }
                ?>
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