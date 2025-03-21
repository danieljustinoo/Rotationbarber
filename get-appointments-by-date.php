<?php
session_start();
include 'config.php';

if (!$conn || $conn->connect_error) {
    die(json_encode(['success' => false, 'message' => 'Erro na conexão com o banco de dados']));
}

$data = json_decode(file_get_contents('php://input'), true);
$date = $data['date'] ?? '';
$barberId = $data['barberId'] ?? 0;

if (!$date || !$barberId) {
    die(json_encode(['success' => false, 'message' => 'Data ou barbeiro não fornecidos']));
}

$sql = "SELECT horario FROM agendamentos WHERE data = ? AND barbeiro_id = ? AND estado != 'cancelado'";
$stmt = $conn->prepare($sql);
$stmt->bind_param("si", $date, $barberId);
$stmt->execute();
$result = $stmt->get_result();
$appointments = [];

while ($row = $result->fetch_assoc()) {
    $appointments[] = $row;
}

echo json_encode(['success' => true, 'appointments' => $appointments]);

$stmt->close();
$conn->close();
?>