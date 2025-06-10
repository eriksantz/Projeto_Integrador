<?php
session_start();

if (!isset($_SESSION['usuario_id']) || strtolower($_SESSION['tipo_usuario']) !== 'cliente') {
    header('Location: ../login.html');
    exit;
}

require '../conexao.php';

$mensagem = '';
$usuario_id = $_SESSION['usuario_id'];
$exibir_popup_sucesso = false;

// Verifica se o cliente já tem convite vinculado
$sql_verifica = "SELECT convites.codigo, convites.data_criacao 
                 FROM clientes 
                 JOIN convites ON clientes.convite_id = convites.id
                 WHERE clientes.id = ? AND clientes.convite_id IS NOT NULL";
$stmt_verifica = $pdo->prepare($sql_verifica);
$stmt_verifica->execute([$usuario_id]);
$convite_vinculado = $stmt_verifica->fetch();

// Processo de submissão do convite
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['codigo'])) {
    $codigo = trim($_POST['codigo']);

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
            $sql = "UPDATE clientes SET convite_id = ? WHERE id = ?";
            $stmt = $pdo->prepare($sql);
            $stmt->execute([$convite['id'], $usuario_id]);

            $sql = "UPDATE convites SET usado = 1 WHERE id = ?";
            $stmt = $pdo->prepare($sql);
            $stmt->execute([$convite['id']]);

            $mensagem = '<span style="color: green;">Convite vinculado com sucesso!</span>';
            $stmt_verifica->execute([$usuario_id]);
            $convite_vinculado = $stmt_verifica->fetch();

            $exibir_popup_sucesso = true;
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
    <title>Vincular Convite</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <link rel="icon" type="image/png" href="../imagens/fav_icon.png" />
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;600&display=swap" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Material+Symbols+Rounded" rel="stylesheet" />
    <link rel="stylesheet" href="receber_convite.css">
    <style>
        .popup-overlay {
            position: fixed;
            top: 0;
            left: 0;
            width: 100vw;
            height: 100vh;
            background: rgba(0, 0, 0, 0.3);
            display: flex;
            justify-content: center;
            align-items: center;
            z-index: 1000;
        }

        .popup-content {
            background: white;
            border-radius: 1rem;
            padding: 2rem;
            max-width: 400px;
            width: 150%;
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.2);
            display: flex;
            flex-direction: column;
            gap: 1rem;
            font-family: 'Plus Jakarta Sans', sans-serif;
        }

        .popup-content input {
            padding: 0.7rem 1rem;
            border: 1px solid #ccc;
            border-radius: 0.6rem;
            font-size: 1rem;
        }

        .popup-content button {
            padding: 0.6rem 1rem;
            border-radius: 0.5rem;
            border: none;
            cursor: pointer;
        }

        .btn-roxo {
            background: #a259ff;
            color: white;
        }

        .btn-cinza {
            background: #f1f1f1;
        }

        .success-message {
            font-weight: bold;
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
                    <a href="painel_cliente.php" class="menu-item"><span class="material-symbols-rounded">space_dashboard</span><span class="text">Visão Geral</span></a>
                    <a href="receber_convite.php" class="menu-item active"><span class="material-symbols-rounded">forward_to_inbox</span><span class="text">Convites</span></a>
                    <a href="#" class="menu-item"><span class="material-symbols-rounded">person</span><span class="text">Perfil</span></a>
                    <a href="#" class="menu-item"><span class="material-symbols-rounded">settings</span><span class="text">Configurações</span></a>
                    <a href="#" class="menu-item"><span class="material-symbols-rounded">help</span><span class="text">Ajuda</span></a>
                </div>
                <div style="margin-top: auto;">
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
                <div class="user-info"><span><?= htmlspecialchars($nome) ?></span><div class="avatar"></div></div>
            </header>

            <section class="modal-section" style="display: flex; flex-direction: column; align-items: center; gap: 1rem;">
                <span class="material-symbols-rounded" style="font-size: 3rem; color: #a259ff;">email</span>
                <?php if ($mensagem): ?>
                    <p class="success-message"><?= $mensagem ?></p>
                <?php endif; ?>
                <?php if (!$convite_vinculado): ?>
                    <button onclick="abrirPopup()" style="background: #a259ff; color: white; padding: 1rem 2rem; border-radius: 1rem; border: none; font-size: 1rem; cursor: pointer;">Vincular Convite</button>
                <?php else: ?>
                    <p><strong>Seu convite vinculado:</strong> <?= htmlspecialchars($convite_vinculado['codigo']) ?></p>
                    <p><strong>Data de Criação:</strong> <?= date('d/m/Y H:i', strtotime($convite_vinculado['data_criacao'])) ?></p>
                <?php endif; ?>
            </section>
        </main>
    </div>

    <!-- Modal Vinculação -->
    <div class="popup-overlay" id="popup" style="display: none;">
        <form method="POST" action="">
            <div class="popup-content">
                <h2 style="color: #a259ff;">Vincular código do gestor</h2>
                <label for="codigo"><strong>Código do gestor:</strong></label>
                <input type="text" name="codigo" placeholder="Cole o código recebido do gestor" required>
                <small>O código foi fornecido pelo seu gestor</small>
                <div style="font-size: 0.9rem;">
                    <strong>Requisitos para vinculação:</strong>
                    <ul style="padding-left: 1rem;">
                        <li>Você deve ter uma conta de cliente ativa</li>
                        <li>Utilize o código exatamente como recebido</li>
                    </ul>
                </div>
                <div style="display: flex; justify-content: flex-end; gap: 0.5rem;">
                    <button type="button" class="btn-cinza" onclick="fecharPopup()">Cancelar</button>
                    <button type="submit" class="btn-roxo">Vincular Agora</button>
                </div>
            </div>
        </form>
    </div>

    <!-- Modal Sucesso -->
    <div class="popup-overlay" id="popupSucesso" style="display: none;">
        <div class="popup-content" style="text-align: center;">
            <h2 style="color: #4CAF50;">✅ Sucesso!</h2>
            <p>Convite vinculado com sucesso.</p>
            <button class="btn-roxo" onclick="fecharPopupSucesso()">Fechar</button>
        </div>
    </div>

    <script>
        const toggleBtn = document.getElementById('toggleBtn');
        const sidebar = document.getElementById('sidebar');
        const iconToggle = document.getElementById('iconToggle');

        function updateIcon() {
            iconToggle.textContent = sidebar.classList.contains('collapsed') ? 'menu' : 'chevron_left';
        }

        sidebar.classList.toggle('collapsed', localStorage.getItem('sidebarCollapsed') === 'true');
        sidebar.classList.toggle('expanded', localStorage.getItem('sidebarCollapsed') !== 'true');
        updateIcon();

        toggleBtn.addEventListener('click', () => {
            sidebar.classList.toggle('collapsed');
            sidebar.classList.toggle('expanded');
            localStorage.setItem('sidebarCollapsed', sidebar.classList.contains('collapsed'));
            updateIcon();
        });

        function abrirPopup() {
            document.getElementById('popup').style.display = 'flex';
        }

        function fecharPopup() {
            document.getElementById('popup').style.display = 'none';
        }

        function fecharPopupSucesso() {
            document.getElementById('popupSucesso').style.display = 'none';
        }

        <?php if ($exibir_popup_sucesso): ?>
            window.onload = function() {
                document.getElementById('popupSucesso').style.display = 'flex';
            };
        <?php endif; ?>
    </script>
</body>
</html>
