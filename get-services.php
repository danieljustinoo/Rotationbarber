<?php
session_start();
include 'config.php';

if (!$conn || $conn->connect_error) {
    die(json_encode(['success' => false, 'message' => 'Erro na conexão com o banco de dados']));
}

if (!isset($_SESSION['user_id']) || ($_SESSION['user_role'] !== 'barbeiro' && $_SESSION['user_role'] !== 'admin')) {
    die(json_encode(['success' => false, 'message' => 'Acesso negado']));
}

$sql = "SELECT id, nome, preco, categoria FROM servicos";
$result = $conn->query($sql);
$services = [];

while ($row = $result->fetch_assoc()) {
    $services[] = $row;
}

echo json_encode(['success' => true, 'services' => $services]);

$conn->close();
?>