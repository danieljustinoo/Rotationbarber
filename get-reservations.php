<?php
session_start();
include 'config.php';

if (!$conn || $conn->connect_error) {
    die(json_encode(['success' => false, 'message' => 'Erro na conexão com o banco de dados']));
}

if (!isset($_SESSION['user_id'])) {
    die(json_encode(['success' => false, 'message' => 'Usuário não autenticado']));
}

$user_id = $_SESSION['user_id'];
$user_role = $_SESSION['user_role'];

if ($user_role === 'cliente') {
    $sql = "SELECT a.id, a.servico, a.data, a.horario, a.estado, u.nome AS barbeiro_nome 
            FROM agendamentos a 
            LEFT JOIN usuarios u ON a.barbeiro_id = u.id 
            WHERE a.cliente_id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $user_id);
} elseif ($user_role === 'barbeiro') {
    $sql = "SELECT a.id, a.servico, a.data, a.horario, a.estado, u.nome AS cliente_nome 
            FROM agendamentos a 
            LEFT JOIN usuarios u ON a.cliente_id = u.id 
            WHERE a.barbeiro_id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $user_id);
} else {
    die(json_encode(['success' => false, 'message' => 'Acesso negado para este papel']));
}

$stmt->execute();
$result = $stmt->get_result();
$reservations = $result->fetch_all(MYSQLI_ASSOC);

echo json_encode(['success' => true, 'reservations' => $reservations]);

$stmt->close();
$conn->close();
?>