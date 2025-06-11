<?php
session_start();
if (!isset($_SESSION['usuario_id']) || $_SESSION['tipo_usuario'] !== 'Gestor') {
    header('Location: ../login.html');
    exit;
}

require '../conexao.php';

$mensagem = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $gestor_id = $_SESSION['usuario_id'];
    $codigo = bin2hex(random_bytes(5));
    $sql = "INSERT INTO convites (gestor_id, codigo) VALUES (?, ?)";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$gestor_id, $codigo]);
    $mensagem = "Convite criado com sucesso! Código: <strong>$codigo</strong>";
}

if (isset($_GET['excluir'])) {
    $convite_id = $_GET['excluir'];
    $sql = "DELETE FROM convites WHERE id = ? AND gestor_id = ?";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$convite_id, $_SESSION['usuario_id']]);
    header('Location: criar_convite.php');
    exit;
}

$gestor_id = $_SESSION['usuario_id'];
$sql = "SELECT id, codigo, data_criacao FROM convites WHERE gestor_id = ?";
$stmt = $pdo->prepare($sql);
$stmt->execute([$gestor_id]);
$convites = $stmt->fetchAll();

$nome = $_SESSION['nome'];
?>
<!DOCTYPE html>
<html lang="pt-br">

<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Criar Convite</title>
    <link rel="icon" type="image/png" sizes="16x16" href="../imagens/fav_icon.png">
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;600&display=swap" rel="stylesheet" />
    <link href="https://fonts.googleapis.com/css2?family=Material+Symbols+Rounded" rel="stylesheet" />
    <link rel="stylesheet" href="criar_convite.css">
    <link rel="stylesheet" href="painel_gestor.css">
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
                    <a href="criar_convite.php" class="menu-item active"><span class="material-symbols-rounded">forward_to_inbox</span><span class="text">Convites</span></a>
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
                <button id="toggleBtn" class="toggle-btn"><span class="material-symbols-rounded" id="iconToggle">chevron_left</span></button>
                <h1>Convites</h1>
                <div class="user-info"><span><?= htmlspecialchars($nome) ?></span>
                    <div class="avatar"></div>
                </div>
            </header>

            <section class="modal-section">
                <span class="material-symbols-rounded">email</span>
                <?php if ($mensagem): ?>
                    <p class="success-message"><?= $mensagem ?></p>
                <?php endif; ?>
                <div class="button-container">
                    <form method="POST" action="">
                        <button type="submit">Gerar Novo Convite</button>
                    </form>
                </div>
            </section>

            <section class="convites-section">
                <h2>Convites Gerados</h2>
                <div class="convites-grid">
                    <?php foreach ($convites as $convite): ?>
                        <div class="convite-card">
                            <?php
                            $sql = "SELECT nome FROM clientes WHERE convite_id = ?";
                            $stmt_cliente = $pdo->prepare($sql);
                            $stmt_cliente->execute([$convite['id']]);
                            $cliente = $stmt_cliente->fetch();
                            $dataHora = strtotime($convite['data_criacao']);
                            ?>

                            <?php if ($cliente): ?>
                                <p class="vinculado"><strong>Cliente:</strong> <?= htmlspecialchars($cliente['nome']) ?></p>
                            <?php else: ?>
                                <p class="disponivel"><strong>Status:</strong> Disponível</p>
                            <?php endif; ?>

                            <p><strong>Código:</strong> <?= htmlspecialchars($convite['codigo']) ?></p>
                            <p>
                                <strong>Data:</strong> <?= date('d/m/Y', $dataHora) ?><br>
                                <strong>Hora:</strong> <?= date('H:i:s', $dataHora) ?>
                            </p>

                            <a href="?excluir=<?= $convite['id'] ?>" class="excluir-btn">Excluir</a>
                        </div>
                    <?php endforeach; ?>

                </div>
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