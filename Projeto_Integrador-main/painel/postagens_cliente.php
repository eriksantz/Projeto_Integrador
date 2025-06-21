<?php
session_start();

// 1. VERIFICAÇÃO DE SESSÃO E PERMISSÃO
if (!isset($_SESSION['usuario_id']) || $_SESSION['tipo_usuario'] !== 'Gestor') {
    header('Location: ../login.php');
    exit;
}

require_once '../conexao.php';

$nome = $_SESSION['nome'];
$gestor_id = $_SESSION['usuario_id'];

if (!isset($_GET['cliente_id'])) {
    header('Location: painel_gestor.php');
    exit;
}

$cliente_id = intval($_GET['cliente_id']);

// 2. VERIFICAR SE O CLIENTE PERTENCE AO GESTOR
$sql = "SELECT clientes.nome
        FROM clientes
        JOIN convites ON clientes.convite_id = convites.id
        WHERE clientes.id = ? AND convites.gestor_id = ?";
$stmt = $pdo->prepare($sql);
$stmt->execute([$cliente_id, $gestor_id]);
$cliente = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$cliente) {
    echo "Cliente não encontrado ou não pertence a você.";
    exit;
}

// 3. LÓGICA DE PROCESSAMENTO DO FORMULÁRIO (CRIAÇÃO/EDIÇÃO)
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $titulo = trim($_POST['titulo']);
    $descricao = trim($_POST['descricao']);
    $data_publicacao = !empty($_POST['data_publicacao']) ? $_POST['data_publicacao'] : null;
    $redes_sociais = trim($_POST['redes_sociais']);
    $is_edit = isset($_POST['postagem_id']) && !empty($_POST['postagem_id']);

    $imagem = null;
    if ($is_edit) {
        $postagem_id = intval($_POST['postagem_id']);
        $stmt = $pdo->prepare("SELECT imagem FROM postagens WHERE id = ? AND cliente_id = ?");
        $stmt->execute([$postagem_id, $cliente_id]);
        $postagem_atual = $stmt->fetch(PDO::FETCH_ASSOC);
        $imagem = $postagem_atual['imagem']; // Mantém a imagem antiga por padrão
    }

    // Processamento do upload de nova mídia
    if (isset($_FILES['imagem']) && $_FILES['imagem']['error'] === UPLOAD_ERR_OK) {
        // *** MODIFICAÇÃO: Adicionado 'gif' e 'mp4' ***
        $extensoes_permitidas = ['jpg', 'jpeg', 'png', 'gif', 'mp4'];
        $nome_arquivo = $_FILES['imagem']['name'];
        $extensao = strtolower(pathinfo($nome_arquivo, PATHINFO_EXTENSION));

        if (in_array($extensao, $extensoes_permitidas)) {
            $novo_nome = uniqid('media_', true) . '.' . $extensao;
            $diretorio = '../uploads/';

            if (!is_dir($diretorio)) {
                mkdir($diretorio, 0777, true);
            }

            $caminho_destino = $diretorio . $novo_nome;

            if (move_uploaded_file($_FILES['imagem']['tmp_name'], $caminho_destino)) {
                // Se for uma edição e uma nova imagem foi enviada, apaga a antiga
                if ($is_edit && !empty($imagem) && file_exists($diretorio . $imagem)) {
                    @unlink($diretorio . $imagem);
                }
                $imagem = $novo_nome; // Atualiza para o nome do novo arquivo
            }
        }
    }

    // Atualiza ou insere no banco de dados
    if ($is_edit) {
        $sql = "UPDATE postagens 
                SET titulo = ?, descricao = ?, imagem = ?, data_publicacao = ?, redes_sociais = ?, status = 'Aguardando Análise'
                WHERE id = ? AND cliente_id = ?";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$titulo, $descricao, $imagem, $data_publicacao, $redes_sociais, $postagem_id, $cliente_id]);
    } else {
        $sql = "INSERT INTO postagens (cliente_id, gestor_id, titulo, descricao, imagem, data_publicacao, redes_sociais)
                VALUES (?, ?, ?, ?, ?, ?, ?)";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$cliente_id, $gestor_id, $titulo, $descricao, $imagem, $data_publicacao, $redes_sociais]);
    }

    header("Location: postagens_cliente.php?cliente_id=" . $cliente_id);
    exit;
}

