<?php
session_start();
include 'config.php';

if (!$conn || $conn->connect_error) {
    die(json_encode(['success' => false, 'message' => 'Erro na conexão com o banco de dados']));
}

if (!isset($_SESSION['user_id'])) {
    die(json_encode(['success' => false, 'message' => 'Usuário não autenticado']));
}

$data = json_decode(file_get_contents('php://input'), true);
$reservationId = $data['reservationId'] ?? 0;

if (!$reservationId) {
    die(json_encode(['success' => false, 'message' => 'ID da reserva não fornecido']));
}

$user_id = $_SESSION['user_id'];
$user_role = $_SESSION['user_role'];

// Verificar se o utilizador tem permissão para cancelar (cliente ou barbeiro da reserva)
if ($user_role === 'cliente') {
    $sqlCheck = "SELECT COUNT(*) as count FROM agendamentos WHERE id = ? AND cliente_id = ?";
    $stmtCheck = $conn->prepare($sqlCheck);
    $stmtCheck->bind_param("ii", $reservationId, $user_id);
} elseif ($user_role === 'barbeiro') {
    $sqlCheck = "SELECT COUNT(*) as count FROM agendamentos WHERE id = ? AND barbeiro_id = ?";
    $stmtCheck = $conn->prepare($sqlCheck);
    $stmtCheck->bind_param("ii", $reservationId, $user_id);
} else {
    die(json_encode(['success' => false, 'message' => 'Acesso negado']));
}

$stmtCheck->execute();
$resultCheck = $stmtCheck->get_result();
$row = $resultCheck->fetch_assoc();

if ($row['count'] == 0) {
    die(json_encode(['success' => false, 'message' => 'Reserva não encontrada ou sem permissão']));
}

// Atualizar o estado para "cancelado"
$sqlUpdate = "UPDATE agendamentos SET estado = 'cancelado' WHERE id = ?";
$stmtUpdate = $conn->prepare($sqlUpdate);
$stmtUpdate->bind_param("i", $reservationId);

if ($stmtUpdate->execute()) {
    echo json_encode(['success' => true, 'message' => 'Reserva cancelada com sucesso']);
} else {
    echo json_encode(['success' => false, 'message' => 'Erro ao cancelar a reserva']);
}

$stmtCheck->close();
$stmtUpdate->close();
$conn->close();
?>