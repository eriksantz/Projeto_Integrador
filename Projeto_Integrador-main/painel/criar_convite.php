<?php
session_start();
if (!isset($_SESSION['usuario_id']) || $_SESSION['tipo_usuario'] !== 'Gestor') {
    header('Location: ../login.php');
    exit;
}

require '../conexao.php';

$limite_convites = 5;
$mensagem = [];
$gestor_id = $_SESSION['usuario_id'];


if (isset($_SESSION['flash_message'])) {
    $mensagem = $_SESSION['flash_message'];
    unset($_SESSION['flash_message']);
}


$sql_select = "SELECT id, codigo, data_criacao FROM convites WHERE gestor_id = ?";
$stmt_select = $pdo->prepare($sql_select);
$stmt_select->execute([$gestor_id]);
$convites = $stmt_select->fetchAll();
$total_convites = count($convites);


if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if ($total_convites < $limite_convites) {
        $codigo = bin2hex(random_bytes(5));
        $sql_insert = "INSERT INTO convites (gestor_id, codigo) VALUES (?, ?)";
        $stmt_insert = $pdo->prepare($sql_insert);

        if ($stmt_insert->execute([$gestor_id, $codigo])) {
            $_SESSION['flash_message'] = ['type' => 'success', 'text' => "Convite criado com sucesso! Código: <strong>$codigo</strong>"];
            header('Location: criar_convite.php');
            exit;
        }
    }
}


if (isset($_GET['excluir'])) {
    $convite_id = $_GET['excluir'];
    $sql_delete = "DELETE FROM convites WHERE id = ? AND gestor_id = ?";
    $stmt_delete = $pdo->prepare($sql_delete);
    $stmt_delete->execute([$convite_id, $_SESSION['usuario_id']]);

    $_SESSION['flash_message'] = ['type' => 'delete', 'text' => "Convite excluído com sucesso."];
    header('Location: criar_convite.php');
    exit;
}


if (isset($_GET['desvincular'])) {
    $convite_id = $_GET['desvincular'];

    try {
        $pdo->beginTransaction();

        // 1. Encontrar o ID do cliente para poder apagar suas postagens
        $sql_find_cliente = "SELECT id FROM clientes WHERE convite_id = ?";
        $stmt_find_cliente = $pdo->prepare($sql_find_cliente);
        $stmt_find_cliente->execute([$convite_id]);
        $cliente_encontrado = $stmt_find_cliente->fetch();

        if ($cliente_encontrado) {
            $cliente_id = $cliente_encontrado['id'];

            // 2. Apagar todas as postagens do cliente
            $sql_delete_posts = "DELETE FROM postagens WHERE cliente_id = ?";
            $stmt_delete_posts = $pdo->prepare($sql_delete_posts);
            $stmt_delete_posts->execute([$cliente_id]);

            // 3. Desvincular o cliente (setar convite_id como NULL)
            $sql_unlink_cliente = "UPDATE clientes SET convite_id = NULL WHERE id = ?";
            $stmt_unlink_cliente = $pdo->prepare($sql_unlink_cliente);
            $stmt_unlink_cliente->execute([$cliente_id]);
        }

        // 4. Apagar o convite, que agora não tem mais vínculo
        $sql_delete_convite = "DELETE FROM convites WHERE id = ?";
        $stmt_delete_convite = $pdo->prepare($sql_delete_convite);
        $stmt_delete_convite->execute([$convite_id]);

        $pdo->commit();
        $_SESSION['flash_message'] = ['type' => 'delete', 'text' => "Cliente desvinculado e todas as suas postagens foram apagadas."];
    } catch (PDOException $e) {
        $pdo->rollBack();
        $_SESSION['flash_message'] = ['type' => 'error', 'text' => "Erro ao desvincular: " . $e->getMessage()];
    }

    header('Location: criar_convite.php');
    exit;
}

