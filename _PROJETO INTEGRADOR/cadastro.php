<?php
require 'conexao.php';

$tipo = $_POST['tipo_usuario'];
$nome = $_POST['nome'];
$email = $_POST['email'];
$senha = password_hash($_POST['senha'], PASSWORD_DEFAULT);

if ($tipo === 'Gestor') {
  $cnpj = $_POST['empresa'] ?? '';
  $sql = "INSERT INTO gestores (nome, email, senha, cnpj) VALUES (?, ?, ?, ?)";
  $stmt = $pdo->prepare($sql);
  $stmt->execute([$nome, $email, $senha, $cnpj]);
  header("Location: login.html?email=" . urlencode($email));
  exit;
} elseif ($tipo === 'Cliente') {
  $sql = "INSERT INTO clientes (nome, email, senha) VALUES (?, ?, ?)";
  $stmt = $pdo->prepare($sql);
  $stmt->execute([$nome, $email, $senha]);
  header("Location: login.html?email=" . urlencode($email));
  exit;
} else {
  echo "Tipo de usuário inválido.";
}
?>
