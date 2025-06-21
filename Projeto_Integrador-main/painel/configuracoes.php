<?php
session_start();

// Verifica se o usuário está logado e se é do tipo permitido (gestor ou cliente)
if (!isset($_SESSION['usuario_id']) || !in_array(strtolower($_SESSION['tipo_usuario']), ['gestor', 'cliente'])) {
    header('Location: ../login.php');
    exit;
}

require_once '../conexao.php';

// Atribui variáveis de sessão para uso fácil
$nome = $_SESSION['nome'];
$usuario_id = $_SESSION['usuario_id'];
$tipo_usuario = strtolower($_SESSION['tipo_usuario']);

// --- LÓGICA DE FLASH MESSAGES (passo 1 da correção) ---
// Verifica se existe uma mensagem na sessão, a exibe e depois a apaga.
$mensagem_sucesso = '';
if (isset($_SESSION['mensagem_sucesso'])) {
    $mensagem_sucesso = $_SESSION['mensagem_sucesso'];
    unset($_SESSION['mensagem_sucesso']); // Limpa a mensagem para não aparecer de novo
}

$mensagem_erro = '';
if (isset($_SESSION['mensagem_erro'])) {
    $mensagem_erro = $_SESSION['mensagem_erro'];
    unset($_SESSION['mensagem_erro']); // Limpa a mensagem para não aparecer de novo
}


// Processa o formulário apenas se o método for POST
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    // --- LÓGICA PARA ALTERAR A SENHA ---
    if (isset($_POST['action']) && $_POST['action'] === 'mudar_senha') {
        $senha_atual = $_POST['senha_atual'];
        $nova_senha = $_POST['nova_senha'];
        $confirmar_nova_senha = $_POST['confirmar_nova_senha'];
        $tabela_usuario = ($tipo_usuario === 'gestor') ? 'gestores' : 'clientes';

        if (empty($senha_atual) || empty($nova_senha) || empty($confirmar_nova_senha)) {
            $_SESSION['mensagem_erro'] = "Todos os campos para alterar a senha são obrigatórios.";
        } elseif ($nova_senha !== $confirmar_nova_senha) {
            $_SESSION['mensagem_erro'] = "A nova senha e a confirmação não correspondem.";
        } elseif (strlen($nova_senha) < 6) {
             $_SESSION['mensagem_erro'] = "A nova senha deve ter no mínimo 6 caracteres.";
        } else {
            try {
                $stmt = $pdo->prepare("SELECT senha FROM $tabela_usuario WHERE id = ?");
                $stmt->execute([$usuario_id]);
                $usuario = $stmt->fetch(PDO::FETCH_ASSOC);

                if ($usuario && password_verify($senha_atual, $usuario['senha'])) {
                    $hash_nova_senha = password_hash($nova_senha, PASSWORD_DEFAULT);
                    $update_stmt = $pdo->prepare("UPDATE $tabela_usuario SET senha = ? WHERE id = ?");
                    if ($update_stmt->execute([$hash_nova_senha, $usuario_id])) {
                        $_SESSION['mensagem_sucesso'] = "Senha alterada com sucesso!";
                    } else {
                        $_SESSION['mensagem_erro'] = "Ocorreu um erro ao atualizar sua senha.";
                    }
                } else {
                    $_SESSION['mensagem_erro'] = "A senha atual está incorreta.";
                }
            } catch (PDOException $e) {
                $_SESSION['mensagem_erro'] = "Erro de banco de dados: " . $e->getMessage();
            }
        }
        
        // --- Redirecionamento (passo 2 da correção) ---
        // Redireciona para a mesma página para evitar reenvio do formulário
        header('Location: configuracoes.php');
        exit;
    }

    // --- LÓGICA PARA EXCLUIR A CONTA ---
    if (isset($_POST['action']) && $_POST['action'] === 'excluir_conta') {
        try {
            $pdo->beginTransaction();

            if ($tipo_usuario === 'gestor') {
                $stmt_clientes = $pdo->prepare(
                    "SELECT c.id FROM clientes c JOIN convites co ON c.convite_id = co.id WHERE co.gestor_id = ?"
                );
                $stmt_clientes->execute([$usuario_id]);
                $clientes_ids_para_desvincular = $stmt_clientes->fetchAll(PDO::FETCH_COLUMN);

                if (!empty($clientes_ids_para_desvincular)) {
                    $placeholders = implode(',', array_fill(0, count($clientes_ids_para_desvincular), '?'));
                    $stmt_unink = $pdo->prepare("UPDATE clientes SET convite_id = NULL WHERE id IN ($placeholders)");
                    $stmt_unink->execute($clientes_ids_para_desvincular);
                }

                $stmt_del_postagens = $pdo->prepare("DELETE FROM postagens WHERE gestor_id = ?");
                $stmt_del_postagens->execute([$usuario_id]);

                $stmt_del_convites = $pdo->prepare("DELETE FROM convites WHERE gestor_id = ?");
                $stmt_del_convites->execute([$usuario_id]);
                
                $stmt_del_gestor = $pdo->prepare("DELETE FROM gestores WHERE id = ?");
                $stmt_del_gestor->execute([$usuario_id]);

            } else { // Se for um cliente
                $stmt_convite_id = $pdo->prepare("SELECT convite_id FROM clientes WHERE id = ?");
                $stmt_convite_id->execute([$usuario_id]);
                $convite_id = $stmt_convite_id->fetchColumn();

                if ($convite_id) {
                    $stmt_reset_convite = $pdo->prepare("UPDATE convites SET usado = FALSE WHERE id = ?");
                    $stmt_reset_convite->execute([$convite_id]);
                }
                
                $stmt_del_cliente = $pdo->prepare("DELETE FROM clientes WHERE id = ?");
                $stmt_del_cliente->execute([$usuario_id]);
            }
            
            $pdo->commit();

            session_unset();
            session_destroy();
            // Esta ação já tem um redirecionamento, então está correta.
            header('Location: ../login.php?status=excluido');
            exit;

        } catch (PDOException $e) {
            $pdo->rollBack();
            // Salva a mensagem de erro na sessão e redireciona
            $_SESSION['mensagem_erro'] = "Erro ao excluir a conta: " . $e->getMessage();
            header('Location: configuracoes.php');
            exit;
        }
    }
}
?>

