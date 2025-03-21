<?php
// Desativar exibição de erros em produção, mas manter para depuração
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Iniciar buffer de saída
ob_start();

// Adicionar log para depuração
file_put_contents('debug.log', date('Y-m-d H:i:s') . " - Iniciando delete-user.php\n", FILE_APPEND);

session_start();

if (session_status() === PHP_SESSION_NONE) {
    file_put_contents('debug.log', date('Y-m-d H:i:s') . " - Sessão não iniciada\n", FILE_APPEND);
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'Sessão não iniciada']);
    ob_end_flush();
    exit;
}

include 'config.php';

// Definir cabeçalhos
header('Content-Type: application/json');
header('Cache-Control: no-cache, must-revalidate');

if (!$conn || $conn->connect_error) {
    $errorMsg = 'Erro na conexão com o banco de dados: ' . ($conn->connect_error ?: 'Ligação não inicializada');
    file_put_contents('debug.log', date('Y-m-d H:i:s') . " - $errorMsg\n", FILE_APPEND);
    echo json_encode(['success' => false, 'message' => $errorMsg]);
    ob_end_flush();
    exit;
}

if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'admin') {
    file_put_contents('debug.log', date('Y-m-d H:i:s') . " - Acesso não autorizado\n", FILE_APPEND);
    echo json_encode(['success' => false, 'message' => 'Acesso não autorizado']);
    ob_end_flush();
    exit;
}

$data = json_decode(file_get_contents('php://input'), true);

if (!isset($data['id'])) {
    file_put_contents('debug.log', date('Y-m-d H:i:s') . " - ID de usuário inválido\n", FILE_APPEND);
    echo json_encode(['success' => false, 'message' => 'ID de usuário inválido']);
    ob_end_flush();
    exit;
}

$userId = intval($data['id']);
file_put_contents('debug.log', date('Y-m-d H:i:s') . " - User ID recebido: $userId\n", FILE_APPEND);

$sqlCheck = "SELECT id FROM usuarios WHERE id = ?";
$stmtCheck = $conn->prepare($sqlCheck);
if (!$stmtCheck) {
    $errorMsg = 'Erro ao preparar consulta de verificação: ' . $conn->error;
    file_put_contents('debug.log', date('Y-m-d H:i:s') . " - $errorMsg\n", FILE_APPEND);
    echo json_encode(['success' => false, 'message' => $errorMsg]);
    ob_end_flush();
    exit;
}
$stmtCheck->bind_param("i", $userId);
$stmtCheck->execute();
$resultCheck = $stmtCheck->get_result();
if ($resultCheck->num_rows === 0) {
    file_put_contents('debug.log', date('Y-m-d H:i:s') . " - Usuário não encontrado para ID: $userId\n", FILE_APPEND);
    echo json_encode(['success' => false, 'message' => 'Usuário não encontrado']);
    ob_end_flush();
    exit;
}
$stmtCheck->close();

$conn->begin_transaction();

try {
    $sqlDeleteClientAppointments = "DELETE FROM agendamentos WHERE cliente_id = ?";
    $stmtDeleteClient = $conn->prepare($sqlDeleteClientAppointments);
    if (!$stmtDeleteClient) {
        throw new Exception('Erro ao preparar DELETE de agendamentos (cliente): ' . $conn->error);
    }
    $stmtDeleteClient->bind_param("i", $userId);
    $stmtDeleteClient->execute();
    $stmtDeleteClient->close();

    $sqlDeleteBarberAppointments = "DELETE FROM agendamentos WHERE barbeiro_id = ?";
    $stmtDeleteBarber = $conn->prepare($sqlDeleteBarberAppointments);
    if (!$stmtDeleteBarber) {
        throw new Exception('Erro ao preparar DELETE de agendamentos (barbeiro): ' . $conn->error);
    }
    $stmtDeleteBarber->bind_param("i", $userId);
    $stmtDeleteBarber->execute();
    $stmtDeleteBarber->close();

    $sqlDelete = "DELETE FROM usuarios WHERE id = ?";
    $stmtDelete = $conn->prepare($sqlDelete);
    if (!$stmtDelete) {
        throw new Exception('Erro ao preparar DELETE de usuário: ' . $conn->error);
    }
    $stmtDelete->bind_param("i", $userId);

    if ($stmtDelete->execute()) {
        $conn->commit();
        $response = ['success' => true, 'message' => 'Utilizador excluído com sucesso!'];
        file_put_contents('debug.log', date('Y-m-d H:i:s') . " - Utilizador excluído com sucesso para ID: $userId. Resposta: " . json_encode($response) . "\n", FILE_APPEND);
        echo json_encode($response);
    } else {
        throw new Exception('Erro ao executar DELETE de usuário: ' . $conn->error);
    }

    $stmtDelete->close();
} catch (Exception $e) {
    $conn->rollback();
    $errorMsg = $e->getMessage();
    $response = ['success' => false, 'message' => $errorMsg];
    file_put_contents('debug.log', date('Y-m-d H:i:s') . " - Erro: $errorMsg. Resposta: " . json_encode($response) . "\n", FILE_APPEND);
    echo json_encode($response);
}

$conn->close();
ob_end_flush();
?>