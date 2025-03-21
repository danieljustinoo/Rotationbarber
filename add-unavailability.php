<?php
session_start();
include 'config.php';

header('Content-Type: application/json');

if (!$conn || $conn->connect_error) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Erro na conexão com o banco de dados']);
    exit();
}

if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'barbeiro') {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'Acesso negado']);
    exit();
}

$data = json_decode(file_get_contents('php://input'), true);
$barbeiro_id = $data['barbeiro_id'] ?? null;
$date = $data['data'] ?? null;
$hora_inicio = '00:00:00'; // Dia inteiro
$hora_fim = '23:59:59';   // Dia inteiro

if (!$barbeiro_id || !$date) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Parâmetros inválidos']);
    exit();
}

// Verificar se o barbeiro_id corresponde ao usuário logado
if ($barbeiro_id != $_SESSION['user_id']) {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'Acesso negado']);
    exit();
}

// Verificar se já existe uma indisponibilidade para o dia
$sql_check = "SELECT id FROM barber_unavailability WHERE barbeiro_id = ? AND data = ?";
$stmt_check = $conn->prepare($sql_check);
$stmt_check->bind_param("is", $barbeiro_id, $date);
$stmt_check->execute();
$result_check = $stmt_check->get_result();

if ($result_check->num_rows > 0) {
    $stmt_check->close();
    $conn->close();
    echo json_encode(['success' => false, 'message' => 'Já existe uma indisponibilidade para este dia']);
    exit();
}
$stmt_check->close();

// Verificar se há agendamentos confirmados ou pendentes no dia
$sql_appointments = "
    SELECT id 
    FROM agendaments 
    WHERE barbeiro_id = ? AND data = ? AND estado IN ('pendente', 'confirmado')
";
$stmt_appointments = $conn->prepare($sql_appointments);
$stmt_appointments->bind_param("is", $barbeiro_id, $date);
$stmt_appointments->execute();
$result_appointments = $stmt_appointments->get_result();

if ($result_appointments->num_rows > 0) {
    $stmt_appointments->close();
    $conn->close();
    echo json_encode(['success' => false, 'message' => 'Existem agendamentos pendentes ou confirmados neste dia']);
    exit();
}
$stmt_appointments->close();

// Inserir a indisponibilidade
$sql = "INSERT INTO barber_unavailability (barbeiro_id, data, hora_inicio, hora_fim) VALUES (?, ?, ?, ?)";
$stmt = $conn->prepare($sql);
$stmt->bind_param("isss", $barbeiro_id, $date, $hora_inicio, $hora_fim);

if ($stmt->execute()) {
    echo json_encode(['success' => true, 'message' => 'Indisponibilidade adicionada com sucesso']);
} else {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Erro ao adicionar indisponibilidade: ' . $stmt->error]);
}

$stmt->close();
$conn->close();
?>