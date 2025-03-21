<?php
session_start();
include 'config.php';

if (!$conn || $conn->connect_error) {
    die(json_encode(['success' => false, 'message' => 'Erro na conexão com o banco de dados']));
}

if (!isset($_SESSION['user_id']) || ($_SESSION['user_role'] !== 'barbeiro' && $_SESSION['user_role'] !== 'admin')) {
    die(json_encode(['success' => false, 'message' => 'Acesso negado']));
}

$data = json_decode(file_get_contents('php://input'), true);
if (!$data) {
    die(json_encode(['success' => false, 'message' => 'Dados inválidos']));
}

$name = $data['name'] ?? '';
$price = $data['price'] ?? 0;
$category = $data['category'] ?? '';

if (empty($name) || $price <= 0 || empty($category)) {
    die(json_encode(['success' => false, 'message' => 'Dados incompletos']));
}

$sql = "INSERT INTO servicos (nome, preco, categoria) VALUES (?, ?, ?)";
$stmt = $conn->prepare($sql);
$stmt->bind_param("sds", $name, $price, $category);

if ($stmt->execute()) {
    echo json_encode(['success' => true, 'message' => 'Serviço salvo com sucesso']);
} else {
    echo json_encode(['success' => false, 'message' => 'Erro ao salvar serviço']);
}

$stmt->close();
$conn->close();
?>