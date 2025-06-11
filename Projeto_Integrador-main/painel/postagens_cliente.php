<?php
session_start();

if (!isset($_SESSION['usuario_id']) || $_SESSION['tipo_usuario'] !== 'Gestor') {
    header('Location: ../login.html');
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

// Verificar se o cliente pertence ao gestor
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

// LÓGICA DE PROCESSAMENTO DO FORMULÁRIO
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $titulo = trim($_POST['titulo']);
    $descricao = trim($_POST['descricao']);
    $data_publicacao = !empty($_POST['data_publicacao']) ? $_POST['data_publicacao'] : null;
    $redes_sociais = trim($_POST['redes_sociais']);
    $is_edit = isset($_POST['postagem_id']) && !empty($_POST['postagem_id']);

    if ($is_edit) {
        $postagem_id = intval($_POST['postagem_id']);
        $stmt = $pdo->prepare("SELECT imagem FROM postagens WHERE id = ? AND cliente_id = ?");
        $stmt->execute([$postagem_id, $cliente_id]);
        $postagem_atual = $stmt->fetch(PDO::FETCH_ASSOC);
        $imagem = $postagem_atual['imagem'];
    } else {
        $imagem = null;
    }

    if (isset($_FILES['imagem']) && $_FILES['imagem']['error'] === UPLOAD_ERR_OK) {
        $extensoes_permitidas = ['jpg', 'jpeg', 'png'];
        $nome_arquivo = $_FILES['imagem']['name'];
        $extensao = strtolower(pathinfo($nome_arquivo, PATHINFO_EXTENSION));
        if (in_array($extensao, $extensoes_permitidas)) {
            $novo_nome = uniqid('img_', true) . '.' . $extensao;
            $diretorio = '../uploads/';
            if (!is_dir($diretorio)) {
                mkdir($diretorio, 0777, true);
            }
            $caminho_destino = $diretorio . $novo_nome;
            if (move_uploaded_file($_FILES['imagem']['tmp_name'], $caminho_destino)) {
                if ($is_edit && !empty($imagem) && file_exists($diretorio . $imagem)) {
                    @unlink($diretorio . $imagem);
                }
                $imagem = $novo_nome;
            }
        }
    }

    if ($is_edit) {
        $sql = "UPDATE postagens 
                SET titulo = ?, descricao = ?, imagem = ?, data_publicacao = ?, redes_sociais = ?, status = 'Aguardando Análise'
                WHERE id = ? AND cliente_id = ?";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$titulo, $descricao, $imagem, $data_publicacao, $redes_sociais, $postagem_id, $cliente_id]);
    } else {
        $sql = "INSERT INTO postagens (cliente_id, titulo, descricao, imagem, data_publicacao, redes_sociais)
                VALUES (?, ?, ?, ?, ?, ?)";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$cliente_id, $titulo, $descricao, $imagem, $data_publicacao, $redes_sociais]);
    }

    header("Location: postagens_cliente.php?cliente_id=" . $cliente_id);
    exit;
}

// Buscar postagens existentes
$sqlPostagens = "SELECT id, titulo, descricao, imagem, criado_em, status, feedback_cliente, data_publicacao, redes_sociais
                 FROM postagens 
                 WHERE cliente_id = ? 
                 ORDER BY criado_em DESC";
