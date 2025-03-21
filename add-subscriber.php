<?php
session_start();
include 'config.php';

header('Content-Type: application/json');

if (!$conn || $conn->connect_error) {
    echo json_encode(['success' => false, 'message' => 'Erro na conexão com o banco de dados']);
    exit;
}

if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'admin') {
    echo json_encode(['success' => false, 'message' => 'Acesso não autorizado']);
    exit;
}

// Receber dados do POST
$data = json_decode(file_get_contents('php://input'), true);

if (!isset($data['email']) || !filter_var($data['email'], FILTER_VALIDATE_EMAIL)) {
    echo json_encode(['success' => false, 'message' => 'Email inválido']);
    exit;
}

$email = $conn->real_escape_string($data['email']);

// Verificar se o email já está cadastrado
$sqlCheck = "SELECT id FROM subscribers WHERE email = ?";
$stmtCheck = $conn->prepare($sqlCheck);
$stmtCheck->bind_param("s", $email);
$stmtCheck->execute();
$resultCheck = $stmtCheck->get_result();
if ($resultCheck->num_rows > 0) {
    echo json_encode(['success' => false, 'message' => 'Este email já está inscrito']);
    exit;
}
$stmtCheck->close();

// Adicionar assinante
$sqlInsert = "INSERT INTO subscribers (email, subscribed_at) VALUES (?, NOW())";
$stmtInsert = $conn->prepare($sqlInsert);
$stmtInsert->bind_param("s", $email);

if ($stmtInsert->execute()) {
    echo json_encode(['success' => true, 'message' => 'Assinante adicionado com sucesso!']);
} else {
    echo json_encode(['success' => false, 'message' => 'Erro ao adicionar assinante']);
}

$stmtInsert->close();
$conn->close();
?>