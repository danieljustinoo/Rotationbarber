<?php
session_start();
include 'config.php';

if (!$conn || $conn->connect_error) {
    die(json_encode(['success' => false, 'message' => 'Erro na conexão com o banco de dados']));
}

if (!isset($_SESSION['user_id'])) {
    die(json_encode(['success' => false, 'message' => 'Usuário não autenticado']));
}

// Simulação de verificação de atualizações (substitua por lógica real)
$role = $_SESSION['user_role'];
$userId = $_SESSION['user_id'];
$updates = false;

if ($role === 'barbeiro') {
    $sql = "SELECT COUNT(*) as count FROM agendamentos WHERE barbeiro_id = ? AND data >= CURDATE()";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $userId);
    $stmt->execute();
    $result = $stmt->get_result();
    $row = $result->fetch_assoc();
    $updates = $row['count'] > 0; // Exemplo simples
} elseif ($role === 'admin' || $role === 'cliente') {
    $sql = "SELECT COUNT(*) as count FROM agendamentos WHERE (barbeiro_id = ? OR cliente_id = ?) AND data >= CURDATE()";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("ii", $userId, $userId);
    $stmt->execute();
    $result = $stmt->get_result();
    $row = $result->fetch_assoc();
    $updates = $row['count'] > 0;
}

echo json_encode(['success' => true, 'updates' => $updates]);
$stmt->close();
$conn->close();
?>