$stmtPostagens = $pdo->prepare($sqlPostagens);
$stmtPostagens->execute([$cliente_id]);
$postagens = $stmtPostagens->fetchAll(PDO::FETCH_ASSOC);
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

    <style>
        .postagem-card:hover {
            transform: translateY(-3px);
            box-shadow: 0 4px 10px rgba(0, 0, 0, 0.15);
        }
        .postagem-card h3 { margin: 0; color: #333; }
        .postagem-card p { color: #555; }
        .data { font-size: 0.9rem; color: #999; }
        .postagem-card img.miniatura {
            width: 100%; max-width: 200px; height: 200px; object-fit: cover;
            border-radius: 8px !important; margin: auto;
        }
        .texto-truncado {
            display: -webkit-box; -webkit-box-orient: vertical; overflow: hidden;
            text-overflow: ellipsis; -webkit-line-clamp: 2; line-clamp: 2;
        }
        .card-conteudo { flex-grow: 1; display: flex; flex-direction: column; gap: 1rem; }
        .postagem-header { display: flex; justify-content: space-between; align-items: center; }

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
            display: flex; align-items: center; gap: 10px; width: 100%;
            padding: 10px; border: none; background: none; cursor: pointer;
            border-radius: 8px; font-family: 'Plus Jakarta Sans', sans-serif;
            font-size: 14px; font-weight: 500; color: #843af3; text-align: left;
            transition: background-color 0.2s ease, color 0.2s ease;
        }
        .menu-option .material-symbols-rounded {
            font-size: 20px; color: #843af3; transition: color 0.2s ease;
        }
        .menu-option:hover { background-color: #f5f5f5; }
        .btn-excluir { color: #E53935; }
        .btn-excluir .material-symbols-rounded { color: #E53935; }
        .btn-excluir:hover { background-color: #FFF1F0; color: #C62828; }
        .btn-excluir:hover .material-symbols-rounded { color: #C62828; }
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
                    <a href="#" class="menu-item"><span class="material-symbols-rounded">Person</span> <span class="text">Perfil</span></a>
                    <a href="#" class="menu-item"><span class="material-symbols-rounded">settings</span> <span class="text">Configurações</span></a>
                    <a href="#" class="menu-item"><span class="material-symbols-rounded">group</span> <span class="text">Usuários</span></a>
                    <a href="#" class="menu-item"><span class="material-symbols-rounded">help</span> <span class="text">Ajuda</span></a>
                </div>
                <div style="margin-top: auto; width: 100%;">
                    <a href="logout.php" class="menu-item logout"><span class="material-symbols-rounded">exit_to_app</span> <span class="text">Sair</span></a>
                </div>
            </nav>
        </aside>

        <main class="main-content">
            <header class="header">
                <button id="toggleBtn" class="toggle-btn">
                    <span class="material-symbols-rounded" id="iconToggle">chevron_left</span>
                </button>
                <h1>Postagens para <?= htmlspecialchars($cliente['nome']) ?></h1>
                <div class="user-info">
                    <span><?= htmlspecialchars($nome) ?></span>
                    <div class="avatar"></div>
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
                        echo '<div class="postagem-card">';
                        
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

                        echo '<div class="card-conteudo">';
                        echo '<p class="texto-truncado">' . nl2br(htmlspecialchars($postagem['descricao'])) . '</p>';
                        if (!empty($postagem['imagem'])) {
                            echo '<img src="../uploads/' . htmlspecialchars($postagem['imagem']) . '" alt="Imagem da postagem" class="miniatura">';
                        }
                        echo '</div>';
                        if (!empty($postagem['feedback_cliente'])) {
                            echo '<div class="feedback-cliente">
                            <strong>Feedback do Cliente:</strong>
                            <div class="texto-truncado">' . nl2br(htmlspecialchars($postagem['feedback_cliente'])) . '</div>
                        </div>';
                        }
                        echo '
                <div class="postagem-footer">
                    <div class="data">Criado em: ' . date('d/m/Y H:i', strtotime($postagem['criado_em'])) . '</div>
                    <span class="status-badge ' . $statusClass . '">' . $postagem['status'] . '</span>
                </div>
            </div>';
                    }
                }
                ?>
            </section>
        </main>
    </div>

    <button id="btn-flutuante" title="Menu" aria-label="Abrir menu">
        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"
            stroke-linecap="round" stroke-linejoin="round">
            <path d="M12 20h9" />
            <path d="M16.5 3.5a2.121 2.121 0 0 1 3 3L7 19l-4 1 1-4 12.5-12.5z" />
        </svg>
    </button>
    <div id="menu-expansivel" aria-label="Menu de ações">
        <button id="btn-criar-postagem" aria-label="Criar postagem">Criar Postagem</button>
    </div>
    <div id="modal-postagem" class="modal" role="dialog" aria-modal="true" aria-labelledby="modal-title">
        <div class="modal-conteudo">
            <button id="fechar-modal" aria-label="Fechar formulário">×</button>
            <h2 id="modal-title">Criar Postagem</h2>
            <form id="form-postagem" method="POST" enctype="multipart/form-data" action="postagens_cliente.php?cliente_id=<?= $cliente_id ?>">
                <input type="hidden" name="cliente_id" value="<?= $cliente_id ?>">
                <label for="titulo">Título</label>
                <input type="text" id="titulo" name="titulo" required>
                <label for="descricao">Descrição</label>
                <textarea id="descricao" name="descricao"></textarea>
                <label for="data_publicacao">Data de Publicação</label>
                <input type="text" id="data_publicacao" name="data_publicacao" placeholder="Selecione uma data">
                <label for="redes_sociais">Redes Sociais (separadas por vírgula)</label>
                <input type="text" id="redes_sociais" name="redes_sociais">
                <label for="imagem">Imagem (jpg, jpeg, png)</label>
                <input type="file" id="imagem" name="imagem" accept="image/jpeg,image/png,image/jpg">
                <button type="submit">Salvar</button>
            </form>
        </div>
    </div>

    <script src="calendario-popup/flatpickr.min.js"></script>
    <script src="calendario-popup/pt.js"></script>
    <script>
        
        const toggleBtn = document.getElementById('toggleBtn');
        const sidebar = document.getElementById('sidebar');
        const iconToggle = document.getElementById('iconToggle');
        function updateIcon() { iconToggle.textContent = sidebar.classList.contains('collapsed') ? 'menu' : 'chevron_left'; }
        const isCollapsed = localStorage.getItem('sidebarCollapsed') === 'true';
        if (isCollapsed) { sidebar.classList.add('collapsed'); sidebar.classList.remove('expanded'); } else { sidebar.classList.add('expanded'); sidebar.classList.remove('collapsed'); }
        updateIcon();
        toggleBtn.addEventListener('click', () => {
            sidebar.classList.toggle('collapsed');
            sidebar.classList.toggle('expanded');
            const currentlyCollapsed = sidebar.classList.contains('collapsed');
            localStorage.setItem('sidebarCollapsed', currentlyCollapsed);
            updateIcon();
        });
        flatpickr("#data_publicacao", { locale: "pt", dateFormat: "Y-m-d", altInput: true, altFormat: "d/m/Y", allowInput: true });
        function showErrorPopup(message) {
            let popup = document.createElement('div');
            popup.textContent = message;
            popup.style.cssText = 'position: fixed; top: 20px; left: 50%; transform: translateX(-50%); background-color: #FA8072; color: white; padding: 1rem 2rem; border-radius: 5px; box-shadow: 0 2px 10px rgba(0,0,0,0.2); z-index: 9999; font-weight: 600; font-size: 1rem; user-select: none;';
            document.body.appendChild(popup);
            setTimeout(() => { popup.style.transition = 'opacity 0.5s'; popup.style.opacity = '0'; }, 3000);
            setTimeout(() => popup.remove(), 3500);
        }

        document.addEventListener('DOMContentLoaded', () => {
            const modal = document.getElementById('modal-postagem');
            const form = document.getElementById('form-postagem');
            const modalTitle = document.getElementById('modal-title');
            const fecharModal = document.getElementById('fechar-modal');
            const btnFlutuante = document.getElementById('btn-flutuante');
            const menuExpansivel = document.getElementById('menu-expansivel');

            const resetFormToCreate = () => {
                form.reset();
                modalTitle.textContent = "Criar Postagem";
                const hiddenInput = form.querySelector('input[name="postagem_id"]');
                if (hiddenInput) { hiddenInput.remove(); }
                form.action = `postagens_cliente.php?cliente_id=<?= $cliente_id ?>`;
            };

            const btnCriarPostagem = document.getElementById('btn-criar-postagem');
            btnCriarPostagem.addEventListener('click', () => {
                resetFormToCreate();
                modal.classList.add('show');
                menuExpansivel.classList.remove('show');
                btnFlutuante.classList.remove('rotate');
            });
            btnFlutuante.addEventListener('click', () => {
                menuExpansivel.classList.toggle('show');
                btnFlutuante.classList.toggle('rotate');
            });
            fecharModal.addEventListener('click', () => modal.classList.remove('show'));
            window.addEventListener('click', (e) => {
                if (e.target === modal) modal.classList.remove('show');
            });
            document.addEventListener('click', (e) => {
                if (!menuExpansivel.contains(e.target) && e.target !== btnFlutuante && !e.target.closest('#btn-flutuante')) {
                    menuExpansivel.classList.remove('show');
                    btnFlutuante.classList.remove('rotate');
                }
            });

            const postagemGrid = document.querySelector('.postagem-grid');
            postagemGrid.addEventListener('click', e => {
                const btnEditar = e.target.closest('.btn-editar');
                const btnExcluir = e.target.closest('.btn-excluir');

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

                if (btnExcluir) {
                    e.preventDefault();
                    const postId = btnExcluir.dataset.id;
                    if (window.confirm('Tem certeza que deseja excluir esta postagem? Esta ação não pode ser desfeita.')) {
                        alert("Funcionalidade de exclusão a ser implementada.");
                        // window.location.href = `excluir_postagem.php?id=${postId}&cliente_id=<?= $cliente_id ?>`;
                    }
                }
            });
            form.addEventListener('submit', (e) => {
                const file = form.querySelector('#imagem').files[0];
                if (file) {
                    const ext = file.name.split('.').pop().toLowerCase();
                    const permitidos = ['jpg', 'jpeg', 'png'];
                    if (!permitidos.includes(ext)) {
                        e.preventDefault();
                        showErrorPopup('Formato não suportado, envie a imagem em formato JPG, JPEG ou PNG.');
                    }
                }
            });
        });

        document.addEventListener('click', e => {
            const menuDots = e.target.closest('.menu-dots');

            const activeDropdown = document.querySelector('.menu-dropdown-trespontos.show');

            if (!menuDots) {
                if (activeDropdown) {
                    activeDropdown.classList.remove('show');
                }
                return;
            }

            const dropdown = menuDots.querySelector('.menu-dropdown-trespontos');
            const isAlreadyOpen = dropdown.classList.contains('show');

            if (activeDropdown) {
                activeDropdown.classList.remove('show');
            }
            
            if (!isAlreadyOpen) {
                dropdown.classList.add('show');
            }
        });
    </script>
</body>
</html>