// 4. BUSCAR POSTAGENS EXISTENTES PARA EXIBIÇÃO
$sqlPostagens = "SELECT id, titulo, descricao, imagem, criado_em, status, feedback_cliente, data_publicacao, redes_sociais
                 FROM postagens 
                 WHERE cliente_id = ? 
                 ORDER BY criado_em DESC";
$stmtPostagens = $pdo->prepare($sqlPostagens);
$stmtPostagens->execute([$cliente_id]);
$postagens = $stmtPostagens->fetchAll(PDO::FETCH_ASSOC);

$usuario_id = $_SESSION['usuario_id'];
$tipo_usuario = strtolower($_SESSION['tipo_usuario']);
?>

<!DOCTYPE html>
<html lang="pt-br">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Postagens de <?= htmlspecialchars($cliente['nome']) ?></title>
    <link rel="icon" type="image/png" sizes="16x16" href="../imagens/fav_icon.png">
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;600&display=swap" rel="stylesheet" />
    <link href="https://fonts.googleapis.com/css2?family=Material+Symbols+Rounded" rel="stylesheet" />
    <link rel="stylesheet" href="calendario-popup/flatpickr.min.css">
    <link rel="stylesheet" href="painel_gestor.css">
    <link rel="stylesheet" href="criacao_postagem.css">
    <link rel="stylesheet" href="estilos_diversos/exclusaopopup.css">
    <link rel="stylesheet" href="estilos_diversos/viewcard.css">
    <link rel="stylesheet" href="responsividade.css">

    <style>
        * {
            letter-spacing: 0.001rem;
        }

        .postagem-card:hover {
            transform: translateY(-3px);
            box-shadow: 0 4px 10px rgba(0, 0, 0, 0.15);
        }

        .postagem-card h3 {
            margin: 0;
            color: #333;
        }

        .postagem-card p {
            color: #555;
        }

        .data {
            font-size: 0.9rem;
            color: #999;
        }

        .postagem-card img.miniatura {
            width: 100%;
            max-width: 200px;
            height: 200px;
            object-fit: cover;
            border-radius: 8px !important;
            margin: auto;
        }

        .texto-truncado {
            display: -webkit-box;
            -webkit-box-orient: vertical;
            overflow: hidden;
            text-overflow: ellipsis;
            -webkit-line-clamp: 2;
            line-clamp: 2;
        }

        .card-conteudo {
            flex-grow: 1;
            display: flex;
            flex-direction: column;
            gap: 1rem;
        }

        .postagem-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .menu-dots {
            position: relative;
            cursor: pointer;
            width: 32px;
            height: 32px;
            border-radius: 50%;
            margin-right: -10px;
            display: flex;
            align-items: center;
            justify-content: center;
            transition: background-color 0.2s ease;
        }

        .menu-dots:hover {
            background-color: #f0f0f0;
        }

        .menu-dots .material-symbols-rounded {
            font-size: 20px;
            color: #555;
            margin-right: -11px;
            pointer-events: none;
        }

        .menu-dropdown-trespontos {
            position: absolute;
            top: 28px;
            right: 0;
            z-index: 100;
            width: 160px;
            background-color: white;
            border-radius: 12px;
            border: 1px solid #EBEBEB;
            box-shadow: 0 4px 16px rgba(0, 0, 0, 0.08);
            padding: 6px;


            opacity: 0;
            visibility: hidden;
            transform: scale(0.95) translateY(-5px);
            transform-origin: top right;
            transition: opacity 0.2s ease, transform 0.2s ease, visibility 0s 0.2s;
        }


        .menu-dropdown-trespontos.show {
            opacity: 1;
            visibility: visible;
            transform: scale(1) translateY(0);
            transition: opacity 0.2s ease, transform 0.2s ease;
        }

        .menu-option {
            display: flex;
            align-items: center;
            gap: 10px;
            width: 100%;
            padding: 10px;
            border: none;
            background: none;
            cursor: pointer;
            border-radius: 8px;
            font-family: 'Plus Jakarta Sans', sans-serif;
            font-size: 14px;
            font-weight: 600;
            color: #843af3;
            text-align: left;
            transition: background-color 0.2s ease, color 0.2s ease;
        }

        .menu-option .material-symbols-rounded {
            font-size: 20px;
            color: #843af3;
            transition: color 0.2s ease;
        }

        .menu-option:hover {
            background-color: #f5f5f5;
        }

        .btn-excluir {
            color: #E53935;
        }

        .btn-excluir .material-symbols-rounded {
            color: #E53935;
        }

        .btn-excluir:hover {
            background-color: #FFF1F0;
            color: #C62828;
        }

        .btn-excluir:hover .material-symbols-rounded {
            color: #C62828;
        }

        .postagem-card img.miniatura,
        .postagem-card video.miniatura {
            width: 100%;
            max-width: 200px;
            height: 200px;
            object-fit: cover;
            border-radius: 8px !important;
            margin: auto;
            display: block;
            background-color: #f0f0f0;
        }
    </style>
