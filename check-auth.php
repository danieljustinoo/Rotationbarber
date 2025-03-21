<?php
session_start();
include 'config.php';

if (!$conn || $conn->connect_error) {
    die(json_encode(['success' => false, 'message' => 'Erro na conexão com o banco de dados']));
}

if (isset($_SESSION['user_id']) && $_SESSION['user_role'] === 'cliente') {
    echo json_encode(['success' => true, 'role' => $_SESSION['user_role']]);
} else {
    echo json_encode(['success' => false, 'message' => 'Usuário não autenticado ou não é cliente']);
}

$conn->close();
?>