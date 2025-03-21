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

if (!isset($data['id']) || !isset($data['email']) || !isset($data['role'])) {
    echo json_encode(['success' => false, 'message' => 'Dados inválidos']);
    exit;
}

$userId = intval($data['id']);
$email = $conn->real_escape_string($data['email']);
$telefone = isset($data['telefone']) ? $conn->real_escape_string($data['telefone']) : null;
$role = $conn->real_escape_string($data['role']);

// Validar email
if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    echo json_encode(['success' => false, 'message' => 'Email inválido']);
    exit;
}

// Validar role
$validRoles = ['cliente', 'barbeiro', 'admin'];
if (!in_array($role, $validRoles)) {
    echo json_encode(['success' => false, 'message' => 'Função inválida']);
    exit;
}

// Verificar se o email já está em uso por outro usuário
$sqlCheckEmail = "SELECT id FROM usuarios WHERE email = ? AND id != ?";
$stmtCheckEmail = $conn->prepare($sqlCheckEmail);
$stmtCheckEmail->bind_param("si", $email, $userId);
$stmtCheckEmail->execute();
$resultCheckEmail = $stmtCheckEmail->get_result();
if ($resultCheckEmail->num_rows > 0) {
    echo json_encode(['success' => false, 'message' => 'Este email já está em uso']);
    exit;
}
$stmtCheckEmail->close();

// Atualizar usuário
$sqlUpdate = "UPDATE usuarios SET email = ?, telefone = ?, role = ? WHERE id = ?";
$stmtUpdate = $conn->prepare($sqlUpdate);
$stmtUpdate->bind_param("sssi", $email, $telefone, $role, $userId);

if ($stmtUpdate->execute()) {
    echo json_encode(['success' => true, 'message' => 'Usuário atualizado com sucesso!']);
} else {
    echo json_encode(['success' => false, 'message' => 'Erro ao atualizar usuário']);
}

$stmtUpdate->close();
$conn->close();
?>