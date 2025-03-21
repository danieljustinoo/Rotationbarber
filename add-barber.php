<?php
session_start();
include 'config.php';

if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'admin') {
    die(json_encode(['success' => false, 'message' => 'Acesso negado']));
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $nome = $_POST['nome'];
    $email = $_POST['email'];
    $senha = password_hash($_POST['senha'], PASSWORD_DEFAULT);

    // Verificar se o email já existe
    $sql_check = "SELECT * FROM usuarios WHERE email = ?";
    $stmt_check = $conn->prepare($sql_check);
    $stmt_check->bind_param("s", $email);
    $stmt_check->execute();
    $result_check = $stmt_check->get_result();

    if ($result_check->num_rows > 0) {
        echo json_encode(['success' => false, 'message' => 'Este email já está registrado']);
        exit();
    }

    // Inserir novo barbeiro
    $role = 'barbeiro';
    $sql = "INSERT INTO usuarios (nome, email, senha, role) VALUES (?, ?, ?, ?)";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("ssss", $nome, $email, $senha, $role);

    if ($stmt->execute()) {
        echo json_encode(['success' => true, 'message' => 'Barbeiro adicionado com sucesso']);
    } else {
        echo json_encode(['success' => false, 'message' => 'Erro ao adicionar barbeiro']);
    }

    $stmt->close();
    $stmt_check->close();
    $conn->close();
}
?>