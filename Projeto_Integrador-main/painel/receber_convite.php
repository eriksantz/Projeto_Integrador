<?php
session_start();

// Verifica se o usuário está logado e é um cliente
if (!isset($_SESSION['usuario_id']) || strtolower($_SESSION['tipo_usuario']) !== 'cliente') {
    header('Location: ../login.php');
    exit;
}

require '../conexao.php'; // Inclui o arquivo de conexão com o banco de dados

$mensagem = '';
$usuario_id = $_SESSION['usuario_id'];
$exibir_popup_sucesso = false;

if (isset($_GET['sucesso']) && $_GET['sucesso'] == '1') {
    $exibir_popup_sucesso = true;
}

// Verifica se o cliente já tem um convite vinculado para exibir na página
$sql_verifica = "SELECT 
                    conv.codigo, 
                    cli.data_vinculacao,
                    gest.nome AS nome_gestor
                 FROM clientes AS cli
                 JOIN convites AS conv ON cli.convite_id = conv.id
                 JOIN gestores AS gest ON conv.gestor_id = gest.id
                 WHERE cli.id = ? AND cli.convite_id IS NOT NULL";

$stmt_verifica = $pdo->prepare($sql_verifica);
$stmt_verifica->execute([$usuario_id]);
$convite_vinculado = $stmt_verifica->fetch();

// Processa o formulário de submissão do código do convite
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['codigo'])) {
    $codigo = trim($_POST['codigo']);

    // Busca o convite no banco de dados
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
            // Atualiza o cliente com o ID do convite
            $sql_update_cliente = "UPDATE clientes SET convite_id = ?, data_vinculacao = NOW() WHERE id = ?";
            $stmt_update_cliente = $pdo->prepare($sql_update_cliente);
            $stmt_update_cliente->execute([$convite['id'], $usuario_id]);

            // Marca o convite como usado
            $sql_update_convite = "UPDATE convites SET usado = 1 WHERE id = ?";
            $stmt_update_convite = $pdo->prepare($sql_update_convite);
            $stmt_update_convite->execute([$convite['id']]);

            // Recarrega os dados do convite para exibir na página e dispara o pop-up de sucesso
            header('Location: receber_convite.php?sucesso=1');
            exit;
        }
    } else {
        $mensagem = '<span style="color: red;">Código de convite inválido.</span>';
    }
}

