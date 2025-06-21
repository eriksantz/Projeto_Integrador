<?php
session_start();
require '../conexao.php';

if (!isset($_SESSION['usuario_id']) || $_SESSION['tipo_usuario'] !== 'Cliente') {
    header('Location: ../login.php');
    exit;
}

$cliente_id_logado = $_SESSION['usuario_id'];
$nome = $_SESSION['nome'];

// 1. CORREÇÃO: Adicionado data_publicacao e redes_sociais à consulta SQL
$sql_postagens = "
    SELECT id, titulo, descricao, imagem, criado_em, status, feedback_cliente, data_publicacao, redes_sociais
    FROM postagens
    WHERE cliente_id = ?
    ORDER BY criado_em DESC
";

$stmt = $pdo->prepare($sql_postagens);
$stmt->execute([$cliente_id_logado]);
$resultado_postagens = $stmt->fetchAll(PDO::FETCH_ASSOC);

$usuario_id = $_SESSION['usuario_id'];
$tipo_usuario = strtolower($_SESSION['tipo_usuario']);
?>

<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Painel do Cliente</title>
    <link rel="icon" type="image/png" sizes="16x16" href="../imagens/fav_icon.png">
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;600&display=swap" rel="stylesheet" />
    <link href="https://fonts.googleapis.com/css2?family=Material+Symbols+Rounded" rel="stylesheet" />
    <link rel="stylesheet" href="sidebar.css">
    <link rel="stylesheet" href="estilos_diversos/viewcard.css">
    <link rel="stylesheet" href="responsividade.css">
    <style>
        :root {
            --cor-principal: #843af3; --cor-verde: #4ce68b; --cor-amarelo: #FFB300;
            --cor-vermelho: #ff3b3b; --cor-cinza: #6c757d; --cor-fundo: #f5f6fa;
            --cor-texto: #333; --cor-texto-claro: #555; --cor-borda: #f0f0f0; --cor-branca: #fff;
        }
        * { margin: 0; padding: 0; box-sizing: border-box; letter-spacing: 0.001rem; }
        body { font-family: 'Plus Jakarta Sans', sans-serif; background: var(--cor-fundo); color: var(--cor-texto); }
        .container { display: flex; min-height: 100vh; }
        .main-content { flex: 1; display: flex; flex-direction: column; padding: 2rem; }
        .header { display: flex; align-items: center; background: var(--cor-branca); padding: 1rem; border-radius: 1rem; box-shadow: 0 2px 4px rgba(0, 0, 0, 0.05); margin-bottom: 2rem; }
        .header h1 { flex: 1; }
        .user-info { display: flex; align-items: center; gap: 1rem; }
        .postagens-grid { display: flex; flex-wrap: wrap; gap: 1.5rem; }
        .postagem-card { background: var(--cor-branca); box-shadow: 0 4px 12px rgba(0, 0, 0, 0.08); border-radius: 16px; padding: 1.5rem; width: 340px; display: flex; flex-direction: column; transition: transform 0.2s ease, box-shadow 0.2s ease; }
        .postagem-card:hover { transform: translateY(-5px); box-shadow: 0 8px 16px rgba(0, 0, 0, 0.12); }
        .card-conteudo { flex-grow: 1; display: flex; flex-direction: column; margin-top: 1.5rem; gap: 1rem; }
        .postagem-card h3 { margin: 0 0 0.25rem 0; color: var(--cor-texto); }
        .postagem-card p { color: var(--cor-texto-claro); font-size: 0.95rem; }
        .texto-truncado { display: -webkit-box; -webkit-box-orient: vertical; overflow: hidden; text-overflow: ellipsis; -webkit-line-clamp: 3; line-clamp: 3; }
        .postagem-card img.miniatura, .postagem-card video.miniatura { width: 100%; max-width: 200px; height: 200px; object-fit: cover; border-radius: 8px !important; margin: auto; display: block; background-color: #f0f0f0; }
        .card-footer { display: flex; flex-direction: column; align-items: center; gap: 1rem; margin-top: 1rem; padding-top: 1rem; border-top: 1px solid #eee; width: 100%; }
        .data { font-size: 0.9rem; color: #999; }
        .form-feedback { width: 100%; display: flex; flex-direction: column; align-items: center; gap: 1rem; }
        .feedback-textarea { width: 100%; min-height: 80px; padding: 12px 16px; border-radius: 12px; border: 1.5px solid #e0e0e0; font-family: inherit; font-size: 0.95rem; color: var(--cor-texto-claro); background-color: #fdfdff; resize: vertical; transition: border-color 0.3s ease, box-shadow 0.3s ease; box-shadow: 0 1px 2px rgba(0,0,0,0.02); }
        .feedback-textarea::placeholder { color: #b0b0b0; }
        .feedback-textarea:focus { outline: none; border-color: var(--cor-principal); box-shadow: 0 0 0 3px rgba(132, 58, 243, 0.15); }
        .acoes-botoes { display: flex; gap: 0.75rem; flex-wrap: wrap; width: 100%; }
        .acoes-botoes button { flex-grow: 1; padding: 0.75rem; border: none; border-radius: 8px; color: white; font-weight: bold; cursor: pointer; display: flex; align-items: center; justify-content: center; gap: 0.5rem; font-family: 'Plus Jakarta Sans', sans-serif; transition: transform 0.2s ease, box-shadow 0.2s ease; }
        .acoes-botoes button:hover { transform: translateY(-2px); box-shadow: 0 2px 6px rgba(0, 0, 0, 0.15); }
        .btn-aprovar { background-color: var(--cor-verde); } .btn-revisar { background-color: var(--cor-principal); } .btn-reprovar { background-color: var(--cor-vermelho); }
        .status-badge { display: inline-block; padding: 6px 14px; border-radius: 20px; font-size: 0.8rem; font-weight: 700; color: white; text-align: center; }
        .status-aguardando-análise { background-color: var(--cor-cinza); } .status-revisar { background-color: var(--cor-amarelo); } .status-aprovado { background-color: var(--cor-verde); } .status-reprovado { background-color: var(--cor-vermelho); }
    </style>
</head>

<body>
    <div class="container">
        <aside class="sidebar expanded" id="sidebar">
            <div class="logo"> <img src="../imagens/logo_GAD_PAINEL.png" alt="Logo" style="width: 80%; height: auto;" /> </div>
            <nav class="menu-wrapper">
                <div class="menu">
                    <a href="painel_cliente.php" class="menu-item active"><span class="material-symbols-rounded">space_dashboard</span><span class="text">Postagens</span></a>
                    <a href="receber_convite.php" class="menu-item"><span class="material-symbols-rounded">forward_to_inbox</span><span class="text">Convites</span></a>
                    <a href="perfil.php" class="menu-item"><span class="material-symbols-rounded">person</span><span class="text">Perfil</span></a>
                    <a href="configuracoes.php" class="menu-item"><span class="material-symbols-rounded">settings</span><span class="text">Configurações</span></a>
                    
                </div>
                <div style="margin-top: auto; width: 100%;">
                    <a href="logout.php" class="menu-item logout"><span class="material-symbols-rounded">exit_to_app</span><span class="text">Sair</span></a>
                </div>
            </nav>
        </aside>

        <div class="sidebar-overlay" id="sidebarOverlay"></div>
        <main class="main-content">
            <header class="header">
                <button id="toggleBtn" class="toggle-btn"><span class="material-symbols-rounded" id="iconToggle">chevron_left</span></button>
                <h1>Postagens</h1>
                <div class="user-info">
                    <span><?= htmlspecialchars($nome) ?></span>
                    <img src="../avatar/gerar_avatar.php?id=<?= $usuario_id ?>&tipo=<?= $tipo_usuario ?>" alt="Avatar de <?= htmlspecialchars($nome) ?>" class="avatar">
                </div>
            </header>

            <section class="postagens-grid">
                <?php if (empty($resultado_postagens)): ?>
                    <div style="text-align: center; width: 100%; padding: 2rem;">
                        <img src="../imagens/sem_gestor_feedback.png" alt="Sem postagens" style="width: 200px; opacity: 0.6;" />
                        <p style="margin-top: 1rem; font-size: 1.1rem; color: #666;">Nenhuma postagem disponível do seu gestor ainda.</p>
                    </div>
                <?php else: ?>
                    <?php foreach ($resultado_postagens as $postagem): ?>
                        <?php $statusClass = 'status-' . strtolower(str_replace(' ', '-', $postagem['status'])); ?>
                        
                        <!-- 2. CORREÇÃO: Adicionados todos os data-attributes necessários -->
                        <?php
                        echo '<div class="postagem-card visualizavel" 
                                data-titulo="'.htmlspecialchars($postagem['titulo'], ENT_QUOTES).'"
                                data-descricao="'.htmlspecialchars($postagem['descricao'], ENT_QUOTES).'"
                                data-imagem="'.htmlspecialchars($postagem['imagem']).'"
                                data-datapub="'.(!empty($postagem['data_publicacao']) ? date('d/m/Y', strtotime($postagem['data_publicacao'])) : 'Não agendada').'"
                                data-redes="'.(!empty($postagem['redes_sociais']) ? htmlspecialchars($postagem['redes_sociais'], ENT_QUOTES) : 'Nenhuma').'"
                                data-feedback="'.htmlspecialchars($postagem['feedback_cliente'], ENT_QUOTES).'">';
                        ?>
                            <h3><?= htmlspecialchars($postagem['titulo']) ?></h3>
                            <p class="texto-truncado"><?= nl2br(htmlspecialchars($postagem['descricao'])) ?></p>

                            <div class="card-conteudo">
                                <?php if (!empty($postagem['imagem'])): ?>
                                    <?php
                                    $caminho_arquivo = '../uploads/' . htmlspecialchars($postagem['imagem']);
                                    $extensao = strtolower(pathinfo($caminho_arquivo, PATHINFO_EXTENSION));
                                    if ($extensao === 'mp4') {
                                        echo '<video src="' . $caminho_arquivo . '" muted loop class="miniatura"></video>';
                                    } else {
                                        echo '<img src="' . $caminho_arquivo . '" alt="Mídia da postagem" class="miniatura">';
                                    }
                                    ?>
                                <?php endif; ?>
                            </div>

                            <div class="card-footer">
                                <div class="data">Criado em: <?= date('d/m/Y H:i', strtotime($postagem['criado_em'])) ?></div>
                                <?php if ($postagem['status'] === 'Aguardando Análise'): ?>
                                    <form action="processar_feedback.php" method="POST" class="form-feedback">
                                        <input type="hidden" name="postagem_id" value="<?= $postagem['id'] ?>">
                                        <textarea name="feedback_texto" placeholder="Opcional: Deixe um comentário..." class="feedback-textarea"></textarea>
                                        <div class="acoes-botoes">
                                            <button type="submit" name="acao" value="Aprovado" class="btn-aprovar"><span class="material-symbols-rounded">thumb_up</span> Aprovar</button>
                                            <button type="submit" name="acao" value="Revisar" class="btn-revisar"><span class="material-symbols-rounded">edit</span> Pedir Alteração</button>
                                            <button type="submit" name="acao" value="Reprovado" class="btn-reprovar"><span class="material-symbols-rounded">thumb_down</span> Reprovar</button>
                                        </div>
                                    </form>
                                <?php else: ?>
                                    <span class="status-badge <?= $statusClass ?>"><?= htmlspecialchars($postagem['status']) ?></span>
                                <?php endif; ?>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </section>
        </main>
    </div>

    <!-- 3. ADICIONADO: HTML do Modal -->
    <div id="modal-visualizacao" class="modal-visualizacao">
        <div class="modal-visualizacao-conteudo">
            <button class="fechar-modal-visualizacao">&times;</button>
            <div class="modal-visualizacao-media" id="view-media-container"></div>
            <div class="modal-visualizacao-info">
                <h2 id="view-titulo"></h2>
                <p id="view-descricao"></p>
                <ul class="detalhes-lista">
                    <li><strong>Data de Publicação:</strong> <span id="view-data"></span></li>
                    <li><strong>Redes Sociais:</strong> <span id="view-redes"></span></li>
                </ul>
                <div id="view-feedback-box" class="feedback-box" style="display: none;">
                    <h3>Seu Feedback</h3>
                    <p id="view-feedback"></p>
                </div>
            </div>
        </div>
    </div>
    
    <script src="sidebar.js"></script>
    <script>
        document.addEventListener('DOMContentLoaded', () => {
            // --- Lógica do Sidebar ---

            // --- Lógica do Modal de Visualização ---
            const postagemGrid = document.querySelector('.postagens-grid');
            const modalView = document.getElementById('modal-visualizacao');
            
            if(postagemGrid && modalView) {
                const fecharModalViewBtn = modalView.querySelector('.fechar-modal-visualizacao');
                const viewMediaContainer = document.getElementById('view-media-container');
                const viewTitulo = document.getElementById('view-titulo');
                const viewDescricao = document.getElementById('view-descricao');
                const viewData = document.getElementById('view-data');
                const viewRedes = document.getElementById('view-redes');
                const viewFeedbackBox = document.getElementById('view-feedback-box');
                const viewFeedback = document.getElementById('view-feedback');

                const fecharModalVisualizacao = () => {
                    modalView.classList.remove('visivel');
                    const video = viewMediaContainer.querySelector('video');
                    if (video) video.pause();
                };
                
                postagemGrid.addEventListener('click', (e) => {
                    // Impede a abertura do modal se o clique for no formulário de feedback
                    if (e.target.closest('.form-feedback')) {
                        return;
                    }

                    const card = e.target.closest('.visualizavel');
                    if (card) {
                        const data = card.dataset;
                        
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
                                video.src = caminho;
                                video.controls = true;
                                video.autoplay = true;
                                video.loop = true;
                                viewMediaContainer.appendChild(video);
                            } else {
                                const img = document.createElement('img');
                                img.src = caminho;
                                img.alt = data.titulo;
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

                fecharModalViewBtn.addEventListener('click', fecharModalVisualizacao);
                modalView.addEventListener('click', (e) => {
                    if (e.target === modalView) {
                        fecharModalVisualizacao();
                    }
                });
            }
        });
    </script>
</body>
</html>
