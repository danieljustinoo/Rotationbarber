<?php
session_start();
include 'config.php';

header('Content-Type: application/json');

if (!$conn || $conn->connect_error) {
    echo json_encode(['success' => false, 'message' => 'Erro na conexão com o banco de dados']);
    exit();
}

$data = json_decode(file_get_contents('php://input'), true);

$client_id = $data['client_id'];
$barber_id = $data['barber_id'];
$servico = $data['service'];
$data_agendamento = $data['date'];
$horario = $data['time'];
$pagamento = $data['payment'];
$estado = $data['status'];

$sql = "INSERT INTO agendamentos (cliente_id, barbeiro_id, servico, data, horario, pagamento, estado) 
        VALUES (?, ?, ?, ?, ?, ?, ?)";
$stmt = $conn->prepare($sql);

if ($stmt) {
    $stmt->bind_param("iisssss", $client_id, $barber_id, $servico, $data_agendamento, $horario, $pagamento, $estado);
    if ($stmt->execute()) {
        echo json_encode(['success' => true, 'message' => 'Agendamento criado com sucesso!']);
    } else {
        echo json_encode(['success' => false, 'message' => 'Erro ao criar agendamento: ' . $conn->error]);
    }
    $stmt->close();
} else {
    echo json_encode(['success' => false, 'message' => 'Erro na preparação da consulta: ' . $conn->error]);
}

$conn->close();
?>