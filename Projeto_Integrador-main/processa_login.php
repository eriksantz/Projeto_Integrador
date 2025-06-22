<?php
session_start();
require_once 'conexao.php';

// Redireciona se o acesso não for via POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: login.php');
    exit;
}

// 1. Pega TODOS os dados do formulário primeiro
$email = $_POST['email'];
$senha = $_POST['senha'];
$tipo_usuario_original = $_POST['tipo_usuario']; // Ex: 'Cliente' ou 'Gestor'

// 2. Usa strtolower() APENAS para a lógica interna de escolher a tabela
$tipo_usuario_logica = strtolower($tipo_usuario_original); 
$tabela = ($tipo_usuario_logica === 'gestor') ? 'gestores' : 'clientes';

try {
    // Busca o usuário pelo e-mail na tabela correta
    $stmt = $pdo->prepare("SELECT * FROM $tabela WHERE email = ?");
    $stmt->execute([$email]);
    $usuario = $stmt->fetch(PDO::FETCH_ASSOC);

    // Função para tratar falhas de login de forma segura
    function falhaLogin($pdo, $tabela, $usuario) {
        $mensagem_generica = "Email, senha ou tipo de usuário incorretos.";

        if ($usuario) {
            $tentativas = $usuario['tentativas_login'] + 1;
            $bloqueio_sql = "";
            
            if ($tentativas >= 3) {
                $tempo_bloqueio = date('Y-m-d H:i:s', time() + 8);
                $bloqueio_sql = ", bloqueado_ate = :bloqueado_ate";
                $mensagem_generica = "Muitas tentativas falhas. Sua conta foi bloqueada por 8 segundos.";
            }

            $sql_update = "UPDATE $tabela SET tentativas_login = :tentativas $bloqueio_sql WHERE id = :id";
            $stmt_update = $pdo->prepare($sql_update);
            $stmt_update->bindValue(':tentativas', $tentativas, PDO::PARAM_INT);
            $stmt_update->bindValue(':id', $usuario['id'], PDO::PARAM_INT);

            if ($tentativas >= 3) {
                $stmt_update->bindValue(':bloqueado_ate', $tempo_bloqueio);
            }
            $stmt_update->execute();
        }

        $_SESSION['erro_login'] = $mensagem_generica;
        header('Location: login.php');
        exit;
    }

    // Se o usuário não for encontrado
    if (!$usuario) {
        falhaLogin($pdo, $tabela, null);
    }
    
    // Verifica se a conta está bloqueada
    if ($usuario['bloqueado_ate'] && strtotime($usuario['bloqueado_ate']) > time()) {
        $tempo_restante = strtotime($usuario['bloqueado_ate']) - time();
        $_SESSION['erro_login'] = "Sua conta está bloqueada. Tente novamente em $tempo_restante segundos.";
        header('Location: login.php');
        exit;
    }

    // Verifica a senha
    if (password_verify($senha, $usuario['senha'])) {
        // --- SUCESSO NO LOGIN ---
        // Zera as tentativas e o bloqueio
        $stmt_sucesso = $pdo->prepare("UPDATE $tabela SET tentativas_login = 0, bloqueado_ate = NULL WHERE id = ?");
        $stmt_sucesso->execute([$usuario['id']]);

        // Guarda as informações na sessão
        session_regenerate_id(true);
        $_SESSION['usuario_id'] = $usuario['id'];
        $_SESSION['nome'] = $usuario['nome'];
        // Salva o tipo de usuário com a primeira letra maiúscula, como veio do form
        $_SESSION['tipo_usuario'] = $tipo_usuario_original; 

        // Redireciona para o painel correto
        $dashboard = ($tipo_usuario_logica === 'gestor') ? 'painel_gestor.php' : 'painel_cliente.php';
        header("Location: painel/$dashboard");
        exit;

    } else {
        // Senha incorreta
        falhaLogin($pdo, $tabela, $usuario);
    }

} catch (PDOException $e) {
    // error_log("Erro de login: " . $e->getMessage()); 
    $_SESSION['erro_login'] = "Ocorreu um erro no servidor. Tente novamente.";
    header('Location: login.php');
    exit;
}
