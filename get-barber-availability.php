<?php
session_start();
include 'config.php';

if (!$conn || $conn->connect_error) {
    die(json_encode(['success' => false, 'message' => 'Erro na conexão com o banco de dados']));
}

if (!isset($_SESSION['user_id'])) {
    die(json_encode(['success' => false, 'message' => 'Usuário não autenticado']));
}

$clientId = $_GET['clientId'] ?? '';
$barberId = $_GET['barber'] ?? '';

$barbers = [
    'barber1' => ['id' => 1, 'name' => 'João', 'available' => true],
    'barber2' => ['id' => 2, 'name' => 'Pedro', 'available' => true],
    'barber3' => ['id' => 3, 'name' => 'Carlos', 'available' => true]
];

// Consultar agendamentos para verificar disponibilidade
$date = date('Y-m-d'); // Data atual ou selecionada pelo cliente
$timeSlots = ['10:00', '11:00', '12:00', '13:00', '14:00', '15:00', '16:00', '17:00'];

if ($barberId) {
    $sql = "SELECT horario FROM agendamentos WHERE barbeiro_id = ? AND data = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("is", $barbers[$barberId]['id'], $date);
    $stmt->execute();
    $result = $stmt->get_result();
    $bookedTimes = [];
    while ($row = $result->fetch_assoc()) {
        $bookedTimes[] = $row['horario'];
    }
    $barbers[$barberId]['available'] = count($bookedTimes) < count($timeSlots);
} else {
    foreach ($barbers as $key => &$barber) {
        $sql = "SELECT horario FROM agendamentos WHERE barbeiro_id = ? AND data = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("is", $barber['id'], $date);
        $stmt->execute();
        $result = $stmt->get_result();
        $bookedTimes = [];
        while ($row = $result->fetch_assoc()) {
            $bookedTimes[] = $row['horario'];
        }
        $barber['available'] = count($bookedTimes) < count($timeSlots);
    }
}

echo json_encode(['success' => true, 'availability' => $barbers]);
$stmt->close();
$conn->close();
?>