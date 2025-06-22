<?php
session_start();

if (!isset($_SESSION['usuario_id']) || $_SESSION['tipo_usuario'] !== 'Gestor') {
    header('Location: ../login.php');
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
    <title>Painel do Gestor</title>
    <link rel="icon" type="image/png" sizes="16x16" href="../imagens/fav_icon.png">
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;600&display=swap" rel="stylesheet" />
    <link href="https://fonts.googleapis.com/css2?family=Material+Symbols+Rounded" rel="stylesheet" />
    <link rel="stylesheet" href="painel_gestor.css">
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
                    <a href="painel_gestor.php" class="menu-item active"><span class="material-symbols-rounded">space_dashboard</span><span class="text">Visão Geral</span></a>
                    <a href="criar_convite.php" class="menu-item"><span class="material-symbols-rounded">forward_to_inbox</span><span class="text">Convites</span></a>
                    <a href="perfil.php" class="menu-item"><span class="material-symbols-rounded">Person</span><span class="text">Perfil</span></a>
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
                <h1>Visão Geral</h1>
                <div class="user-info">
                    <span><?= htmlspecialchars($nome) ?></span>
                    <img
                        src="../avatar/gerar_avatar.php?id=<?= $usuario_id ?>&tipo=<?= $tipo_usuario ?>" 
                        alt="Avatar de <?= htmlspecialchars($nome) ?>"
                        class="avatar">
                </div>
            </header>

            <section class="clientes-grid">
                <?php
                $gestor_id = $_SESSION['usuario_id'];

                $sql = "SELECT clientes.id, clientes.nome
        FROM clientes
        JOIN convites ON clientes.convite_id = convites.id
        WHERE convites.gestor_id = ?";

                $stmt = $pdo->prepare($sql);
                $stmt->execute([$gestor_id]);
                $clientes = $stmt->fetchAll(PDO::FETCH_ASSOC);

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
                        <a class="cliente-card" href="postagens_cliente.php?cliente_id=' . $cliente['id'] . '">
                            <h3>Cliente: ' . htmlspecialchars($cliente['nome']) . '</h3>
                        </a>';
                    }
                }
                ?>
            </section>
        </main>
    </div>

    <script src="sidebar.js"></script>
    <script>
    </script>
</body>

</html>