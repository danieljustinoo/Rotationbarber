<?php
include 'config.php';

// Verificar ligação com a base de dados
if ($conn->connect_error) {
    die(json_encode(['success' => false, 'message' => 'Erro na ligação à base de dados']));
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $nome = trim($_POST['nome']);
    $email = trim($_POST['email']);
    $senha = password_hash($_POST['senha'], PASSWORD_DEFAULT); // Criptografar a palavra-passe
    $telefone = isset($_POST['telefone']) ? trim($_POST['telefone']) : null;

    // Validar o número de telefone (se fornecido)
    if ($telefone) {
        // Garantir que o telefone contém apenas números e tem pelo menos 9 dígitos
        if (!preg_match("/^[0-9]{9,}$/", $telefone)) {
            echo json_encode(['success' => false, 'message' => 'Número de telefone inválido! Deve conter apenas números e ter pelo menos 9 dígitos.']);
            exit();
        }
    }

    // Verificar se o email já existe
    $sql_check = "SELECT * FROM usuarios WHERE email = ?";
    $stmt_check = $conn->prepare($sql_check);
    $stmt_check->bind_param("s", $email);
    $stmt_check->execute();
    $result_check = $stmt_check->get_result();

    if ($result_check->num_rows > 0) {
        echo json_encode(['success' => false, 'message' => 'Este email já está registado!']);
        $stmt_check->close();
        $conn->close();
        exit();
    }

    // Inserir novo utilizador (incluindo o telefone)
    $sql = "INSERT INTO usuarios (nome, email, senha, telefone, role) VALUES (?, ?, ?, ?, 'cliente')";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("ssss", $nome, $email, $senha, $telefone);

    if ($stmt->execute()) {
        echo json_encode(['success' => true, 'message' => 'Registo bem-sucedido! Faça login.']);
    } else {
        echo json_encode(['success' => false, 'message' => 'Erro ao registar!']);
    }

    $stmt->close();
    $stmt_check->close();
    $conn->close();
    exit();
}
?>