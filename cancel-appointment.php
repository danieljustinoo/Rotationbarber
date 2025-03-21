<?php
session_start();
include 'config.php';

if (!$conn || $conn->connect_error) {
    echo json_encode(['success' => false, 'message' => 'Erro na conexão com o banco de dados.']);
    exit();
}

if (!isset($_SESSION['user_id'])) {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'Acesso negado.']);
    exit();
}

$appointment_id = json_decode(file_get_contents('php://input'), true)['id'] ?? null;
$user_id = $_SESSION['user_id'];
$user_role = $_SESSION['user_role'];

if ($appointment_id) {
    // Verificar se o agendamento existe
    $stmt_check = $conn->prepare("SELECT cliente_id, barbeiro_id, estado FROM agendamentos WHERE id = ?");
    $stmt_check->bind_param("i", $appointment_id);
    $stmt_check->execute();
    $result = $stmt_check->get_result();
    $appointment = $result->fetch_assoc();

    if ($appointment) {
        // Verificar permissões
        $canCancel = false;
        if ($user_role === 'admin') {
            $canCancel = true; // Admin pode cancelar qualquer agendamento
        } elseif ($user_role === 'barbeiro' && $appointment['barbeiro_id'] == $user_id) {
            $canCancel = true; // Barbeiro só pode cancelar seus próprios agendamentos
        } elseif ($user_role === 'cliente' && $appointment['cliente_id'] == $user_id) {
            $canCancel = true; // Cliente só pode cancelar seus próprios agendamentos
        }

        if ($canCancel && $appointment['estado'] === 'pendente') {
            $stmt_update = $conn->prepare("UPDATE agendamentos SET estado = 'cancelado' WHERE id = ?");
            $stmt_update->bind_param("i", $appointment_id);
            if ($stmt_update->execute()) {
                echo json_encode(['success' => true, 'message' => 'Agendamento cancelado com sucesso!']);
            } else {
                echo json_encode(['success' => false, 'message' => 'Erro ao cancelar agendamento.']);
            }
            $stmt_update->close();
        } else {
            echo json_encode(['success' => false, 'message' => 'Agendamento não encontrado ou não está pendente, ou você não tem permissão.']);
        }
    } else {
        echo json_encode(['success' => false, 'message' => 'Agendamento não encontrado.']);
    }
    $stmt_check->close();
} else {
    echo json_encode(['success' => false, 'message' => 'ID do agendamento não fornecido.']);
}

$conn->close();
?>