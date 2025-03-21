<?php
session_start();
include 'config.php';

header('Content-Type: application/json');

// Habilitar exibição de erros para depuração (remover em produção)
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

if (!$conn || $conn->connect_error) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Erro na conexão com a base de dados']);
    exit;
}

if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'barbeiro') {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'Acesso não autorizado']);
    exit;
}

$data = json_decode(file_get_contents('php://input'), true);

if (!isset($data['data']) || !isset($data['barbeiro_id'])) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Dados inválidos']);
    exit;
}

$barber_id = $data['barbeiro_id'];
$date = $data['data'];
$start_time = '00:00:00'; // Dia inteiro
$end_time = '23:59:59';   // Dia inteiro

// Verificar se o barbeiro_id corresponde ao usuário logado
if ($barber_id != $_SESSION['user_id']) {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'Acesso não autorizado']);
    exit;
}

// Verificar se já existe uma indisponibilidade para o dia
$sql_check = "SELECT * FROM barber_unavailability WHERE barbeiro_id = ? AND data = ?";
$stmt_check = $conn->prepare($sql_check);
$stmt_check->bind_param("is", $barber_id, $date);
$stmt_check->execute();
$result_check = $stmt_check->get_result();

if ($result_check->num_rows > 0) {
    http_response_code(409);
    echo json_encode(['success' => false, 'message' => 'Já existe uma indisponibilidade neste dia']);
    exit;
}
$stmt_check->close();

// Verificar conflitos com agendamentos existentes
$sql_check_appointments = "SELECT * FROM agendamentos WHERE barbeiro_id = ? AND data = ? AND estado IN ('pendente', 'confirmado')";
$stmt_check_appointments = $conn->prepare($sql_check_appointments);
$stmt_check_appointments->bind_param("is", $barber_id, $date);
$stmt_check_appointments->execute();
$result_check_appointments = $stmt_check_appointments->get_result();

if ($result_check_appointments->num_rows > 0) {
    http_response_code(409);
    echo json_encode(['success' => false, 'message' => 'Existem agendamentos ativos neste dia']);
    exit;
}
$stmt_check_appointments->close();

// Inserir a indisponibilidade
$sql = "INSERT INTO barber_unavailability (barbeiro_id, data, hora_inicio, hora_fim) VALUES (?, ?, ?, ?)";
$stmt = $conn->prepare($sql);

if ($stmt) {
    $stmt->bind_param("isss", $barber_id, $date, $start_time, $end_time);
    if ($stmt->execute()) {
        echo json_encode(['success' => true, 'message' => 'Indisponibilidade definida com sucesso!']);
    } else {
        http_response_code(500);
        echo json_encode(['success' => false, 'message' => 'Erro ao definir indisponibilidade: ' . $conn->error]);
    }
    $stmt->close();
} else {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Erro na preparação da consulta: ' . $conn->error]);
}

$conn->close();
?>