</head>

<body>
    <div class="container">
        <aside class="sidebar expanded" id="sidebar">
            <div class="logo">
                <img src="../imagens/logo_GAD_PAINEL.png" alt="Logo" style="width: 80%; height: auto;">
            </div>
            <nav class="menu-wrapper">
                <div class="menu">
                    <a href="painel_gestor.php" class="menu-item"><span class="material-symbols-rounded">space_dashboard</span> <span class="text">Visão Geral</span></a>
                    <a href="criar_convite.php" class="menu-item"><span class="material-symbols-rounded">forward_to_inbox</span> <span class="text">Convites</span></a>
                    <a href="perfil.php" class="menu-item"><span class="material-symbols-rounded">Person</span> <span class="text">Perfil</span></a>
                    <a href="configuracoes.php" class="menu-item"><span class="material-symbols-rounded">settings</span> <span class="text">Configurações</span></a>
                </div>
                <div style="margin-top: auto; width: 100%;">
                    <a href="logout.php" class="menu-item logout"><span class="material-symbols-rounded">exit_to_app</span> <span class="text">Sair</span></a>
                </div>
            </nav>
        </aside>

        <div class="sidebar-overlay" id="sidebarOverlay"></div>
        <main class="main-content">
            <header class="header">
                <button id="toggleBtn" class="toggle-btn">
                    <span class="material-symbols-rounded" id="iconToggle">chevron_left</span>
                </button>
                <h1>Postagens para <?= htmlspecialchars($cliente['nome']) ?></h1>
                <div class="user-info">
                    <span><?= htmlspecialchars($nome) ?></span>
                    <img
                        src="../avatar/gerar_avatar.php?id=<?= $usuario_id ?>&tipo=<?= $tipo_usuario ?>"
                        alt="Avatar de <?= htmlspecialchars($nome) ?>"
                        class="avatar">
                </div>
            </header>

            <section class="postagem-grid">
                <?php
                if (empty($postagens)) {
                    echo '
                <div style="text-align: center; width: 100%; padding: 2rem;">
                    <img src="../imagens/sem_cliente_feedback.png" alt="Sem postagens" style="width: 200px; opacity: 0.6;">
                    <p style="margin-top: 1rem; font-size: 1.1rem; color: #666;">
                        Nenhuma postagem foi criada para este cliente até agora.
                    </p>
                </div>';
                } else {
                    foreach ($postagens as $postagem) {
                        $statusClass = 'status-' . strtolower(str_replace(' ', '-', $postagem['status']));
                        echo '<div class="postagem-card visualizavel" 
        data-titulo="' . htmlspecialchars($postagem['titulo'], ENT_QUOTES) . '"
        data-descricao="' . htmlspecialchars($postagem['descricao'], ENT_QUOTES) . '"
        data-imagem="' . htmlspecialchars($postagem['imagem']) . '"
        data-datapub="' . (!empty($postagem['data_publicacao']) ? date('d/m/Y', strtotime($postagem['data_publicacao'])) : 'Não agendada') . '"
        data-redes="' . (!empty($postagem['redes_sociais']) ? htmlspecialchars($postagem['redes_sociais'], ENT_QUOTES) : 'Nenhuma') . '"
        data-feedback="' . htmlspecialchars($postagem['feedback_cliente'], ENT_QUOTES) . '">';

                        // Header do Card com título e menu de 3 pontos
                        echo '
                        <div class="postagem-header">
                            <h3>' . htmlspecialchars($postagem['titulo']) . '</h3>
                            <div class="menu-dots">
                                <span class="material-symbols-rounded">more_vert</span>
                                <div class="menu-dropdown-trespontos"> 
                                    <button class="menu-option btn-editar"
                                        data-id="' . $postagem['id'] . '"
                                        data-titulo="' . htmlspecialchars($postagem['titulo'], ENT_QUOTES) . '"
                                        data-descricao="' . htmlspecialchars($postagem['descricao'], ENT_QUOTES) . '"
                                        data-redes="' . htmlspecialchars($postagem['redes_sociais'], ENT_QUOTES) . '"
                                        data-publicacao="' . htmlspecialchars($postagem['data_publicacao'], ENT_QUOTES) . '">
                                        <span class="material-symbols-rounded">edit</span>
                                        Editar
                                    </button>
                                    <button class="menu-option btn-excluir" data-id="' . $postagem['id'] . '">
                                        <span class="material-symbols-rounded">delete</span>
                                        Excluir
                                    </button>
                                </div>
                            </div>
                        </div>';

                        // Conteúdo do Card
                        echo '<div class="card-conteudo">';
                        echo '<p class="texto-truncado">' . nl2br(htmlspecialchars($postagem['descricao'])) . '</p>';

                        // *** MODIFICAÇÃO: Lógica para exibir imagem, gif ou vídeo ***
                        if (!empty($postagem['imagem'])) {
                            $caminho_arquivo = '../uploads/' . htmlspecialchars($postagem['imagem']);
                            $extensao = strtolower(pathinfo($caminho_arquivo, PATHINFO_EXTENSION));

                            if ($extensao === 'mp4') {
                                echo '<video src="' . $caminho_arquivo . '" controls class="miniatura"></video>';
                            } else { // Para jpg, jpeg, png, gif
                                echo '<img src="' . $caminho_arquivo . '" alt="Mídia da postagem" class="miniatura">';
                            }
                        }

                        echo '</div>'; // Fim de .card-conteudo

                        // Feedback do Cliente
                        if (!empty($postagem['feedback_cliente'])) {
                            echo '<div class="feedback-cliente">
                                <strong>Feedback do Cliente:</strong>
                                <div class="texto-truncado">' . nl2br(htmlspecialchars($postagem['feedback_cliente'])) . '</div>
                            </div>';
                        }

                        // Footer do Card
                        echo '
                        <div class="postagem-footer">
                            <div class="data">Criado em: ' . date('d/m/Y H:i', strtotime($postagem['criado_em'])) . '</div>
                            <span class="status-badge ' . $statusClass . '">' . htmlspecialchars($postagem['status']) . '</span>
                        </div>';

                        echo '</div>'; // Fim de .postagem-card
                    }
                }
                ?>
            </section>
        </main>
    </div>

    <button id="btn-flutuante" title="Menu" aria-label="Abrir menu">
        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
            <path d="M12 20h9" />
            <path d="M16.5 3.5a2.121 2.121 0 0 1 3 3L7 19l-4 1 1-4 12.5-12.5z" />
        </svg>
    </button>
    <div id="menu-expansivel" aria-label="Menu de ações">
        <button id="btn-criar-postagem" aria-label="Criar postagem">Criar Postagem</button>
    </div>

    <!-- Modal para Criar/Editar Postagem -->
    <div id="modal-postagem" class="modal" role="dialog" aria-modal="true" aria-labelledby="modal-title">
        <div class="modal-conteudo">
            <button id="fechar-modal" aria-label="Fechar formulário">×</button>
            <h2 id="modal-title">Criar Postagem</h2>
            <br>
            <form id="form-postagem" method="POST" enctype="multipart/form-data" action="postagens_cliente.php?cliente_id=<?= $cliente_id ?>">
                <input type="hidden" name="cliente_id" value="<?= $cliente_id ?>">
                <label for="titulo">Título</label>
                <input type="text" id="titulo" name="titulo" required maxlength="28">
                <label for="descricao">Descrição</label>
                <textarea id="descricao" name="descricao"></textarea>
                <label for="data_publicacao">Data de Publicação</label>
                <input type="text" id="data_publicacao" name="data_publicacao" placeholder="Selecione uma data">
                <label for="redes_sociais">Redes Sociais (separadas por vírgula)</label>
                <input type="text" id="redes_sociais" name="redes_sociais">

                <!-- *** MODIFICAÇÃO: Atualizado o label e o 'accept' do input *** -->
                <label for="imagem">Mídia (jpg, png, gif, mp4)</label>
                <input type="file" id="imagem" name="imagem" accept="image/jpeg,image/png,image/gif,video/mp4">

                <button type="submit">Salvar</button>
            </form>
        </div>
    </div>

    <script src="calendario-popup/flatpickr.min.js"></script>
    <script src="calendario-popup/pt.js"></script>
    <script src="sidebar.js"></script>
    <script>
        // Bloco de funções e configurações iniciais (sem alterações)

    flatpickr("#data_publicacao", {
        locale: "pt",
        dateFormat: "Y-m-d",
        altInput: true,
        altFormat: "d/m/Y",
        allowInput: true
    });

    function showErrorPopup(message) {
        let popup = document.createElement('div');
        popup.textContent = message;
        popup.style.cssText = 'position: fixed; top: 20px; left: 50%; transform: translateX(-50%); background-color: #FA8072; color: white; padding: 1rem 2rem; border-radius: 5px; box-shadow: 0 2px 10px rgba(0,0,0,0.2); z-index: 9999; font-weight: 600; font-size: 1rem; user-select: none;';
        document.body.appendChild(popup);
        setTimeout(() => {
            popup.style.transition = 'opacity 0.5s';
            popup.style.opacity = '0';
        }, 3000);
        setTimeout(() => popup.remove(), 3500);
    }

    // --- Início do Script Principal Corrigido ---
    document.addEventListener('DOMContentLoaded', () => {
        // --- Variáveis Gerais ---
        const modal = document.getElementById('modal-postagem');
        const form = document.getElementById('form-postagem');
        const modalTitle = document.getElementById('modal-title');
        const fecharModal = document.getElementById('fechar-modal');
        const btnFlutuante = document.getElementById('btn-flutuante');
        const menuExpansivel = document.getElementById('menu-expansivel');
        const postagemGrid = document.querySelector('.postagem-grid');
        
        // --- Variáveis do Modal de Exclusão ---
        const modalExclusaoOverlay = document.getElementById('popupdelete-overlay');
        let urlParaExcluir = null;
        if(modalExclusaoOverlay) {
            const btnConfirmarExclusao = modalExclusaoOverlay.querySelector('.popupdelete-botao-confirmar');
            const btnCancelarExclusao = modalExclusaoOverlay.querySelector('.popupdelete-botao-cancelar');
            const btnFecharExclusao = modalExclusaoOverlay.querySelector('.popupdelete-fechar');
            
            window.abrirModalExclusao = (url) => { urlParaExcluir = url; modalExclusaoOverlay.classList.add('popupdelete-visivel'); };
            const fecharModalExclusao = () => { modalExclusaoOverlay.classList.remove('popupdelete-visivel'); urlParaExcluir = null; };
            
            btnConfirmarExclusao.addEventListener('click', () => { if (urlParaExcluir) window.location.href = urlParaExcluir; });
            btnCancelarExclusao.addEventListener('click', fecharModalExclusao);
            btnFecharExclusao.addEventListener('click', fecharModalExclusao);
            modalExclusaoOverlay.addEventListener('click', (e) => { if (e.target === modalExclusaoOverlay) fecharModalExclusao(); });
        }
        
        // --- Variáveis do Modal de Visualização ---
        const modalView = document.getElementById('modal-visualizacao');
        const fecharModalViewBtn = modalView.querySelector('.fechar-modal-visualizacao');
        const viewMediaContainer = document.getElementById('view-media-container');
        const viewTitulo = document.getElementById('view-titulo');
        const viewDescricao = document.getElementById('view-descricao');
        const viewData = document.getElementById('view-data');
        const viewRedes = document.getElementById('view-redes');
        const viewFeedbackBox = document.getElementById('view-feedback-box');
        const viewFeedback = document.getElementById('view-feedback');

        // --- Funções de Controle dos Modais ---
        const resetFormToCreate = () => {
            if(!form) return;
            form.reset();
            modalTitle.textContent = "Criar Postagem";
            const hiddenInput = form.querySelector('input[name="postagem_id"]');
            if (hiddenInput) hiddenInput.remove();
            form.action = `postagens_cliente.php?cliente_id=<?= $cliente_id ?>`;
        };

        const fecharModalVisualizacao = () => {
            modalView.classList.remove('visivel');
            const video = viewMediaContainer.querySelector('video');
            if (video) video.pause();
        };

        // --- Event Listeners ---

        // Botão flutuante
        const btnCriarPostagem = document.getElementById('btn-criar-postagem');
        if(btnCriarPostagem) {
            btnCriarPostagem.addEventListener('click', () => {
                resetFormToCreate();
                modal.classList.add('show');
                menuExpansivel.classList.remove('show');
                btnFlutuante.classList.remove('rotate');
            });
        }
        if(btnFlutuante) {
            btnFlutuante.addEventListener('click', () => {
                menuExpansivel.classList.toggle('show');
                btnFlutuante.classList.toggle('rotate');
            });
        }
        
        // Fechar modal de criação
        if(fecharModal) fecharModal.addEventListener('click', () => modal.classList.remove('show'));
        window.addEventListener('click', (e) => { if (e.target === modal) modal.classList.remove('show'); });

        // Fechar modal de visualização
        fecharModalViewBtn.addEventListener('click', fecharModalVisualizacao);
        modalView.addEventListener('click', (e) => { if (e.target === modalView) fecharModalVisualizacao(); });

        // Validação do formulário de criação/edição
        if(form) {
            form.addEventListener('submit', (e) => {
                const file = form.querySelector('#imagem').files[0];
                if (file) {
                    const ext = file.name.split('.').pop().toLowerCase();
                    const permitidos = ['jpg', 'jpeg', 'png', 'gif', 'mp4'];
                    if (!permitidos.includes(ext)) {
                        e.preventDefault();
                        showErrorPopup('Formato não suportado, envie a imagem em formato JPG, JPEG, PNG, GIF ou MP4.');
                    }
                }
            });
        }
        
        // *** INÍCIO DA LÓGICA DE CLIQUE CORRIGIDA E UNIFICADA ***
        if(postagemGrid) {
            postagemGrid.addEventListener('click', e => {
                const menuDots = e.target.closest('.menu-dots');
                const btnEditar = e.target.closest('.btn-editar');
                const btnExcluir = e.target.closest('.btn-excluir');
                
                // Prioridade 1: Clicou em qualquer elemento do menu de 3 pontos
                if(menuDots || btnEditar || btnExcluir) {
                    e.stopPropagation(); // Impede que o clique "vaze" para o card
                    
                    // Se clicou nos 3 pontos, abre/fecha o menu
                    if (menuDots) {
                        const dropdown = menuDots.querySelector('.menu-dropdown-trespontos');
                        if (dropdown) dropdown.classList.toggle('show');
                    }
                    
                    // Se clicou em Editar
                    if (btnEditar) {
                        resetFormToCreate();
                        const post = btnEditar.dataset;
                        modalTitle.textContent = "Editar Postagem";
                        form.querySelector('#titulo').value = post.titulo;
                        form.querySelector('#descricao').value = post.descricao;
                        form.querySelector('#data_publicacao')._flatpickr.setDate(post.publicacao, true);
                        form.querySelector('#redes_sociais').value = post.redes;
                        const hiddenInput = document.createElement('input');
                        hiddenInput.type = 'hidden';
                        hiddenInput.name = 'postagem_id';
                        hiddenInput.value = post.id;
                        form.appendChild(hiddenInput);
                        modal.classList.add('show');
                    }
                    
                    // Se clicou em Excluir
                    if (btnExcluir) {
                        const postId = btnExcluir.dataset.id;
                        const url = `excluir_postagem.php?id=${postId}&cliente_id=<?= $cliente_id ?>`;
                        window.abrirModalExclusao(url);
                    }
                    
                    return; // Finaliza a execução aqui
                }

                // Prioridade 2: Se não foi no menu, verifica se foi no card para visualização
                const cardVisualizavel = e.target.closest('.visualizavel');
                if (cardVisualizavel) {
                    const data = cardVisualizavel.dataset;
                    viewTitulo.textContent = data.titulo;
                    viewDescricao.textContent = data.descricao;
                    viewData.textContent = data.datapub;
                    viewRedes.textContent = data.redes;

                    viewMediaContainer.innerHTML = '';
                    if (data.imagem) {
                        const caminho = `../uploads/${data.imagem}`;
                        const extensao = data.imagem.split('.').pop().toLowerCase();
                        if (extensao === 'mp4') {
                            const video = document.createElement('video');
                            video.src = caminho; video.controls = true; video.autoplay = true; video.loop = true;
                            viewMediaContainer.appendChild(video);
                        } else {
                            const img = document.createElement('img');
                            img.src = caminho; img.alt = data.titulo;
                            viewMediaContainer.appendChild(img);
                        }
                    }
                    
                    if (data.feedback && data.feedback.trim() !== '') {
                        viewFeedback.textContent = data.feedback;
                        viewFeedbackBox.style.display = 'block';
                    } else {
                        viewFeedbackBox.style.display = 'none';
                    }
                    modalView.classList.add('visivel');
                }
            });
        }

        // Listener para fechar menus dropdown e flutuante se clicar fora
        document.addEventListener('click', e => {
            const activeDropdown = document.querySelector('.menu-dropdown-trespontos.show');
            if (activeDropdown && !e.target.closest('.menu-dots')) {
                activeDropdown.classList.remove('show');
            }

            if (menuExpansivel && menuExpansivel.classList.contains('show') && !e.target.closest('#btn-flutuante') && !e.target.closest('#menu-expansivel')) {
                menuExpansivel.classList.remove('show');
                btnFlutuante.classList.remove('rotate');
            }
        });
        // *** FIM DA LÓGICA DE CLIQUE CORRIGIDA ***
    });
    </script>


    <div id="popupdelete-overlay" class="popupdelete-overlay">
        <div class="popupdelete-conteudo">
            <button class="popupdelete-fechar">
                <span class="material-symbols-rounded">close</span>
            </button>

            <div class="popupdelete-icone popupdelete-cor-icone-perigo">
                <span class="material-symbols-rounded">delete_forever</span>
            </div>

            <h2>Tem certeza?</h2>
            <p>Você realmente deseja excluir esta postagem? Esta ação não pode ser desfeita.</p>

            <div class="popupdelete-botoes">
                <button class="popupdelete-botao-cancelar">Cancelar</button>
                <button class="popupdelete-botao-confirmar popupdelete-cor-botao-perigo">Excluir</button>
            </div>
        </div>
    </div>

    <div id="modal-visualizacao" class="modal-visualizacao">
        <div class="modal-visualizacao-conteudo">
            <button class="fechar-modal-visualizacao">&times;</button>
            <div class="modal-visualizacao-media" id="view-media-container">
            </div>
            <div class="modal-visualizacao-info">
                <h2 id="view-titulo"></h2>
                <p id="view-descricao"></p>
                <ul class="detalhes-lista">
                    <li><strong>Data de Publicação:</strong> <span id="view-data"></span></li>
                    <li><strong>Redes Sociais:</strong> <span id="view-redes"></span></li>
                </ul>
                <div id="view-feedback-box" class="feedback-box" style="display: none;">
                    <h3>Feedback do Cliente</h3>
                    <p id="view-feedback"></p>
                </div>
            </div>
        </div>
    </div>

</body>

</html>