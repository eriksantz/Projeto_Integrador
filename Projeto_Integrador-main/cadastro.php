<?php
require 'conexao.php';
session_start();

$tipo = $_POST['tipo_usuario'];
$nome = $_POST['nome'];
$email = $_POST['email'];
$senha = password_hash($_POST['senha'], PASSWORD_DEFAULT);

if ($tipo === 'Gestor') {
  $cnpj = $_POST['empresa'] ?? '';
  $sql = "INSERT INTO gestores (nome, email, senha, cnpj) VALUES (?, ?, ?, ?)";
  $stmt = $pdo->prepare($sql);
  $stmt->execute([$nome, $email, $senha, $cnpj]);


  $id = $pdo->lastInsertId();


  $_SESSION['usuario_id'] = $id;
  $_SESSION['nome'] = $nome;
  $_SESSION['tipo_usuario'] = 'Gestor';


  header("Location: painel/painel_gestor.php");
  exit;
} elseif ($tipo === 'Cliente') {
  $sql = "INSERT INTO clientes (nome, email, senha) VALUES (?, ?, ?)";
  $stmt = $pdo->prepare($sql);
  $stmt->execute([$nome, $email, $senha]);


  $id = $pdo->lastInsertId();


  $_SESSION['usuario_id'] = $id;
  $_SESSION['nome'] = $nome;
  $_SESSION['tipo_usuario'] = 'Cliente';


  header("Location: painel/painel_cliente.php");
  exit;
} else {
  echo "Tipo de usuário inválido.";
}
