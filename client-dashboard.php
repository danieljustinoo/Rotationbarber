<?php
session_start();
include 'config.php';

if (!$conn || $conn->connect_error) {
    die('Erro na conexão com o banco de dados');
}

if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'cliente') {
    header('Location: login.html');
    exit();
}

// Obter o ID do cliente logado
$client_id = $_SESSION['user_id'];

// Carregar agendamentos do cliente
$sql_appointments = "
    SELECT a.id, a.cliente_id, a.barbeiro_id, a.servico, a.data, a.horario, a.pagamento, a.estado,
           u.email AS barbeiro_email
    FROM agendamentos a
    LEFT JOIN usuarios u ON a.barbeiro_id = u.id
    WHERE a.cliente_id = ?
";
$stmt_appointments = $conn->prepare($sql_appointments);
$stmt_appointments->bind_param("i", $client_id);
$stmt_appointments->execute();
$result_appointments = $stmt_appointments->get_result();
$appointments = [];
if ($result_appointments) {
    while ($row = $result_appointments->fetch_assoc()) {
        $appointments[] = $row;
    }
}
$stmt_appointments->close();

// Mapear barbeiro_id para imagens
$barber_images = [
    2 => 'Midia/Modelo.png',  // João
    3 => 'Midia/Modelo2.png', // Pedro
    4 => 'Midia/Modelo3.png'  // Carlos
];
$default_image = 'Logotipo/default-user.png';

// Verificar existência das imagens
foreach ($appointments as &$appointment) {
    $image_path = $barber_images[$appointment['barbeiro_id']] ?? $default_image;
    if (!file_exists($_SERVER['DOCUMENT_ROOT'] . '/PAP/' . $image_path)) {
        $appointment['image_path'] = $default_image;
    } else {
        $appointment['image_path'] = $image_path;
    }
}
unset($appointment);

// Capturar mensagem de status da URL
$status = isset($_GET['status']) ? $_GET['status'] : '';
$message = isset($_GET['message']) ? urldecode($_GET['message']) : '';

$conn->close();
?>

<!DOCTYPE html>
<html lang="pt-PT">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="shortcut icon" href="Logotipo/LogoGrande.png">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css" integrity="sha512-DTOQO9RWCH3ppGqcWaEA1BIZOC6xxalwEsw9c2QQeAIftl+Vegovlnee1c9QX4TctnWMn13TZye+giMm8e2LwA==" crossorigin="anonymous" referrerpolicy="no-referrer" />
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;600&family=Georgia&display=swap" rel="stylesheet">
    <title>Painel do Cliente - Rotation Barber</title>
    <style>
        body {
            font-family: 'Poppins', sans-serif;
            background: #2c1f1a;
            color: #b3a29b;
            margin: 0;
            padding: 0;
            display: flex;
            flex-direction: column;
            min-height: 100vh;
        }

        header {
            background: #5a3f2f;
            padding: 15px 20px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.3);
        }

        .logo img {
            height: 50px;
        }

        .nav-links {
            list-style: none;
            display: flex;
            margin: 0;
            padding: 0;
        }

        .nav-links li {
            margin: 0 15px;
        }

        .nav-links a {
            color: #b3a29b;
            text-decoration: none;
            transition: color 0.3s ease;
            font-size: 16px;
        }

        .nav-links a:hover {
            color: #eda276;
        }

        .logout-btn {
            background: #eda276;
            color: #fff;
            border: none;
            padding: 8px 20px;
            border-radius: 20px;
            cursor: pointer;
            transition: background 0.3s ease;
            font-size: 14px;
        }

        .logout-btn:hover {
            background: #b38062;
        }

        .dashboard-container {
            flex: 1;
            padding: 30px;
            max-width: 1200px;
            margin: 0 auto;
            width: 100%;
        }

        h1 {
            color: #eda276;
            font-family: 'Georgia', serif;
            font-size: 2em;
            text-align: center;
            margin-bottom: 20px;
        }

        p {
            text-align: center;
            margin-bottom: 30px;
            color: #e58f65;
        }

        .appointments-table {
            width: 100%;
            border-collapse: collapse;
            background: #5a3f2f;
            border-radius: 15px;
            overflow: hidden;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.2);
        }

        .appointments-table th,
        .appointments-table td {
            padding: 15px;
            text-align: left;
            border-bottom: 1px solid #7a5a47;
            font-size: 14px;
        }

        .appointments-table th {
            background: #7a5a47;
            color: #fff;
            text-transform: uppercase;
            font-size: 12px;
        }

        .appointments-table td {
            color: #b3a29b;
        }

        .appointments-table .barber-image {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            object-fit: cover;
            margin-right: 10px;
            vertical-align: middle;
        }

        .appointments-table button {
            background: #ff5555;
            color: #fff;
            border: none;
            padding: 8px 15px;
            border-radius: 15px;
            cursor: pointer;
            transition: background 0.3s ease;
            font-size: 12px;
        }

        .appointments-table button:hover {
            background: #cc4444;
        }

        .appointments-table button:disabled {
            background: #6c757d;
            cursor: not-allowed;
        }

        .status-message {
            margin: 15px 0;
            padding: 10px 20px;
            border-radius: 10px;
            text-align: center;
            background: <?php echo $status === 'success' ? '#d4edda' : '#f8d7da'; ?>;
            color: <?php echo $status === 'success' ? '#155724' : '#721c24'; ?>;
            display: <?php echo $status ? 'block' : 'none'; ?>;
            font-size: 14px;
        }

        footer {
            background: #5a3f2f;
            padding: 20px;
            text-align: center;
            color: #b3a29b;
            margin-top: auto;
        }

        @media (max-width: 768px) {
            .dashboard-container {
                padding: 15px;
            }

            .appointments-table th,
            .appointments-table td {
                padding: 10px;
                font-size: 12px;
            }

            .appointments-table .barber-image {
                width: 30px;
                height: 30px;
            }

            .appointments-table button {
                padding: 6px 12px;
                font-size: 10px;
            }

            h1 {
                font-size: 1.5em;
            }
        }
    </style>
