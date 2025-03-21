<?php
session_start();
include 'config.php';

if (!$conn || $conn->connect_error) {
    die(json_encode(['success' => false, 'message' => 'Erro na conexão com o banco de dados']));
}

if (!isset($_SESSION['user_id']) || ($_SESSION['user_role'] !== 'barbeiro' && $_SESSION['user_role'] !== 'admin')) {
    die(json_encode(['success' => false, 'message' => 'Acesso negado']));
}

$data = json_decode(file_get_contents('php://input'), true);
if (!$data || !isset($data['id'])) {
    die(json_encode(['success' => false, 'message' => 'Dados inválidos']));
}

$id = $data['id'];

$sql = "DELETE FROM servicos WHERE id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $id);

if ($stmt->execute()) {
    echo json_encode(['success' => true, 'message' => 'Serviço excluído com sucesso']);
} else {
    echo json_encode(['success' => false, 'message' => 'Erro ao excluir serviço']);
}

$stmt->close();
$conn->close();
?>