$nome = $_SESSION['nome']; // Pega o nome do usuário da sessão
$usuario_id = $_SESSION['usuario_id'];
$tipo_usuario = strtolower($_SESSION['tipo_usuario']);
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
    <link rel="stylesheet" href="sidebar.css">
    <link rel="stylesheet" href="responsividade.css">
    <style>
        /* Estilos Gerais dos Pop-ups */
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
            max-width: 550px;
            width: 90%;
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.2);
            display: flex;
            flex-direction: column;
            gap: 1rem;
            font-family: 'Plus Jakarta Sans', sans-serif;
            position: relative; /* Necessário para posicionar o botão de fechar */
        }

        /* Cabeçalho do Pop-up */
        .popup-header {
            display: flex;
            align-items: center;
            gap: 0.75rem;
        }
        
        /* Botão de Fechar (X) */
        .popup-close-btn {
            position: absolute;
            top: 10px;
            right: 10px;
            background: transparent;
            border: none;
            font-size: 1.8rem;
            cursor: pointer;
            color: #999;
            padding: 5px;
            line-height: 1;
            transition: color 0.2s ease;
        }

        .popup-close-btn:hover {
            color: #333;
        }

        .popup-content input {
            box-shadow: 0 2px 5px rgba(0,0,0,0.05);
            padding: 0.7rem 1rem;
            border: 1.5px solid #843af3;
            border-radius: 20px;
            font-size: 1rem;
            color: #333;
            font-family: 'Plus Jakarta Sans', sans-serif;
            transition: border-color 0.3s;
        }

        .popup-content input:focus {
            border-color: #4ce68b;
            outline: none;
        }

        .popup-content button {
            padding: 0.6rem 1rem;
            border-radius: 10rem;
            border: none;
            cursor: pointer;
            font-family: 'Plus Jakarta Sans', sans-serif;
        }

        .btn-roxo {
            background: #843af3;
            color: white;
            transition: background 0.3s ease;
            font-size: 1rem;
        }

        .btn-roxo:hover {
            background: #4ce68b;
        }

        .btn-cinza {
            background: #f1f1f1;
            transition: background 0.3s ease;
        }

        .btn-cinza:hover {
            background: #e0e0e0;
        }
        
        /* FIX: Container para a mensagem de erro/sucesso para evitar pulo de layout */
        .message-container {
            min-height: 24px; /* Reserva altura para a mensagem */
            display: flex;
            align-items: center;
            justify-content: center;
            width: 100%;
            font-weight: bold;
            text-align: center;
        }

        .message-container p {
            margin: 0;
        }

        /* Ícone de Sucesso */
        .success-icon {
            font-size: 3.5rem;
            color: #4ce68b;
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
                    <a href="painel_cliente.php" class="menu-item"><span class="material-symbols-rounded">space_dashboard</span><span class="text">Postagens</span></a>
                    <a href="receber_convite.php" class="menu-item active"><span class="material-symbols-rounded">forward_to_inbox</span><span class="text">Convites</span></a>
                    <a href="perfil.php" class="menu-item"><span class="material-symbols-rounded">person</span><span class="text">Perfil</span></a>
                    <a href="configuracoes.php" class="menu-item"><span class="material-symbols-rounded">settings</span><span class="text">Configurações</span></a>
                    
                </div>
                <div style="margin-top: auto;">
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
                <button id="toggleBtn" class="toggle-btn"><span class="material-symbols-rounded" id="iconToggle">chevron_left</span></button>
                <h1>Vincular Convite</h1>
                <div class="user-info"><span><?= htmlspecialchars($nome) ?></span>
                <img
                    src="../avatar/gerar_avatar.php?id=<?= $usuario_id ?>&tipo=<?= $tipo_usuario ?>" 
                    alt="Avatar de <?= htmlspecialchars($nome) ?>"
                    class="avatar">
                </div>
            </header>

            <section class="modal-section" style="display: flex; flex-direction: column; align-items: center;">
                <span class="material-symbols-rounded" style="font-size: 3rem; color: #843af3;">email</span>
                
                <div class="message-container">
                    <?php if ($mensagem): ?>
                        <p><?= $mensagem ?></p>
                    <?php endif; ?>
                </div>

                <?php if (!$convite_vinculado): ?>
                    <button class="btn-roxo" onclick="abrirPopup()">Vincular Convite</button>
                <?php else: ?>
                    <p><strong>Seu convite vinculado:</strong> <?= htmlspecialchars($convite_vinculado['codigo']) ?></p>
                    <p><strong>Vinculado em:</strong> <?= date('d/m/Y', strtotime($convite_vinculado['data_vinculacao'])) ?> às <?= date('H:i', strtotime($convite_vinculado['data_vinculacao'])) ?></p>
                    <br>
                    <p><strong>Seu gestor:</strong> <?= htmlspecialchars($convite_vinculado['nome_gestor']) ?></p>
                <?php endif; ?>
            </section>
        </main>
    </div>

    <!-- Modal Vinculação -->
    <div class="popup-overlay" id="popup" style="display: none;">
        <form method="POST" action="" class="popup-content">
             <button type="button" class="popup-close-btn" onclick="fecharPopup()">
                <span class="material-symbols-rounded">close</span>
            </button>

            <div class="popup-header">
                <span class="material-symbols-rounded" style="font-size: 2rem; color: #843af3;">link</span>
                <h2 style="color: #843af3; margin: 0;">Vincular código do gestor</h2>
            </div>

            <label for="codigo"><strong>Código do gestor:</strong></label>
            <input type="text" name="codigo" placeholder="Ex: 58e7a72ffa" required>
            
            <div style="font-size: 0.9rem;">
                <strong>Instruções para vinculação:</strong>
                <ul style="padding-left: 1rem; margin-top: 0.5rem;">
                    <li style="margin-bottom: 0.5rem;">O código é fornecido pelo seu gestor;</li>
                    <li style="margin-bottom: 0.5rem;">Você pode se vincular a apenas um único gestor;</li>
                    <li style="margin-bottom: 0.5rem;">Se futuramente desejar trocar de gestor, solicite a desvinculação ao gestor atual;</li>
                    <li>Utilize o código exatamente como recebido, sem alterações.</li>
                </ul>
            </div>

            <div style="display: flex; justify-content: flex-end; gap: 0.5rem; margin-top: 1rem;">
                <button type="button" class="btn-cinza" onclick="fecharPopup()">Cancelar</button>
                <button type="submit" class="btn-roxo">Vincular Agora</button>
            </div>
        </form>
    </div>

    <!-- Modal Sucesso -->
    <div class="popup-overlay" id="popupSucesso" style="display: none;">
        <div class="popup-content" style="align-items: center; text-align: center;">
             <button type="button" class="popup-close-btn" onclick="fecharPopupSucesso()">
                <span class="material-symbols-rounded">close</span>
            </button>
            <span class="material-symbols-rounded success-icon">task_alt</span>
            <h2 style="color: #333; margin: 0;">Sucesso!</h2>
            <p style="margin: 0; color: #555;">Seu convite foi vinculado.</p>
            
            <button class="btn-roxo" onclick="fecharPopupSucesso()">Ótimo!</button>
        </div>
    </div>

    <script>
    // --- LÓGICA DA SIDEBAR ---
    const sidebar = document.getElementById('sidebar');
    const toggleBtn = document.getElementById('toggleBtn');
    const iconToggle = document.getElementById('iconToggle');
    const sidebarOverlay = document.getElementById('sidebarOverlay'); // Pega o novo overlay

    // Função para ABRIR a sidebar
    function abrirSidebar() {
        if (sidebar) sidebar.classList.add('expanded');
        if (sidebar) sidebar.classList.remove('collapsed');
        if (sidebarOverlay) sidebarOverlay.classList.add('visible');
        localStorage.setItem('sidebarCollapsed', 'false');
        if (iconToggle) updateIcon();
    }

    // Função para FECHAR a sidebar
    function fecharSidebar() {
        if (sidebar) sidebar.classList.add('collapsed');
        if (sidebar) sidebar.classList.remove('expanded');
        if (sidebarOverlay) sidebarOverlay.classList.remove('visible');
        localStorage.setItem('sidebarCollapsed', 'true');
        if (iconToggle) updateIcon();
    }

    // Função para atualizar o ícone do botão
    function updateIcon() {
        // No celular, o ícone deve ser sempre o de menu quando fechado e de fechar quando aberto
        if (window.innerWidth <= 768) {
            iconToggle.textContent = sidebar.classList.contains('expanded') ? 'close' : 'menu';
        } else { // Comportamento para desktop
            iconToggle.textContent = sidebar.classList.contains('collapsed') ? 'menu' : 'chevron_left';
        }
    }

    // Evento de clique no botão principal
    toggleBtn.addEventListener('click', () => {
        if (sidebar.classList.contains('collapsed')) {
            abrirSidebar();
        } else {
            fecharSidebar();
        }
    });

    // Evento de clique no OVERLAY para fechar o menu
    sidebarOverlay.addEventListener('click', () => {
        fecharSidebar();
    });

    // Estado inicial da sidebar ao carregar a página
    // Apenas no desktop respeitamos o localStorage. No mobile, começa sempre fechada.
    if (window.innerWidth > 768) {
        if (localStorage.getItem('sidebarCollapsed') === 'true') {
            fecharSidebar();
        } else {
            abrirSidebar();
        }
    } else {
        fecharSidebar(); // Força o estado fechado inicial no mobile
    }
    
    // Atualiza o ícone caso o tamanho da janela mude
    window.addEventListener('resize', updateIcon);


    // --- LÓGICA DOS POPUPS (Exemplo da página receber_convite.php) ---
    const popup = document.getElementById('popup');
    const popupSucesso = document.getElementById('popupSucesso');
    
    function abrirPopup() {
        if(popup) popup.style.display = 'flex';
    }
    function fecharPopup() {
        if(popup) popup.style.display = 'none';
    }
    function fecharPopupSucesso() {
        if(popupSucesso) popupSucesso.style.display = 'none';
    }

    if (popup) {
        popup.addEventListener('click', function(event) {
            if (event.target === popup) {
                fecharPopup();
            }
        });
    }
    if (popupSucesso) {
        popupSucesso.addEventListener('click', function(event) {
            if (event.target === popupSucesso) {
                fecharPopupSucesso();
            }
        });
    }

    // Este bloco PHP só deve existir na página receber_convite.php
    <?php if (isset($exibir_popup_sucesso) && $exibir_popup_sucesso): ?>
    window.addEventListener('load', function() {
        document.getElementById('popupSucesso').style.display = 'flex';
    });
    <?php endif; ?>
    </script>
</body>
</html>