if ($total_convites >= $limite_convites && empty($mensagem)) {
    $mensagem = ['type' => 'error', 'text' => "Você atingiu o limite de $limite_convites convites. <br> Exclua um convite para poder gerar um novo."];
}
$usuario_id = $_SESSION['usuario_id'];
$tipo_usuario = strtolower($_SESSION['tipo_usuario']);
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
    <link rel="stylesheet" href="estilos_diversos/exclusaopopup.css">
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
                    <a href="painel_gestor.php" class="menu-item"><span class="material-symbols-rounded">space_dashboard</span><span class="text">Visão Geral</span></a>
                    <a href="criar_convite.php" class="menu-item active"><span class="material-symbols-rounded">forward_to_inbox</span><span class="text">Convites</span></a>
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
                <button id="toggleBtn" class="toggle-btn"><span class="material-symbols-rounded" id="iconToggle">chevron_left</span></button>
                <h1>Convites</h1>
                <div class="user-info"><span><?= htmlspecialchars($nome) ?></span>
                <img
                    src="../avatar/gerar_avatar.php?id=<?= $usuario_id ?>&tipo=<?= $tipo_usuario ?>" 
                    alt="Avatar de <?= htmlspecialchars($nome) ?>"
                    class="avatar">
                </div>
            </header>

            <section class="modal-section">
                <span class="material-symbols-rounded">email</span>
                <?php if (!empty($mensagem)): ?>
                    <?php
                    // Define a classe CSS com base no tipo da mensagem
                    $message_class = '';
                    if ($mensagem['type'] === 'success') {
                        $message_class = 'success-message';
                    } elseif ($mensagem['type'] === 'delete') {
                        $message_class = 'delete-message';
                    } elseif ($mensagem['type'] === 'error') {
                        $message_class = 'error-message';
                    }
                    ?>
                    <p class="<?= $message_class ?>"><?= $mensagem['text'] ?></p>
                <?php endif; ?>

                <div class="button-container">
                    <form method="POST" action="">
                        <?php if ($total_convites >= $limite_convites): ?>
                        <?php else: ?>
                            <button type="submit">Gerar Novo Convite</button>
                        <?php endif; ?>
                    </form>
                </div>
            </section>

            <section class="convites-section">
                <h2>Convites Gerados (<?= $total_convites ?>/<?= $limite_convites ?>):</h2>
                <br>
                <div class="convites-grid">
                    <?php foreach ($convites as $convite): ?>
                        <div class="convite-card">
                            <?php

                            $sql_cliente = "SELECT nome FROM clientes WHERE convite_id = ?";
                            $stmt_cliente = $pdo->prepare($sql_cliente);
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

                            <?php if ($cliente): ?>
                                <a href="?desvincular=<?= $convite['id'] ?>"
                                    class="excluir-btn"
                                    data-action="desvincular">
                                    Desvincular
                                </a>
                            <?php else: ?>
                                <a href="?excluir=<?= $convite['id'] ?>"
                                    class="excluir-btn"
                                    data-action="excluir">
                                    Excluir
                                </a>
                            <?php endif; ?>
                        </div>
                    <?php endforeach; ?>
                </div>
            </section>

            <div id="popup-confirmacao" class="popupdelete-overlay">
                <div class="popupdelete-conteudo">
                    <button id="popup-fechar" class="popupdelete-fechar">
                        <span class="material-symbols-rounded">close</span>
                    </button>

                    <div id="popup-icone" class="popupdelete-icone popupdelete-cor-icone-perigo">
                        <span id="popup-icone-span" class="material-symbols-rounded">person_cancel</span>
                    </div>

                    <h2 id="popup-titulo">Tem certeza?</h2>
                    <p id="popup-texto">Esta ação não pode ser desfeita.</p>

                    <div class="popupdelete-botoes">
                        <button id="popup-cancelar" class="popupdelete-botao-cancelar">Cancelar</button>
                        <button id="popup-confirmar" class="popupdelete-botao-confirmar popupdelete-cor-botao-perigo">Confirmar</button>
                    </div>
                </div>
            </div>
        </main>
    </div>

    <script src="sidebar.js"></script>
    <script>
    
        document.addEventListener('DOMContentLoaded', () => {
            const popup = document.getElementById('popup-confirmacao');
            if (!popup) return;

            const popupTitulo = document.getElementById('popup-titulo');
            const popupTexto = document.getElementById('popup-texto');
            const popupConfirmar = document.getElementById('popup-confirmar');
            const popupCancelar = document.getElementById('popup-cancelar');
            const popupFechar = document.getElementById('popup-fechar');
            const popupIconeSpan = document.getElementById('popup-icone-span');

            let urlParaAcao = null;

            const abrirPopup = (url, titulo, texto, botaoTexto, icone) => {
                urlParaAcao = url;
                popupTitulo.textContent = titulo;
                popupTexto.innerHTML = texto;
                popupConfirmar.textContent = botaoTexto;
        
                if (popupIconeSpan) {
                    popupIconeSpan.textContent = icone;
                }
                popup.classList.add('popupdelete-visivel');
            };

            const fecharPopup = () => {
                popup.classList.remove('popupdelete-visivel');
                urlParaAcao = null;
            };

            popupConfirmar.addEventListener('click', () => {
                if (urlParaAcao) {
                    window.location.href = urlParaAcao;
                }
            });

            popupCancelar.addEventListener('click', fecharPopup);
            popupFechar.addEventListener('click', fecharPopup);
            popup.addEventListener('click', (e) => {
                if (e.target === popup) {
                    fecharPopup();
                }
            });

            
            const convitesGrid = document.querySelector('.convites-grid');
            if (convitesGrid) {
                convitesGrid.addEventListener('click', (e) => {
                    const botao = e.target.closest('.excluir-btn, .desvincular-btn');
                    if (botao) {
                        e.preventDefault();
                        const url = botao.href;
                        const acao = botao.dataset.action;

                        if (acao === 'desvincular') {
                            abrirPopup(
                                url,
                                'Desvincular Cliente?',
                                'Esta ação irá desvincular o cliente e <strong>excluir permanentemente todas as postagens associadas a ele</strong>.<br><br>Essa operação é irreversível.',
                                'Desvincular',
                                'person_cancel' // Ícone para desvincular
                            );
                        } else if (acao === 'excluir') {
                            abrirPopup(
                                url,
                                'Excluir Convite?',
                                'Você realmente deseja excluir este convite disponível?',
                                'Excluir',
                                'delete' // Ícone para excluir
                            );
                        }
                    }
                });
            }
        });
    </script>
</body>

</html>