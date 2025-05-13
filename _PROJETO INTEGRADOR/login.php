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
  echo "Login bem-sucedido como $tipo. Bem-vindo, " . htmlspecialchars($usuario['nome']) . "!";
} else {
  echo "Email ou senha incorretos.";
}
?>