<!DOCTYPE html>
<html lang="pt-br">

<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Configurações</title>
    <link rel="icon" type="image/png" sizes="16x16" href="../imagens/fav_icon.png">
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;600&display=swap" rel="stylesheet" />
    <link href="https://fonts.googleapis.com/css2?family=Material+Symbols+Rounded" rel="stylesheet" />
    <link rel="stylesheet" href="painel_gestor.css">
    <link rel="stylesheet" href="sidebar.css">
    <link rel="stylesheet" href="profile.css">
    <link rel="stylesheet" href="responsividade.css">
    <style>
        :root {
            --cor-primaria: #843af3;
            --cor-secundaria: #4ce68b;
            --cor-perigo-fundo: #ef4444;
            --cor-perigo-fundo-hover: #dc2626;
            --cor-perigo-texto: #ffffff;
            --cor-perigo-borda-icone: #f8b4b4;
            --cor-perigo-icone: #ef4444;
            --cor-cancelar-fundo: #e5e7eb;
            --cor-cancelar-fundo-hover: #d1d5db;
            --cor-cancelar-texto: #374151;
        }

        * {
            letter-spacing: 0.001rem;
        }
        
        /* --- MELHORIAS GERAIS E CARDS --- */
        .content-body {
            padding: 1.5rem 2rem;
        }

        .settings-card {
            background-color: #fff;
            padding: 25px;
            border-radius: 12px;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.08);
            margin-bottom: 2rem;
        }

        .settings-card h2 {
            margin-top: 0;
            margin-bottom: 20px;
            font-size: 1.25rem;
            font-weight: 700;
            color: #333;
            border-bottom: 1px solid #eee;
            padding-bottom: 10px;
        }

        /* --- MELHORIAS NO FORMULÁRIO --- */
        .form-group {
            margin-bottom: 1rem;
        }

        .form-group label {
            display: block;
            margin-bottom: 6px;
            font-weight: 600;
            color: #4a5568;
            font-size: 0.9rem;
        }

        .form-group input {
            width: 100%;
            padding: 12px;
            border: 1px solid #ccc;
            border-radius: 8px;
            font-family: 'Plus Jakarta Sans', sans-serif;
            font-size: 1rem;
            box-sizing: border-box;
            transition: border-color 0.2s ease, box-shadow 0.2s ease;
        }

        .form-group input:focus,
        .form-group input:focus-visible {
            outline: none;
            border-color: var(--cor-primaria);
            box-shadow: 0 0 0 3px rgba(132, 58, 243, 0.2);
        }

        /* --- MELHORIAS NOS BOTÕES --- */
        .btn {
            padding: 12px 24px;
            border: none;
            border-radius: 8px;
            cursor: pointer;
            font-size: 1rem;
            font-weight: 700;
            font-family: 'Plus Jakarta Sans', sans-serif;
            text-decoration: none;
            display: inline-block;
            text-align: center;
            transition: background-color 0.2s ease, transform 0.2s ease, box-shadow 0.2s ease;
        }

        .btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.15);
        }

        .btn:active {
            transform: translateY(0);
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
        }

        .btn-primary {
            background-color: var(--cor-primaria);
            color: white;
        }

        .btn-primary:hover {
            background-color: #4ce68b;
        }

        .btn-danger {
            background-color: var(--cor-perigo-fundo);
            color: white;
            margin-top: 1rem;
        }

        .btn-danger:hover {
            background-color: var(--cor-perigo-fundo-hover);
        }

        /* --- ZONA DE PERIGO --- */
        .danger-zone {
            border-left: 4px solid var(--cor-perigo-fundo);
            background-color: #fff5f5;
            padding: 20px;
        }

        .danger-zone p {
            margin-top: 0;
            color: #58151c;
        }

        /* --- MENSAGENS DE FEEDBACK --- */
        .mensagem {
            padding: 15px;
            margin-bottom: 20px;
            border-radius: 8px;
            color: #fff;
            text-align: center;
            font-weight: 600;
        }

        .mensagem-sucesso {
            background-color: #4ce68b;
        }

        .mensagem-erro {
            background-color: #ef4444;
        }

        /* --- POPUP DE EXCLUSÃO --- */
        .popupdelete-overlay {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background-color: rgba(17, 24, 39, 0.6);
            display: flex;
            justify-content: center;
            align-items: center;
            z-index: 1002;
            visibility: hidden;
            opacity: 0;
            transition: opacity 0.3s ease, visibility 0s 0.3s;
        }

        .popupdelete-overlay.popupdelete-visivel {
            visibility: visible;
            opacity: 1;
            transition: opacity 0.3s ease;
        }

        .popupdelete-conteudo {
            background: #ffffff;
            padding: 32px;
            border-radius: 16px;
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.3);
            text-align: center;
            width: 90%;
            max-width: 400px;
            position: relative;
            transform: scale(0.9);
            transition: transform 0.3s ease;
        }

        .popupdelete-overlay.popupdelete-visivel .popupdelete-conteudo {
            transform: scale(1);
        }

        .popupdelete-fechar {
            position: absolute;
            top: 16px;
            right: 16px;
            background: none;
            border: none;
            cursor: pointer;
            color: #9ca3af;
            padding: 0;
            display: flex;
            transition: color 0.5s ease;
        }

        .popupdelete-fechar:hover {
            color: #656668;
        }

        .popupdelete-fechar .material-symbols-rounded {
            font-size: 28px;
        }

        .popupdelete-icone {
            width: 100px;
            height: 100px;
            margin: 0 auto 16px auto;
            display: flex;
            justify-content: center;
            align-items: center;
        }

        .popupdelete-cor-icone-perigo {
            border: 4px solid var(--cor-perigo-borda-icone);
            border-radius: 50%;
            color: var(--cor-perigo-icone);
        }

        .popupdelete-icone .material-symbols-rounded {
            font-size: 85px;
            font-variation-settings: 'FILL' 1;
        }

        .popupdelete-conteudo h2 {
            margin-top: 0;
            margin-bottom: 8px;
            color: #111827;
            font-family: 'Plus Jakarta Sans', sans-serif;
            font-weight: 700;
            font-size: 1.5rem;
        }

        .popupdelete-conteudo p {
            margin-top: 0;
            margin-bottom: 24px;
            color: #6b7280;
            font-family: 'Plus Jakarta Sans', sans-serif;
            line-height: 1.5;
        }

        .popupdelete-botoes {
            display: flex;
            gap: 16px;
            justify-content: center;
        }

        .popupdelete-botao-cancelar {
            background-color: var(--cor-cancelar-fundo);
            color: var(--cor-cancelar-texto);
        }

        .popupdelete-botao-cancelar:hover {
            background-color: var(--cor-cancelar-fundo-hover);
        }

        .popupdelete-cor-botao-perigo {
            background-color: var(--cor-perigo-fundo);
            color: var(--cor-perigo-texto);
        }

        .popupdelete-cor-botao-perigo:hover {
            background-color: var(--cor-perigo-fundo-hover);
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
                    <?php if ($tipo_usuario === 'gestor'): ?>
                        <a href="painel_gestor.php" class="menu-item"><span class="material-symbols-rounded">space_dashboard</span><span class="text">Visão Geral</span></a>
                        <a href="criar_convite.php" class="menu-item"><span class="material-symbols-rounded">forward_to_inbox</span><span class="text">Convites</span></a>
                    <?php else: // Cliente 
                    ?>
                        <a href="painel_cliente.php" class="menu-item"><span class="material-symbols-rounded">space_dashboard</span><span class="text">Postagens</span></a>
                        <a href="receber_convite.php" class="menu-item"><span class="material-symbols-rounded">forward_to_inbox</span><span class="text">Convites</span></a>
                    <?php endif; ?>
                    <a href="perfil.php" class="menu-item"><span class="material-symbols-rounded">Person</span><span class="text">Perfil</span></a>
                    <a href="configuracoes.php" class="menu-item active"><span class="material-symbols-rounded">settings</span><span class="text">Configurações</span></a>
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
                <h1>Configurações</h1>
                <div class="user-info">
                    <span><?= htmlspecialchars($nome) ?></span>
                    <img src="../avatar/gerar_avatar.php?id=<?= $usuario_id ?>&tipo=<?= $tipo_usuario ?>" alt="Avatar de <?= htmlspecialchars($nome) ?>" class="avatar">
                </div>
            </header>

            <div class="content-body">
                <?php if ($mensagem_sucesso): ?><div class="mensagem mensagem-sucesso"><?= $mensagem_sucesso ?></div><?php endif; ?>
                <?php if ($mensagem_erro): ?><div class="mensagem mensagem-erro"><?= $mensagem_erro ?></div><?php endif; ?>

                <div class="settings-card">
                    <h2>Alterar Senha</h2>
                    <form method="post" action="configuracoes.php">
                        <input type="hidden" name="action" value="mudar_senha">
                        <div class="form-group">
                            <label for="senha_atual">Senha Atual</label>
                            <input type="password" id="senha_atual" name="senha_atual" required>
                        </div>
                        <div class="form-group">
                            <label for="nova_senha">Nova Senha (mínimo 6 caracteres)</label>
                            <input type="password" id="nova_senha" name="nova_senha" required minlength="6">
                        </div>
                        <div class="form-group">
                            <label for="confirmar_nova_senha">Confirmar Nova Senha</label>
                            <input type="password" id="confirmar_nova_senha" name="confirmar_nova_senha" required>
                        </div>
                        <button type="submit" class="btn btn-primary">Salvar Nova Senha</button>
                    </form>
                </div>

                <div class="settings-card danger-zone">
                    <h2>Exclusão de Conta:</h2>
                    <p>A exclusão da conta é permanente. Todos os seus dados, convites (vinculados ou não) e postagens serão apagados.</p>
                    <button type="button" id="abrirPopupDeleteBtn" class="btn btn-danger">Excluir Minha Conta</button>
                </div>
            </div>
        </main>
    </div>

    <!-- HTML DO POPUP DE EXCLUSÃO -->
    <div class="popupdelete-overlay" id="popupDelete">
        <div class="popupdelete-conteudo">
            <button class="popupdelete-fechar" id="fecharPopupDeleteBtn"><span class="material-symbols-rounded">close</span></button>
            <div class="popupdelete-icone popupdelete-cor-icone-perigo">
                <span class="material-symbols-rounded">delete_forever</span>
            </div>
            <h2>Excluir Conta</h2>
            <p>Você tem certeza absoluta? Esta ação não pode ser desfeita. Todos os seus dados serão apagados para sempre.</p>
            <div class="popupdelete-botoes">
                <button type="button" class="btn popupdelete-botao-cancelar" id="cancelarPopupDeleteBtn">Cancelar</button>
                <form method="post" action="configuracoes.php" style="margin: 0;">
                    <input type="hidden" name="action" value="excluir_conta">
                    <button type="submit" class="btn popupdelete-cor-botao-perigo">Excluir Conta</button>
                </form>
            </div>
        </div>
    </div>

    <script src="sidebar.js"></script>
    <script>

        // --- SCRIPT PARA CONTROLAR O POPUP DE EXCLUSÃO ---
        const popupDelete = document.getElementById('popupDelete');
        const abrirPopupDeleteBtn = document.getElementById('abrirPopupDeleteBtn');
        const fecharPopupDeleteBtn = document.getElementById('fecharPopupDeleteBtn');
        const cancelarPopupDeleteBtn = document.getElementById('cancelarPopupDeleteBtn');

        abrirPopupDeleteBtn.addEventListener('click', () => {
            popupDelete.classList.add('popupdelete-visivel');
        });

        const fecharPopup = () => {
            popupDelete.classList.remove('popupdelete-visivel');
        };

        fecharPopupDeleteBtn.addEventListener('click', fecharPopup);
        cancelarPopupDeleteBtn.addEventListener('click', fecharPopup);

        // Opcional: fechar o popup se clicar fora do conteúdo
        popupDelete.addEventListener('click', (event) => {
            if (event.target === popupDelete) {
                fecharPopup();
            }
        });
    </script>
</body>

</html>