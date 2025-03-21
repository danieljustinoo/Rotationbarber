<?php
session_start();
include 'config.php';

if (!$conn || $conn->connect_error) {
    die(json_encode(['success' => false, 'message' => 'Erro na conexão com o banco de dados']));
}

if (!isset($_SESSION['user_id']) || ($_SESSION['user_role'] !== 'barbeiro' && $_SESSION['user_role'] !== 'admin')) {
    die(json_encode(['success' => false, 'message' => 'Acesso negado']));
}

// Filtrar agendamentos pelo barbeiro, se for barbeiro
$barberId = ($_SESSION['user_role'] === 'barbeiro') ? $_SESSION['user_id'] : null;
$sql = "SELECT a.id, c.nome AS cliente_nome, b.nome AS barbeiro_nome, b.imagem AS barbeiro_imagem, a.servico, a.data, a.horario, a.pagamento, a.estado 
        FROM agendamentos a 
        LEFT JOIN usuarios c ON a.cliente_id = c.id
        LEFT JOIN usuarios b ON a.barbeiro_id = b.id";
if ($barberId) {
    $sql .= " WHERE a.barbeiro_id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $barberId);
} else {
    $stmt = $conn->prepare($sql);
}
$stmt->execute();
$result = $stmt->get_result();
$appointments = [];

while ($row = $result->fetch_assoc()) {
    $appointments[] = [
        'id' => $row['id'],
        'cliente_nome' => $row['cliente_nome'] ?? 'Desconhecido',
        'barbeiro_nome' => $row['barbeiro_nome'] ?? 'Desconhecido',
        'barbeiro_imagem' => $row['barbeiro_imagem'] ?? null,
        'servico' => $row['servico'],
        'data' => $row['data'],
        'horario' => $row['horario'],
        'pagamento' => $row['pagamento'],
        'estado' => $row['estado']
    ];
}

echo json_encode(['success' => true, 'appointments' => $appointments]);

$stmt->close();
$conn->close();
?>