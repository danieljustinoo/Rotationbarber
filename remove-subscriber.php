<?php
session_start();
include 'config.php';

// Verificar ligação à base de dados
if (!$conn || $conn->connect_error) {
    die(json_encode(['success' => false, 'message' => 'Erro na conexão com o banco de dados']));
}

// Verificar permissões de administrador
if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'admin') {
    die(json_encode(['success' => false, 'message' => 'Acesso negado']));
}

// Obter os dados do pedido
$data = json_decode(file_get_contents('php://input'), true);
if (!$data || !isset($data['id'])) {
    die(json_encode(['success' => false, 'message' => 'ID do assinante não fornecido']));
}

$subscriberId = intval($data['id']);

// Verificar se o assinante existe
$sql_check = "SELECT id FROM subscribers WHERE id = ?"; // Alterado de 'newsletter' para 'subscribers'
$stmt_check = $conn->prepare($sql_check);
if (!$stmt_check) {
    die(json_encode(['success' => false, 'message' => 'Erro ao preparar a consulta: ' . $conn->error]));
}
$stmt_check->bind_param("i", $subscriberId);
$stmt_check->execute();
$result = $stmt_check->get_result();

if ($result->num_rows === 0) {
    $stmt_check->close();
    $conn->close();
    die(json_encode(['success' => false, 'message' => 'Assinante não encontrado']));
}
$stmt_check->close();

// Remover o assinante
$sql = "DELETE FROM subscribers WHERE id = ?"; // Alterado de 'newsletter' para 'subscribers'
$stmt = $conn->prepare($sql);
if (!$stmt) {
    die(json_encode(['success' => false, 'message' => 'Erro ao preparar a consulta: ' . $conn->error]));
}
$stmt->bind_param("i", $subscriberId);

if ($stmt->execute()) {
    echo json_encode(['success' => true, 'message' => 'Assinante removido com sucesso']);
} else {
    echo json_encode(['success' => false, 'message' => 'Erro ao remover assinante: ' . $stmt->error]);
}

$stmt->close();
$conn->close();
?>