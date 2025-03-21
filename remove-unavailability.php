<?php
session_start();
include 'config.php';

header('Content-Type: application/json');

if (!$conn || $conn->connect_error) {
    echo json_encode(['success' => false, 'message' => 'Erro na conexão com o banco de dados']);
    exit;
}

if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'barbeiro') {
    echo json_encode(['success' => false, 'message' => 'Acesso não autorizado']);
    exit;
}

$data = json_decode(file_get_contents('php://input'), true);

if (!isset($data['id'])) {
    echo json_encode(['success' => false, 'message' => 'ID inválido']);
    exit;
}

$id = $data['id'];

$sql = "DELETE FROM barber_unavailability WHERE id = ? AND barbeiro_id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("ii", $id, $_SESSION['user_id']);

if ($stmt->execute()) {
    echo json_encode(['success' => true, 'message' => 'Indisponibilidade removida com sucesso!']);
} else {
    echo json_encode(['success' => false, 'message' => 'Erro ao remover indisponibilidade: ' . $conn->error]);
}

$stmt->close();
$conn->close();
?>