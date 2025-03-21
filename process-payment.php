<?php
session_start();
include 'config.php'; // Arquivo de configuração com a conexão ao banco de dados
require 'vendor/autoload.php'; // Carregar Stripe via Composer

// Configurar logs
ini_set('display_errors', 0);
ini_set('display_startup_errors', 0);
error_reporting(E_ALL);

// Função para enviar resposta JSON
function sendResponse($success, $message, $statusCode = 200) {
    http_response_code($statusCode);
    echo json_encode(['success' => $success, 'message' => $message]);
    exit();
}

// Configurar a chave secreta do Stripe (modo teste)
\Stripe\Stripe::setApiKey('sk_test_51R2bjN4K752cALOme1jFBCIjrgN2xjCwaCU4GsLsokxmk4tO8JhiOM9v8lOprQ8OmnNKZyldP24OEbPqGPtomsZ800YdXs62Ch'); // Substitua pela sua chave secreta de teste

// Verificar conexão com o banco de dados
if (!$conn || $conn->connect_error) {
    error_log('Erro na conexão com o banco de dados: ' . $conn->connect_error);
    sendResponse(false, 'Erro na conexão com o banco de dados', 500);
}

// Verificar se o usuário está logado e é cliente
if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'cliente') {
    error_log('Acesso negado: usuário não autenticado ou não é cliente');
    sendResponse(false, 'Acesso negado', 401);
}

// Obter dados enviados pelo cliente
$data = json_decode(file_get_contents('php://input'), true);
error_log('Dados recebidos em process-payment.php: ' . json_encode($data));

$paymentMethodId = $data['paymentMethodId'] ?? '';
$amount = $data['amount'] ?? 0; // Amount em centavos

// Validar dados
if (empty($paymentMethodId) || $amount <= 0) {
    error_log('Dados de pagamento inválidos: paymentMethodId ou amount inválido');
    sendResponse(false, 'Dados de pagamento inválidos', 400);
}

try {
    // Criar PaymentIntent com o Stripe
    $paymentIntent = \Stripe\PaymentIntent::create([
        'amount' => $amount, // Em centavos
        'currency' => 'eur', // Usar euros
        'payment_method' => $paymentMethodId,
        'confirm' => true,
        'return_url' => 'http://localhost/rotationbarber/reservation.html' // Atualize com sua URL real
    ]);

    error_log('PaymentIntent criado com sucesso: ' . $paymentIntent->id);
    sendResponse(true, 'Pagamento processado com sucesso');
} catch (\Stripe\Exception\ApiErrorException $e) {
    // Se houver erro no Stripe, retornar mensagem de erro
    error_log('Erro no Stripe: ' . $e->getMessage());
    sendResponse(false, 'Erro no Stripe: ' . $e->getMessage(), 400);
} catch (Exception $e) {
    // Outros erros gerais
    error_log('Erro geral: ' . $e->getMessage());
    sendResponse(false, 'Erro interno: ' . $e->getMessage(), 500);
}

$conn->close();
?>