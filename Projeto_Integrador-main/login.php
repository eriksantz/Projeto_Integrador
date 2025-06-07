<?php
require 'conexao.php';

$tipo = $_POST['tipo_usuario'];
$email = $_POST['email'];
$senha = $_POST['senha'];

if ($tipo === 'Gestor') {
  $sql = "SELECT * FROM gestores WHERE email = ?";
} elseif ($tipo === 'Cliente') {
  $sql = "SELECT * FROM clientes WHERE email = ?";
} else {
  die("Tipo de usuário inválido.");
}

$stmt = $pdo->prepare($sql);
$stmt->execute([$email]);
$usuario = $stmt->fetch(PDO::FETCH_ASSOC);

if ($usuario && password_verify($senha, $usuario['senha'])) {
  // Iniciar sessão
  session_start();
  $_SESSION['usuario_id'] = $usuario['id'];  // Armazenar o ID do usuário
  $_SESSION['nome'] = $usuario['nome'];      // Armazenar o nome do usuário
  $_SESSION['tipo_usuario'] = $tipo;         // Armazenar o tipo de usuário (Gestor ou Cliente)

  // Redirecionar para o painel adequado
  if ($tipo === 'Gestor') {
    header("Location: painel/painel_gestor.php");
  } elseif ($tipo === 'Cliente') {
    header("Location: painel/painel_cliente.php"); // Você ainda pode criar um painel para clientes
  }
  exit;
} else {
  echo "Email ou senha incorretos.";
}
?>
