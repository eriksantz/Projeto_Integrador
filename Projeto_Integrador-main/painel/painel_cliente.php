<?php
session_start();
require '../conexao.php';

if (!isset($_SESSION['usuario_id']) || $_SESSION['tipo_usuario'] !== 'Cliente') {
    header('Location: ../login.html');
    exit;
}

$cliente_id_logado = $_SESSION['usuario_id']; // Pega o ID do cliente da sessão
$nome = $_SESSION['nome'];

// NOVA LÓGICA (MUITO MAIS SIMPLES E CORRETA)
// Busca APENAS as postagens onde o 'cliente_id' é o do cliente logado.
$sql_postagens = "
    SELECT id, titulo, descricao, imagem, criado_em, status, feedback_cliente
    FROM postagens
    WHERE cliente_id = ?
    ORDER BY criado_em DESC
";

$stmt = $pdo->prepare($sql_postagens);
$stmt->execute([$cliente_id_logado]); // Usa o ID do cliente logado
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
    <style>
        :root {
            --cor-principal: #843af3;
            --cor-verde: #4ce68b;
            --cor-amarelo: #FFB300;
            --cor-vermelho: #ff3b3b;
            --cor-cinza: #6c757d;
            --cor-fundo: #f5f6fa;
            --cor-texto: #333;
            --cor-texto-claro: #555;
            --cor-borda: #f0f0f0;
            --cor-branca: #fff;
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Plus Jakarta Sans', sans-serif;
            background: var(--cor-fundo);
            color: var(--cor-texto);
        }

        .container {
            display: flex;
            min-height: 100vh;
        }

        .main-content {
            flex: 1;
            display: flex;
            flex-direction: column;
            padding: 2rem;
            /* Se a sidebar estiver fixa, adicione o margin-left aqui */
        }

        .header {
            display: flex;
            align-items: center;
            background: var(--cor-branca);
            padding: 1rem 2rem;
            border-radius: 1rem;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.05);
            margin-bottom: 2rem;
        }

        .header h1 {
            flex: 1;
            letter-spacing: 0.001em; 
        }

        .user-info {
            display: flex;
            align-items: center;
            gap: 1rem;
        }

        .postagens-grid {
            display: flex;
            flex-wrap: wrap;
            gap: 1.5rem;
        }

        .postagem-card {
            background: var(--cor-branca);
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.08);
            border-radius: 16px;
            padding: 1.5rem;
            width: 340px;
            display: flex;
            flex-direction: column;
            transition: transform 0.2s ease, box-shadow 0.2s ease;
        }

        .postagem-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 8px 16px rgba(0, 0, 0, 0.12);
        }

        .card-conteudo {
            flex-grow: 1;
            display: flex;
            flex-direction: column;
            margin-top: 0.5rem;
            gap: 1rem;
        }

        .postagem-card h3 {
            margin: 0 0 0.25rem 0;
            color: var(--cor-texto);
        }

        .postagem-card p {
            color: var(--cor-texto-claro);
            font-size: 0.95rem;
        }

        .texto-truncado {
            display: -webkit-box;
            -webkit-box-orient: vertical;
            overflow: hidden;
            text-overflow: ellipsis;
            -webkit-line-clamp: 3;
            line-clamp: 3;
        }

        .postagem-card img.miniatura {
            width: 100%;
            max-width: 200px;
            height: 200px;
            border-radius: 8px !important;
            margin-top: auto;
            margin-bottom: auto;
            margin-left: auto;
            margin-right: auto;
        }

        .card-footer {
            display: flex;
            flex-direction: column;
            align-items: center;
            gap: 0.5rem;
            margin-top: 1rem;
            padding-top: 0.5rem;
            border-top: 1px solid #eee;
        }

        .data {
            font-size: 0.9rem;
            color: #999;
        }

        .feedback-textarea {
            width: 100%;
            padding: 0.75rem;
            border-radius: 8px;
            border: 1px solid #ccc;
            min-height: 80px;
            margin-bottom: 1rem;
            font-family: inherit;
            resize: vertical;
        }

        .acoes-botoes {
            display: flex;
            gap: 0.5rem;
            flex-wrap: wrap;
            
        }

        .acoes-botoes button {
            flex-grow: 1;
            padding: 0.75rem;
            border: none;
            border-radius: 8px;
            color: white;
            font-weight: bold;
            cursor: pointer;
            transition: opacity 0.2s;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 0.5rem;
            font-family: 'Plus Jakarta Sans', sans-serif;
        }

        .acoes-botoes button:hover {
            transform: translateY(-2px);
            box-shadow: 0 2px 6px rgba(0, 0, 0, 0.15);
            transition: transform 0.2s ease-in-out, box-shadow 0.2s ease-in-out;
        }

        .btn-aprovar {
            background-color: var(--cor-verde);
        }

        .btn-revisar {
            background-color: var(--cor-principal);
            color: var(--cor-texto);
        }

        .btn-reprovar {
            background-color: var(--cor-vermelho);
        }

        .status-badge {
            display: inline-block;
            padding: 6px 14px;
            border-radius: 20px;
            font-size: 0.8rem;
            font-weight: 700;
            color: white;
            text-align: center;
        }

        .status-aguardando-análise {
            background-color: var(--cor-cinza);
        }

        .status-revisar {
            background-color: var(--cor-amarelo);
        }

        .status-aprovado {
            background-color: var(--cor-verde);
        }

        .status-reprovado {
            background-color: var(--cor-vermelho);
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
                    <a href="painel_cliente.php" class="menu-item active"><span class="material-symbols-rounded">space_dashboard</span><span class="text">Visão Geral</span></a>

                    <a href="receber_convite.php" class="menu-item"><span class="material-symbols-rounded">forward_to_inbox</span><span class="text">Convites</span></a>


                    <a href="perfil.php" class="menu-item"><span class="material-symbols-rounded">person</span><span class="text">Perfil</span></a>
                    <a href="#" class="menu-item"><span class="material-symbols-rounded">settings</span><span class="text">Configurações</span></a>
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
                    <img
                        src="../avatar/gerar_avatar.php?id=<?= $usuario_id ?>&tipo=<?= $tipo_usuario ?>" 
                        alt="Avatar de <?= htmlspecialchars($nome) ?>"
                        class="avatar">
                </div>
            </header>

            <section class="postagens-grid">
                <?php if (!empty($resultado_postagens)): ?>
                    <?php foreach ($resultado_postagens as $postagem): ?>

                        <?php
                        // Variavel para a classe de status, usada no rodapé
                        $statusClass = 'status-' . strtolower(str_replace(' ', '-', $postagem['status']));
                        ?>

                        <div class="postagem-card">

                            <h3><?= htmlspecialchars($postagem['titulo']) ?></h3>
                            <p class="texto-truncado"><?= nl2br(htmlspecialchars($postagem['descricao'])) ?></p>

                            <div class="card-conteudo">
                                <?php if (!empty($postagem['imagem'])): ?>
                                    <img src="../uploads/<?= htmlspecialchars($postagem['imagem']) ?>" alt="Imagem da postagem" class="miniatura">
                                <?php endif; ?>
                            </div>

                            <div class="card-footer">
                                <div class="data">Criado em: <?= date('d/m/Y H:i', strtotime($postagem['criado_em'])) ?></div>

                                <?php
                                // Lógica para mostrar o formulário de feedback OU o status final
                                if ($postagem['status'] === 'Aguardando Análise'):
                                ?>
                                    <form action="processar_feedback.php" method="POST" class="form-feedback">
                                        <input type="hidden" name="postagem_id" value="<?= $postagem['id'] ?>">
                                        <textarea name="feedback_texto" placeholder="Opcional: Deixe um comentário..." class="feedback-textarea"></textarea>
                                        <div class="acoes-botoes">
                                            <button type="submit" name="acao" value="Aprovado" class="btn-aprovar">
                                                <span class="material-symbols-rounded">thumb_up</span> Aprovar
                                            </button>
                                            <button type="submit" name="acao" value="Revisar" class="btn-revisar">
                                                <span class="material-symbols-rounded">edit</span> Pedir Alteração
                                            </button>
                                            <button type="submit" name="acao" value="Reprovado" class="btn-reprovar">
                                                <span class="material-symbols-rounded">thumb_down</span> Reprovar
                                            </button>
                                        </div>
                                    </form>
                                <?php else: ?>
                                    <span class="status-badge <?= $statusClass ?>"><?= htmlspecialchars($postagem['status']) ?></span>
                                <?php endif; // Fim do if/else do status 
                                ?>
                            </div>
                        </div> <?php endforeach; // Fim do loop, agora no lugar certo 
                                ?>

                <?php else: ?>
                    <div style="text-align: center; width: 100%; padding: 2rem;">
                        <img src="../imagens/sem_gestor_feedback.png" alt="Sem postagens" style="width: 200px; opacity: 0.6;" />
                        <p style="margin-top: 1rem; font-size: 1.1rem; color: #666;">
                            Nenhuma postagem disponível do seu gestor ainda.
                        </p>
                    </div>
                <?php endif; // Fim do if (!empty) 
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