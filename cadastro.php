<?php
$host = "localhost";
$db = "meubanco";
$user = "root";
$pass = "";

$conn = new mysqli($host, $user, $pass, $db);

if ($conn->connect_error) {
    die("Erro na conexão: " . $conn->connect_error);
}

$nome = $_POST['nome'];
$email = $_POST['email'];
$senha = password_hash($_POST['senha'], PASSWORD_DEFAULT); // Criptografar a senha
$tipo_usuario = $_POST['tipo_usuario'];
$empresa = isset($_POST['empresa']) ? $_POST['empresa'] : null;

$sql = "INSERT INTO usuarios (nome, email, senha, tipo_usuario, empresa)
        VALUES (?, ?, ?, ?, ?)";

$stmt = $conn->prepare($sql);
$stmt->bind_param("sssss", $nome, $email, $senha, $tipo_usuario, $empresa);

if ($stmt->execute()) {
    echo "Conta criada com sucesso!";
} else {
    echo "Erro ao criar conta: " . $stmt->error;
}

$stmt->close();
$conn->close();
?>
