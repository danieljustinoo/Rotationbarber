<?php
session_start();
include 'config.php';

if ($conn->connect_error) {
    die(json_encode(['success' => false, 'message' => 'Erro na conexão com o banco de dados']));
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $email = $_POST['email'];
    $senha = $_POST['senha'];

    $sql = "SELECT * FROM usuarios WHERE email = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows > 0) {
        $user = $result->fetch_assoc();
        if (password_verify($senha, $user['senha'])) {
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['user_role'] = $user['role'];
            $_SESSION['user_name'] = $user['nome'];
            $_SESSION['email'] = $user['email'];

            // Retornar sucesso e role para o frontend decidir o redirecionamento
            echo json_encode(['success' => true, 'role' => $user['role'], 'message' => 'Login bem-sucedido!']);
        } else {
            echo json_encode(['success' => false, 'message' => 'Senha incorreta!']);
        }
    } else {
        echo json_encode(['success' => false, 'message' => 'Utilizador não encontrado!']);
    }

    $stmt->close();
    $conn->close();
    exit();
}
?>