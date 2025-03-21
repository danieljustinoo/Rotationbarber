<?php
session_start();
include 'config.php';

if (!$conn || $conn->connect_error) {
    die("Erro na conexão com o banco de dados: " . $conn->connect_error);
}

if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'barbeiro') {
    header("Location: login.html");
    exit();
}

$barber_id = $_SESSION['user_id'];

// Buscar horários atuais do barbeiro
$sql = "SELECT data, horario_inicio, horario_fim, disponivel FROM barber_schedules WHERE barber_id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $barber_id);
$stmt->execute();
$result = $stmt->get_result();
$schedules = $result->fetch_all(MYSQLI_ASSOC);

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $data = $_POST['date'] ?? '';
    $start_time = $_POST['start_time'] ?? '';
    $end_time = $_POST['end_time'] ?? '';
    $disponivel = isset($_POST['disponivel']) ? 1 : 0;

    // Verificar se o horário já existe para o barbeiro e data
    $sqlCheck = "SELECT COUNT(*) as count FROM barber_schedules WHERE barber_id = ? AND data = ?";
    $stmtCheck = $conn->prepare($sqlCheck);
    $stmtCheck->bind_param("is", $barber_id, $data);
    $stmtCheck->execute();
    $resultCheck = $stmtCheck->get_result();
    $row = $resultCheck->fetch_assoc();

    if ($row['count'] > 0) {
        // Atualizar horário existente
        $sqlUpdate = "UPDATE barber_schedules SET horario_inicio = ?, horario_fim = ?, disponivel = ? WHERE barber_id = ? AND data = ?";
        $stmtUpdate = $conn->prepare($sqlUpdate);
        $stmtUpdate->bind_param("ssisi", $start_time, $end_time, $disponivel, $barber_id, $data);
        $stmtUpdate->execute();
    } else {
        // Inserir novo horário
        $sqlInsert = "INSERT INTO barber_schedules (barber_id, data, horario_inicio, horario_fim, disponivel) VALUES (?, ?, ?, ?, ?)";
        $stmtInsert = $conn->prepare($sqlInsert);
        $stmtInsert->bind_param("isssi", $barber_id, $data, $start_time, $end_time, $disponivel);
        $stmtInsert->execute();
    }

    header("Location: barber-schedule.php");
    exit();
}

$stmt->close();
$conn->close();
?>

<!DOCTYPE html>
<html lang="pt-PT">
<head>
    <meta charset="UTF-8">
    <title>Gerenciar Horários - Rotation Barber</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <style>
        body { font-family: 'Poppins', sans-serif; background: #4a3025; color: #fff; margin: 0; padding: 20px; }
        .container { max-width: 800px; margin: 0 auto; background: #2c1f1a; padding: 20px; border-radius: 15px; box-shadow: 0 4px 20px rgba(0, 0, 0, 0.4); }
        h1 { text-align: center; color: #eda276; }
        .schedule-form { margin-top: 20px; }
        .form-group { margin-bottom: 15px; }
        .form-group label { color: #b3a29b; font-weight: 600; margin-right: 10px; }
        .form-group input { padding: 8px; border: 1px solid #7a5a47; border-radius: 5px; background: #2c1f1a; color: #b3a29b; }
        .form-group input[type="checkbox"] { margin-right: 5px; }
        button { background: #eda276; color: #fff; border: none; padding: 10px 20px; border-radius: 25px; cursor: pointer; margin-top: 10px; }
        button:hover { background: #b38062; }
        .schedule-list { margin-top: 20px; }
        .schedule-item { padding: 10px; background: #5a3f2f; border-radius: 5px; margin-bottom: 10px; }
        .logout-btn { background: #eda276; color: #fff; border: none; padding: 10px 20px; border-radius: 25px; cursor: pointer; margin-top: 20px; display: block; margin-left: auto; margin-right: auto; }
        .logout-btn:hover { background: #b38062; }
    </style>
</head>
<body>
    <div class="container">
        <h1>Gerenciar Meus Horários - Rotation Barber</h1>
        <form class="schedule-form" method="POST">
            <div class="form-group">
                <label for="date">Data:</label>
                <input type="date" id="date" name="date" required>
            </div>
            <div class="form-group">
                <label for="start_time">Início:</label>
                <input type="time" id="start_time" name="start_time" required>
            </div>
            <div class="form-group">
                <label for="end_time">Fim:</label>
                <input type="time" id="end_time" name="end_time" required>
            </div>
            <div class="form-group">
                <label>Disponível:</label>
                <input type="checkbox" name="disponivel" checked>
            </div>
            <button type="submit">Salvar Horário</button>
        </form>
        <div class="schedule-list">
            <h3>Meus Horários Atuais</h3>
            <?php if (!empty($schedules)): ?>
                <?php foreach ($schedules as $schedule): ?>
                    <div class="schedule-item">
                        <p>Data: <?php echo date('d/m/Y', strtotime($schedule['data'])); ?></p>
                        <p>Horário: <?php echo $schedule['horario_inicio'] . ' - ' . $schedule['horario_fim']; ?></p>
                        <p>Disponível: <?php echo $schedule['disponivel'] ? 'Sim' : 'Não'; ?></p>
                    </div>
                <?php endforeach; ?>
            <?php else: ?>
                <p>Nenhum horário registrado.</p>
            <?php endif; ?>
        </div>
        <button class="logout-btn" onclick="window.location.href='logout.php'">Sair</button>
    </div>
</body>
</html>