</head>
<body>
    <header>
        <div class="logo">
            <img src="Logotipo/LogoGrande.png" alt="Rotation Barber Logo">
        </div>
        <nav>
            <ul class="nav-links">
                <li><a href="rotationbarber.html">Home</a></li>
                <li><a href="rotationbarber.html#about">Sobre Nós</a></li>
                <li><a href="rotationbarber.html#servicos">Serviços</a></li>
            </ul>
        </nav>
        <form action="logout.php" method="POST" style="display: inline;">
            <button type="submit" class="logout-btn">Sair</button>
        </form>
    </header>

    <div class="dashboard-container">
        <h1>Painel do Cliente</h1>
        <p>Veja e faça a gestão dos seus agendamentos aqui.</p>

        <?php if ($status): ?>
            <div class="status-message"><?php echo htmlspecialchars($message); ?></div>
        <?php endif; ?>

        <table class="appointments-table">
            <thead>
                <tr>
                    <th>Barbeiro</th>
                    <th>Serviço</th>
                    <th>Data</th>
                    <th>Horário</th>
                    <th>Pagamento</th>
                    <th>Estado</th>
                    <th>Ações</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($appointments as $appointment): ?>
                    <tr>
                        <td>
                            <img src="<?php echo htmlspecialchars($appointment['image_path']); ?>" alt="Foto do Barbeiro" class="barber-image">
                            <?php echo htmlspecialchars($appointment['barbeiro_email'] ?? 'Sem barbeiro'); ?>
                        </td>
                        <td><?php echo htmlspecialchars($appointment['servico'] ?? 'Sem serviço'); ?></td>
                        <td><?php echo htmlspecialchars($appointment['data'] ?? 'Sem data'); ?></td>
                        <td><?php echo htmlspecialchars($appointment['horario'] ?? 'Sem horário'); ?></td>
                        <td><?php echo htmlspecialchars($appointment['pagamento'] === 'local' ? 'Pagar no local' : 'Online'); ?></td>
                        <td><?php echo htmlspecialchars($appointment['estado'] ?? 'Sem estado'); ?></td>
                        <td>
                            <?php
                            $isCancellable = !in_array($appointment['estado'], ['cancelado', 'concluído']);
                            ?>
                            <button class="cancel" onclick="cancelAppointment(<?php echo $appointment['id']; ?>)" <?php echo $isCancellable ? '' : 'disabled'; ?>>Cancelar</button>
                        </td>
                    </tr>
                <?php endforeach; ?>
                <?php if (empty($appointments)): ?>
                    <tr>
                        <td colspan="7" style="text-align: center;">Nenhum agendamento encontrado.</td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>

    <footer>
        <p>© 2025 Rotation Barber. Todos os direitos reservados.</p>
    </footer>

    <script>
        function cancelAppointment(id) {
            if (confirm('Tens a certeza de que queres cancelar este agendamento?')) {
                fetch('cancel-appointment.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ id: id })
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        alert('Agendamento cancelado com sucesso!');
                        window.location.reload();
                    } else {
                        alert(data.message || 'Erro ao cancelar agendamento.');
                    }
                })
                .catch(error => {
                    console.error('Erro ao cancelar agendamento:', error);
                    alert('Erro ao conectar ao servidor. Tente novamente.');
                });
            }
        }
    </script>
</body>
</html>