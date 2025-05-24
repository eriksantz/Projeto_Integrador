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

// Processar formulário
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $titulo = trim($_POST['titulo']);
    $descricao = trim($_POST['descricao']);
    $data_publicacao = !empty($_POST['data_publicacao']) ? $_POST['data_publicacao'] : null;
    $redes_sociais = trim($_POST['redes_sociais']);
    $imagem = null;

    // Upload de imagem
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
                $imagem = $novo_nome;
            }
        }
    }

    // Inserir no banco
    $sql = "INSERT INTO postagens (cliente_id, titulo, descricao, imagem, data_publicacao, redes_sociais)
            VALUES (?, ?, ?, ?, ?, ?)";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([
        $cliente_id,
        $titulo,
        $descricao,
        $imagem,
        $data_publicacao,
        $redes_sociais
    ]);

    header("Location: postagens.php?cliente_id=" . $cliente_id);
    exit;
}

// Buscar postagens existentes
$sqlPostagens = "SELECT id, titulo, descricao, imagem, criado_em 
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
    <link rel="stylesheet" href="painel_gestor.css">
    <link rel="stylesheet" href="criacao_postagem.css">
    <style>
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
            height: 200px;
            object-fit: contain;
            display: block;
            margin: 1rem auto 1rem auto;
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
                    <a href="painel_gestor.php" class="menu-item">
                        <span class="material-symbols-rounded">space_dashboard</span>
                        <span class="text">Visão Geral</span>
                    </a>
                    <a href="criar_convite.php" class="menu-item">
                        <span class="material-symbols-rounded">forward_to_inbox</span>
                        <span class="text">Convites</span>
                    </a>
                    <a href="#" class="menu-item">
                        <span class="material-symbols-rounded">Person</span>
                        <span class="text">Perfil</span>
                    </a>
                    <a href="#" class="menu-item">
                        <span class="material-symbols-rounded">settings</span>
                        <span class="text">Configurações</span>
                    </a>
                    <a href="#" class="menu-item">
                        <span class="material-symbols-rounded">group</span>
                        <span class="text">Usuários</span>
                    </a>
                    <a href="#" class="menu-item">
                        <span class="material-symbols-rounded">help</span>
                        <span class="text">Ajuda</span>
                    </a>
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
                        echo '
        <div class="postagem-card">
            <div class="postagem-header">
                <h3>' . htmlspecialchars($postagem['titulo']) . '</h3>
                <div class="menu-dots" tabindex="0" aria-haspopup="true" aria-expanded="false" aria-label="Abrir menu de ações">
                    &#8942;
                    <div class="menu-dropdown" role="menu" aria-hidden="true">
                        <a href="editar_postagem.php?id=' . $postagem['id'] . '" class="btn-editar" role="menuitem">Editar</a>
                        <a href="excluir_postagem.php?id=' . $postagem['id'] . '" class="btn-excluir" role="menuitem" onclick="return confirm(\'Tem certeza que deseja excluir?\')">Excluir</a>
                    </div>
                </div>
            </div>
            <p>' . nl2br(htmlspecialchars($postagem['descricao'])) . '</p>';

                        if (!empty($postagem['imagem'])) {
                            echo '<img src="../uploads/' . htmlspecialchars($postagem['imagem']) . '" alt="Imagem da postagem" class="miniatura">';
                        }

                        echo '<div class="data">Criado em: ' . date('d/m/Y \à\s H:i', strtotime($postagem['criado_em'])) . '</div>
        </div>';
                    }
                }
                ?>
            </section>


        </main>
    </div>

    <!-- Botão Flutuante e Menu -->
    <button id="btn-flutuante" title="Menu" aria-label="Abrir menu">
        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"
            stroke-linecap="round" stroke-linejoin="round">
            <path d="M12 20h9" />
            <path d="M16.5 3.5a2.121 2.121 0 0 1 3 3L7 19l-4 1 1-4 12.5-12.5z" />
        </svg>
    </button>

    <div id="menu-expansivel" aria-label="Menu de ações">
        <button id="btn-criar-postagem" aria-label="Criar postagem">Criar Postagem</button>
        <!-- Adicionar outros botões aqui!!!!!!!! -->
    </div>

    <div id="modal-postagem" class="modal" role="dialog" aria-modal="true" aria-labelledby="modal-title">
        <div class="modal-conteudo">
            <button id="fechar-modal" aria-label="Fechar formulário">×</button>
            <h2 id="modal-title">Criar Postagem</h2>
            <form id="form-postagem" method="POST" enctype="multipart/form-data" action="processa_postagem.php">
                <input type="hidden" name="cliente_id" value="<?= $cliente_id ?>">
                <label for="titulo">Título</label>
                <input type="text" id="titulo" name="titulo" required>

                <label for="descricao">Descrição</label>
                <textarea id="descricao" name="descricao"></textarea>

                <label for="data_publicacao">Data de Publicação</label>
                <input type="date" id="data_publicacao" name="data_publicacao">

                <label for="redes_sociais">Redes Sociais (vírgulas)</label>
                <input type="text" id="redes_sociais" name="redes_sociais">

                <label for="imagem">Imagem (jpg, jpeg, png)</label>
                <input type="file" id="imagem" name="imagem" accept="image/*">

                <button type="submit">Salvar</button>
            </form>
        </div>
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


        const btnFlutuante = document.getElementById('btn-flutuante');
        const menuExpansivel = document.getElementById('menu-expansivel');
        const btnCriarPostagem = document.getElementById('btn-criar-postagem');
        const modal = document.getElementById('modal-postagem');
        const fecharModal = document.getElementById('fechar-modal');

        btnFlutuante.addEventListener('click', () => {
            menuExpansivel.classList.toggle('show');
            btnFlutuante.classList.toggle('rotate');
        });

        btnCriarPostagem.addEventListener('click', () => {
            modal.classList.add('show');
            menuExpansivel.classList.remove('show');
            btnFlutuante.classList.remove('rotate');
        });

        fecharModal.addEventListener('click', () => {
            modal.classList.remove('show');
        });

        window.addEventListener('click', (e) => {
            if (e.target === modal) {
                modal.classList.remove('show');
            }
        });

        window.addEventListener('click', (e) => {
            if (!menuExpansivel.contains(e.target) && e.target !== btnFlutuante) {
                menuExpansivel.classList.remove('show');
                btnFlutuante.classList.remove('rotate');
            }
        });

        document.querySelectorAll('.menu-dots').forEach(dot => {
            dot.addEventListener('click', e => {
                e.stopPropagation();
                const dropdown = dot.querySelector('.menu-dropdown');
                const isOpen = dropdown.style.display === 'block';

                // Fecha todos menus abertos
                document.querySelectorAll('.menu-dropdown').forEach(menu => menu.style.display = 'none');

                if (!isOpen) {
                    dropdown.style.display = 'block';
                    dot.setAttribute('aria-expanded', 'true');
                } else {
                    dropdown.style.display = 'none';
                    dot.setAttribute('aria-expanded', 'false');
                }
            });
        });

        // Fecha o menu ao clicar fora
        document.addEventListener('click', () => {
            document.querySelectorAll('.menu-dropdown').forEach(menu => {
                menu.style.display = 'none';
                menu.parentElement.setAttribute('aria-expanded', 'false');
            });
        });


        const form = document.getElementById('form-postagem');
        const inputImagem = document.getElementById('imagem');

        form.addEventListener('submit', (e) => {
            const file = inputImagem.files[0];
            if (file) {
                const ext = file.name.split('.').pop().toLowerCase();
                const permitidos = ['jpg', 'jpeg', 'png'];
                if (!permitidos.includes(ext)) {
                    e.preventDefault();
                    showErrorPopup('Formato não suportado, envie a imagem em formato JPG, JPEG ou PNG.');
                }
            }
        });

        function showErrorPopup(message) {
            let popup = document.createElement('div');
            popup.textContent = message;
            popup.style.position = 'fixed';
            popup.style.top = '20px';
            popup.style.left = '50%';
            popup.style.transform = 'translateX(-50%)';
            popup.style.backgroundColor = '#FA8072';
            popup.style.color = 'white';
            popup.style.padding = '1rem 2rem';
            popup.style.borderRadius = '5px';
            popup.style.boxShadow = '0 2px 10px rgba(0,0,0,0.2)';
            popup.style.zIndex = '9999';
            popup.style.fontWeight = '600';
            popup.style.fontSize = '1rem';
            popup.style.userSelect = 'none';

            document.body.appendChild(popup);

            setTimeout(() => {
                popup.style.transition = 'opacity 0.5s';
                popup.style.opacity = '0';
            }, 3000);

            setTimeout(() => {
                popup.remove();
            }, 3500);
        }
    </script>
</body>

</html>