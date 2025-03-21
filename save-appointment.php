<?php
ob_start();

session_start();
header('Content-Type: application/json; charset=UTF-8');

ini_set('display_errors', 0);
ini_set('display_startup_errors', 0);
error_reporting(E_ALL);

// Incluir o PHPMailer
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

require 'PHPMailer/src/Exception.php';
require 'PHPMailer/src/PHPMailer.php';
require 'PHPMailer/src/SMTP.php';

function sendResponse($success, $message, $statusCode = 200) {
    http_response_code($statusCode);
    echo json_encode(['success' => $success, 'message' => $message]);
    ob_end_flush();
    exit();
}

if (!file_exists('config.php')) {
    error_log("Arquivo config.php não encontrado");
    sendResponse(false, "Erro interno: Arquivo de configuração não encontrado", 500);
}

require_once 'config.php';

if (!isset($conn) || $conn->connect_error) {
    $errorMessage = isset($conn) ? $conn->connect_error : 'Variável $conn não definida';
    error_log("Erro na conexão com o banco de dados: " . $errorMessage);
    sendResponse(false, "Erro na conexão com o banco de dados: " . $errorMessage, 500);
}

$input = file_get_contents('php://input');
error_log("Dados recebidos: " . $input);
$data = json_decode($input, true);

