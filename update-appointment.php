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

$data = json_decode(file_get_contents('php://input'), true);
$appointment_id = $data['id'] ?? null;
$new_date = $data['data'] ?? null;
$new_time = $data['horario'] ?? null;
$user_id = $_SESSION['user_id'];
$user_role = $_SESSION['user_role'];

if ($appointment_id && $new_date && $new_time) {
    // Verificar se o agendamento existe e pertence ao barbeiro ou é editado por admin
    $stmt_check = $conn->prepare("SELECT barbeiro_id, estado FROM agendamentos WHERE id = ?");
    $stmt_check->bind_param("i", $appointment_id);
    $stmt_check->execute();
    $result = $stmt_check->get_result();
    $appointment = $result->fetch_assoc();

    if ($appointment) {
        $canEdit = false;
        if ($user_role === 'admin') {
            $canEdit = true; // Admin pode editar qualquer agendamento
        } elseif ($user_role === 'barbeiro' && $appointment['barbeiro_id'] == $user_id) {
            $canEdit = true; // Barbeiro só pode editar seus próprios agendamentos
        }

        if ($canEdit && !in_array($appointment['estado'], ['cancelado', 'concluído'])) {
            $stmt_update = $conn->prepare("UPDATE agendamentos SET data = ?, horario = ? WHERE id = ?");
            $stmt_update->bind_param("ssi", $new_date, $new_time, $appointment_id);
            if ($stmt_update->execute()) {
                echo json_encode(['success' => true, 'message' => 'Agendamento atualizado com sucesso!']);
            } else {
                echo json_encode(['success' => false, 'message' => 'Erro ao atualizar agendamento.']);
            }
            $stmt_update->close();
        } else {
            echo json_encode(['success' => false, 'message' => 'Não autorizado ou agendamento não editável.']);
        }
    } else {
        echo json_encode(['success' => false, 'message' => 'Agendamento não encontrado.']);
    }
    $stmt_check->close();
} else {
    echo json_encode(['success' => false, 'message' => 'Dados inválidos.']);
}

$conn->close();
?>