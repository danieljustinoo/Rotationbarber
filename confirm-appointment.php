<?php
session_start();
include 'config.php';

header('Content-Type: application/json'); // Garantir que a resposta seja JSON

// Habilitar exibição de erros para depuração (remover em produção)
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

if (!$conn || $conn->connect_error) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Erro na conexão com o banco de dados: ' . $conn->connect_error]);
    exit();
}

if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'admin') {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'Acesso negado. Apenas administradores podem confirmar agendamentos.']);
    exit();
}

$input = json_decode(file_get_contents('php://input'), true);
$appointment_id = $input['id'] ?? null;

if ($appointment_id === null) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'ID do agendamento não fornecido.']);
    exit();
}

// Verificar se o agendamento existe e está pendente
$stmt_check = $conn->prepare("SELECT estado FROM agendamentos WHERE id = ?");
if (!$stmt_check) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Erro ao preparar a consulta: ' . $conn->error]);
    exit();
}

$stmt_check->bind_param("i", $appointment_id);
$stmt_check->execute();
$result = $stmt_check->get_result();
$appointment = $result->fetch_assoc();

if ($appointment && $appointment['estado'] === 'pendente') {
    $stmt_update = $conn->prepare("UPDATE agendamentos SET estado = 'confirmado' WHERE id = ?");
    if (!$stmt_update) {
        http_response_code(500);
        echo json_encode(['success' => false, 'message' => 'Erro ao preparar a atualização: ' . $conn->error]);
        exit();
    }

    $stmt_update->bind_param("i", $appointment_id);
    if ($stmt_update->execute()) {
        echo json_encode(['success' => true, 'message' => 'Agendamento confirmado com sucesso!']);
    } else {
        http_response_code(500);
        echo json_encode(['success' => false, 'message' => 'Erro ao confirmar agendamento: ' . $conn->error]);
    }
    $stmt_update->close();
} else {
    http_response_code(404);
    echo json_encode(['success' => false, 'message' => 'Agendamento não encontrado ou não está pendente.']);
}

$stmt_check->close();
$conn->close();
?>