try {
    if (!$data || !isset($data['barberId']) || !isset($data['service']) || !isset($data['date']) || !isset($data['time']) || !isset($data['client']) || !isset($data['payment']) || !isset($data['preco'])) {
        error_log("Dados inválidos: " . json_encode($data));
        sendResponse(false, "Dados inválidos ou incompletos", 400);
    }

    $barberId = $data['barberId'] !== '' ? intval($data['barberId']) : 0;
    $service = $data['service'];
    $date = $data['date'];
    $time = $data['time'];
    $payment = $data['payment'];
    $client = $data['client'];
    $preco = floatval($data['preco']);
    $paymentId = $data['paymentId'] ?? null; // Adicionar paymentId

    if (strlen($time) === 5) {
        $time .= ':00';
    }

    if (empty($service) || empty($date) || empty($time) || empty($client['name']) || empty($client['phone']) || empty($client['email']) || empty($payment)) {
        error_log("Campos obrigatórios ausentes: " . json_encode($data));
        sendResponse(false, "Campos obrigatórios ausentes", 400);
    }

    // Validar paymentId para pagamentos online
    if (($payment === 'online-stripe' || $payment === 'online-paypal') && empty($paymentId)) {
        error_log("ID de pagamento ausente para pagamento online: " . json_encode($data));
        sendResponse(false, "ID de pagamento ausente para pagamento online", 400);
    }

    $clientId = isset($_SESSION['user_id']) ? intval($_SESSION['user_id']) : null;
    if (!$clientId) {
        error_log("Sessão não contém user_id");
        sendResponse(false, "Usuário não autenticado", 401);
    }

    $sqlCheckClient = "SELECT id FROM usuarios WHERE id = ?";
    $stmtCheckClient = $conn->prepare($sqlCheckClient);
    if (!$stmtCheckClient) {
        error_log("Erro ao preparar consulta de cliente: " . $conn->error);
        sendResponse(false, "Erro ao verificar cliente", 500);
    }
    $stmtCheckClient->bind_param("i", $clientId);
    $stmtCheckClient->execute();
    $resultCheckClient = $stmtCheckClient->get_result();
    if ($resultCheckClient->num_rows === 0) {
        error_log("Cliente não encontrado: ID $clientId");
        $stmtCheckClient->close();
        sendResponse(false, "Cliente inválido", 400);
    }
    $stmtCheckClient->close();

    if ($barberId === 0) {
        $sqlRandomBarber = "SELECT id FROM usuarios WHERE role = 'barbeiro' ORDER BY RAND() LIMIT 1";
        $resultRandomBarber = $conn->query($sqlRandomBarber);
        if ($resultRandomBarber && $resultRandomBarber->num_rows > 0) {
            $barber = $resultRandomBarber->fetch_assoc();
            $barberId = $barber['id'];
        } else {
            error_log("Nenhum barbeiro disponível");
            sendResponse(false, "Nenhum barbeiro disponível", 400);
        }
    }

    $sqlCheckBarber = "SELECT id, nome FROM usuarios WHERE id = ? AND role = 'barbeiro'";
    $stmtCheckBarber = $conn->prepare($sqlCheckBarber);
    if (!$stmtCheckBarber) {
        error_log("Erro ao preparar consulta de barbeiro: " . $conn->error);
        sendResponse(false, "Erro ao verificar barbeiro", 500);
    }
    $stmtCheckBarber->bind_param("i", $barberId);
    $stmtCheckBarber->execute();
    $resultCheckBarber = $stmtCheckBarber->get_result();
    if ($resultCheckBarber->num_rows === 0) {
        error_log("Barbeiro não encontrado: ID $barberId");
        $stmtCheckBarber->close();
        sendResponse(false, "Barbeiro inválido", 400);
    }
    $barberRow = $resultCheckBarber->fetch_assoc();
    $barberName = $barberRow['nome'];
    $stmtCheckBarber->close();

    $sqlCheck = "SELECT COUNT(*) as count FROM agendamentos WHERE barbeiro_id = ? AND data = ? AND horario = ? AND estado = 'pendente'";
    $stmtCheck = $conn->prepare($sqlCheck);
    if (!$stmtCheck) {
        error_log("Erro ao preparar consulta de disponibilidade: " . $conn->error);
        sendResponse(false, "Erro ao verificar disponibilidade", 500);
    }
    $stmtCheck->bind_param("iss", $barberId, $date, $time);
    $stmtCheck->execute();
    $resultCheck = $stmtCheck->get_result()->fetch_assoc();
    $stmtCheck->close();

    if ($resultCheck['count'] > 0) {
        error_log("Horário indisponível: Barbeiro $barberId, Data $date, Horário $time");
        sendResponse(false, "Horário indisponível para este barbeiro", 400);
    }

    // Ajustar a query para incluir payment_id
    $sql = "INSERT INTO agendamentos (cliente_id, barbeiro_id, servico, data, horario, pagamento, estado, preco, payment_id) VALUES (?, ?, ?, ?, ?, ?, 'pendente', ?, ?)";
    $stmt = $conn->prepare($sql);
    if (!$stmt) {
        error_log("Erro ao preparar inserção: " . $conn->error);
        sendResponse(false, "Erro ao preparar o agendamento", 500);
    }
    $stmt->bind_param("iissssds", $clientId, $barberId, $service, $date, $time, $payment, $preco, $paymentId);
    $success = $stmt->execute();

    if ($success) {
        error_log("Agendamento salvo com sucesso: Cliente $clientId, Barbeiro $barberId, Data $date, Horário $time");

        // Enviar e-mail de confirmação
        $mail = new PHPMailer(true);
        try {
            // Configurações do servidor SMTP
            $mail->isSMTP();
            $mail->Host = 'smtp.gmail.com';
            $mail->SMTPAuth = true;
            $mail->Username = 'borad0293@gmail.com';
            $mail->Password = 'pyee hucj ppea oacy';
            $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
            $mail->Port = 587;
            $mail->CharSet = 'UTF-8';

            // Remetente e destinatário
            $mail->setFrom('borad0293@gmail.com', 'Rotation Barber');
            $mail->addAddress($client['email'], $client['name']);

            // Conteúdo do e-mail
            $mail->isHTML(true);
            $mail->Subject = 'Confirmação de Agendamento - Rotation Barber';
            $mail->Body = "
                <h2>Confirmação de Agendamento</h2>
                <p>Olá, {$client['name']}!</p>
                <p>O seu agendamento foi realizado com sucesso. Aqui estão os detalhes:</p>
                <ul>
                    <li><strong>Serviço:</strong> {$service}</li>
                    <li><strong>Barbeiro:</strong> {$barberName}</li>
                    <li><strong>Data:</strong> {$date}</li>
                    <li><strong>Horário:</strong> {$time}</li>
                    <li><strong>Preço:</strong> {$preco}€</li>
                    <li><strong>Pagamento:</strong> " . ($payment === 'local' ? 'Pagar no local' : 'Online') . "</li>
                </ul>
                <p>Obrigado por escolher a Rotation Barber! Estamos ansiosos para atendê-lo.</p>
                <p>Se precisar de alguma alteração, entre em contato conosco.</p>
                <p>Com estilo,<br>Equipe Rotation Barber</p>
            ";
            $mail->AltBody = "Confirmação de Agendamento\n\nOlá, {$client['name']}!\nSeu agendamento foi realizado com sucesso. Aqui estão os detalhes:\n- Serviço: {$service}\n- Barbeiro: {$barberName}\n- Data: {$date}\n- Horário: {$time}\n- Preço: {$preco}€\n- Pagamento: " . ($payment === 'local' ? 'Pagar no local' : 'Online') . "\n\nObrigado por escolher a Rotation Barber! Estamos ansiosos para atendê-lo.\n\nCom estilo,\nEquipe Rotation Barber";

            $mail->send();
            error_log("E-mail de confirmação enviado para: {$client['email']}");
        } catch (Exception $e) {
            error_log("Erro ao enviar e-mail: {$mail->ErrorInfo}");
            sendResponse(true, "Agendamento salvo com sucesso, mas houve um erro ao enviar o e-mail de confirmação: {$mail->ErrorInfo}");
        }

        sendResponse(true, "Agendamento salvo com sucesso");
    } else {
        error_log("Erro ao salvar agendamento: " . $stmt->error);
        sendResponse(false, "Erro ao salvar o agendamento: " . $stmt->error, 500);
    }

    $stmt->close();
    $conn->close();
} catch (Exception $e) {
    error_log("Exceção capturada: " . $e->getMessage());
    sendResponse(false, "Erro interno: " . $e->getMessage(), 500);
}
?>