<?php
session_start();
include 'config.php';

if (!$conn || $conn->connect_error) {
    die('Erro na conexão com o banco de dados');
}

if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'admin') {
    header('Location: login.html');
    exit();
}

// Carregar agendamentos com preços dos serviços
$sql_appointments = "
    SELECT a.id, a.cliente_id, a.barbeiro_id, a.servico, a.data, a.horario, a.pagamento, a.estado,
           u1.email AS cliente_email, u2.email AS barbeiro_email,
           COALESCE(a.preco, s.preco) AS preco
    FROM agendamentos a
    LEFT JOIN usuarios u1 ON a.cliente_id = u1.id
    LEFT JOIN usuarios u2 ON a.barbeiro_id = u2.id
    LEFT JOIN servicos s ON LOWER(TRIM(a.servico)) = LOWER(TRIM(s.nome))
";
$result_appointments = $conn->query($sql_appointments);
$appointments = [];
if ($result_appointments) {
    while ($row = $result_appointments->fetch_assoc()) {
        $appointments[] = $row;
    }
} else {
    die('Erro ao executar a consulta: ' . $conn->error);
}

// Calcular totais financeiros
$total_revenue = 0;
$pending_payments = 0;
$local_payments = 0;
$online_payments = 0;

foreach ($appointments as $appointment) {
    $preco = floatval($appointment['preco'] ?? 0); // Converter para float, usando 0 como fallback
    if (in_array($appointment['estado'], ['concluído', 'confirmado']) && $preco > 0) {
        $total_revenue += $preco;
    }
    if ($appointment['estado'] === 'pendente' && $preco > 0) {
        $pending_payments += $preco;
    }
    if ($appointment['pagamento'] === 'local' && in_array($appointment['estado'], ['concluído', 'confirmado']) && $preco > 0) {
        $local_payments += $preco;
    }
    // Ajuste para considerar 'online-stripe' e 'online-paypal' como pagamentos online
    if (in_array($appointment['pagamento'], ['online-stripe', 'online-paypal']) && in_array($appointment['estado'], ['concluído', 'confirmado']) && $preco > 0) {
        $online_payments += $preco;
    }
}

// Carregar barbeiros para o calendário
$sql_barbers = "SELECT id, email FROM usuarios WHERE role = 'barbeiro'";
$result_barbers = $conn->query($sql_barbers);
$barbers = [];
$barber_colors = [];
$colors = ['#FF6F61', '#6B5B95', '#88B04B', '#F7CAC9', '#92A8D1'];
$color_index = 0;
if ($result_barbers) {
    while ($row = $result_barbers->fetch_assoc()) {
        $barbers[$row['id']] = $row['email'];
        $barber_colors[$row['id']] = $colors[$color_index % count($colors)];
        $color_index++;
    }
}

// Carregar serviços
$sql_services = "SELECT id, nome, preco FROM servicos";
$result_services = $conn->query($sql_services);
$services = [];
if ($result_services) {
    while ($row = $result_services->fetch_assoc()) {
        $services[] = $row;
    }
}

// Carregar usuários
$sql_users = "SELECT id, email, telefone, role FROM usuarios";
$result_users = $conn->query($sql_users);
$users = [];
if ($result_users) {
    while ($row = $result_users->fetch_assoc()) {
        $users[] = $row;
    }
}

// Carregar assinantes da newsletter
$sql_newsletter = "SELECT id, email, subscribed_at FROM subscribers";
$result_newsletter = $conn->query($sql_newsletter);
$newsletter_subscribers = [];
if ($result_newsletter) {
    while ($row = $result_newsletter->fetch_assoc()) {
        $newsletter_subscribers[] = $row;
    }
}

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
    <!-- Adicionando Chart.js -->
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <title>Painel de Controle - Admin - Rotation Barber</title>
    <style>
        body {
            font-family: 'Poppins', sans-serif;
            background: #2c1f1a;
            color: #b3a29b;
            margin: 0;
            padding: 0;
        }

        header {
            background: #5a3f2f;
            padding: 10px 20px;
            display: flex;
            justify-content: space-between;
            align-items: center;
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
        }

        .nav-links a:hover {
            color: #eda276;
        }

        .logout-btn {
            background: #eda276;
            color: #fff;
            border: none;
            padding: 8px 15px;
            border-radius: 20px;
            cursor: pointer;
            transition: background 0.3s ease;
        }

        .logout-btn:hover {
            background: #b38062;
        }

        .dashboard-container {
            padding: 20px;
        }

        .tabs {
            margin-bottom: 20px;
        }

        .tab-btn {
            background: #7a5a47;
            color: #b3a29b;
            border: none;
            padding: 10px 20px;
            margin-right: 5px;
            border-radius: 20px;
            cursor: pointer;
            transition: background 0.3s ease;
        }

        .tab-btn.active {
            background: #eda276;
            color: #fff;
        }

        .tab-btn:hover:not(.active) {
            background: #8a5d43;
        }

        .tab-content {
            display: none;
        }

        .tab-content.active {
            display: block;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            background: #5a3f2f;
            border-radius: 10px;
            overflow: hidden;
        }

        th, td {
            padding: 12px;
            text-align: left;
            border-bottom: 1px solid #7a5a47;
        }

        th {
            background: #7a5a47;
            color: #fff;
        }

        td button {
            background: #eda276;
            color: #fff;
            border: none;
            padding: 6px 12px;
            border-radius: 15px;
            cursor: pointer;
            margin-right: 5px;
            transition: background 0.3s ease;
            font-size: 12px;
        }

        td button.edit {
            background: #eda276;
        }

        td button.edit:hover {
            background: #b38062;
        }

        td button.cancel {
            background: #ff5555;
        }

        td button.cancel:hover {
            background: #cc4444;
        }

        td button.confirm {
            background: #28a745;
        }

        td button.confirm:hover {
            background: #218838;
        }

        td button:disabled {
            background: #6c757d;
            cursor: not-allowed;
        }

        .modal {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0, 0, 0, 0.5);
            display: none;
            justify-content: center;
            align-items: center;
            z-index: 1000;
        }

        .modal-content {
            background: #5a3f2f;
            padding: 20px;
            border-radius: 10px;
            color: #b3a29b;
            width: 300px;
            position: relative;
        }

        .close-modal-btn {
            position: absolute;
            top: 10px;
            right: 10px;
            font-size: 20px;
            color: #e58f65;
            cursor: pointer;
            border: none;
            background: none;
            transition: color 0.3s ease;
        }

        .close-modal-btn:hover {
            color: #eda276;
        }

        .modal-content label {
            display: block;
            margin: 10px 0 5px;
        }

        .modal-content input, .modal-content select, .modal-content textarea {
            width: 100%;
            padding: 8px;
            border: none;
            border-radius: 5px;
            background: #2c1f1a;
            color: #b3a29b;
            resize: vertical;
        }

        .modal-action-btn {
            background: #eda276;
            color: #fff;
            border: none;
            padding: 8px 15px;
            border-radius: 20px;
            cursor: pointer;
            transition: background 0.3s ease;
            margin-right: 5px;
        }

        .modal-action-btn:hover {
            background: #b38062;
        }

        .modal-action-btn.cancel {
            background: #ff5555;
        }

        .modal-action-btn.cancel:hover {
            background: #cc4444;
        }

        .status-message {
            margin: 10px 0;
            padding: 10px;
            border-radius: 5px;
            display: <?php echo $status ? 'block' : 'none'; ?>;
            background: <?php echo $status === 'success' ? '#28a745' : '#ff5555'; ?>;
            color: #fff;
        }

        /* Estilos para a seção Newsletter */
        .newsletter-section {
            background: #5a3f2f;
            border-radius: 10px;
            padding: 20px;
            margin-bottom: 20px;
            box-shadow: 0 2px 5px rgba(0, 0, 0, 0.2);
        }

        .newsletter-section h3 {
            color: #eda276;
            margin-bottom: 15px;
            font-size: 1.2em;
        }

        .newsletter-form {
            display: flex;
            flex-wrap: wrap;
            gap: 10px;
        }

        .form-group {
            flex: 1;
            min-width: 200px;
        }

        .form-group label {
            display: block;
            margin-bottom: 5px;
            color: #b3a29b;
        }

        .form-group input,
        .form-group textarea {
            width: 100%;
            padding: 8px;
            border: none;
            border-radius: 5px;
            background: #2c1f1a;
            color: #b3a29b;
            resize: vertical;
        }

        .form-group textarea {
            min-height: 80px;
        }

        .submit-btn {
            background: #eda276;
            color: #fff;
            border: none;
            padding: 8px 15px;
            border-radius: 20px;
            cursor: pointer;
            transition: background 0.3s ease;
            margin-top: 10px;
        }

        .submit-btn:hover {
            background: #b38062;
        }

        .cancel-btn {
            background: #ff5555;
            color: #fff;
            border: none;
            padding: 6px 12px;
            border-radius: 15px;
            cursor: pointer;
            transition: background 0.3s ease;
        }

        .cancel-btn:hover {
            background: #cc4444;
        }

        .newsletter-table {
            width: 100%;
            border-collapse: collapse;
            background: #5a3f2f;
            border-radius: 10px;
            overflow: hidden;
        }

        .newsletter-table th,
        .newsletter-table td {
            padding: 12px;
            text-align: left;
            border-bottom: 1px solid #7a5a47;
        }

        .newsletter-table th {
            background: #7a5a47;
            color: #fff;
        }

        /* Estilos para o Calendário */
        .calendar-section {
            background: #5a3f2f;
            border-radius: 10px;
            padding: 20px;
            margin-bottom: 20px;
            box-shadow: 0 2px 5px rgba(0, 0, 0, 0.2);
            position: relative;
        }

        .calendar-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
        }

        .calendar-header button {
            background: #eda276;
            color: #fff;
            border: none;
            padding: 8px 15px;
            border-radius: 20px;
            cursor: pointer;
            transition: background 0.3s ease;
        }

        .calendar-header button:hover {
            background: #b38062;
        }

        .calendar-header span {
            font-size: 1.2em;
            color: #eda276;
        }

        .calendar-container {
            position: relative;
            width: 100%;
            overflow-x: auto;
        }

        .calendar-grid {
            display: grid;
            grid-template-columns: 100px repeat(7, 1fr);
            border: 1px solid #7a5a47;
            border-radius: 10px;
            background: #5a3f2f;
        }

        .calendar-grid div {
            border: 1px solid #7a5a47;
            padding: 5px;
            text-align: center;
            min-height: 50px;
            position: relative;
        }

        .calendar-grid .time-slot {
            text-align: right;
            padding-right: 10px;
            font-size: 12px;
            background: #7a5a47;
            color: #fff;
        }

        .calendar-grid .day-header {
            background: #7a5a47;
            color: #fff;
            font-weight: bold;
            position: sticky;
            top: 0;
            z-index: 2;
        }

        .calendar-grid .appointment-block {
            position: absolute;
            left: 2px;
            right: 2px;
            padding: 5px;
            color: #fff;
            font-size: 12px;
            border-radius: 5px;
            cursor: pointer;
            z-index: 1;
        }

        .calendar-legend {
            margin-top: 20px;
            display: flex;
            flex-wrap: wrap;
            gap: 15px;
        }

        .legend-item {
            display: flex;
            align-items: center;
            font-size: 12px;
        }

        .legend-color {
            width: 15px;
            height: 15px;
            border-radius: 3px;
            margin-right: 5px;
        }

        /* Estilos para a seção Finanças */
        .finance-section {
            background: #5a3f2f;
            border-radius: 10px;
            padding: 20px;
            margin-bottom: 20px;
            box-shadow: 0 2px 5px rgba(0, 0, 0, 0.2);
        }

        .finance-section h3 {
            color: #eda276;
            margin-bottom: 15px;
            font-size: 1.2em;
        }

        .finance-summary {
            margin-bottom: 20px;
        }

        .finance-summary p {
            margin: 5px 0;
        }

        .finance-charts {
            display: flex;
            flex-wrap: wrap;
            gap: 20px;
            margin-bottom: 20px;
        }

        .chart-container {
            flex: 1;
            min-width: 300px;
            max-width: 400px;
        }

        .finance-table {
            width: 100%;
            border-collapse: collapse;
            background: #5a3f2f;
            border-radius: 10px;
            overflow: hidden;
        }

        .finance-table th,
        .finance-table td {
            padding: 12px;
            text-align: left;
            border-bottom: 1px solid #7a5a47;
        }

        .finance-table th {
            background: #7a5a47;
            color: #fff;
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
                <li><a href="Rotationbarber.html">Home</a></li>
                <li><a href="Rotationbarber.html#about">Sobre Nós</a></li>
                <li><a href="Rotationbarber.html#servicos">Serviços</a></li>
                <li><a href="Rotationbarber.html#pricing">Preços</a></li>
                <li><a href="Rotationbarber.html#gallery">Galeria</a></li>
                <li><a href="Rotationbarber.html#team">Equipa</a></li>
            </ul>
        </nav>
        <form action="logout.php" method="POST" style="display: inline;">
            <button type="submit" class="logout-btn">Sair</button>
        </form>
    </header>

    <div class="dashboard-container">
        <h1>Painel de Controle - Admin - Rotation Barber</h1>
        <p>Gestão agendamentos, serviços, utilizadores, newsletter e finanças aqui.</p>

        <?php if ($status): ?>
            <div class="status-message"><?php echo htmlspecialchars($message); ?></div>
        <?php endif; ?>

        <div class="tabs">
            <button class="tab-btn active" onclick="openTab('appointments')">Agendamentos</button>
            <button class="tab-btn" onclick="openTab('calendar')">Calendário</button>
            <button class="tab-btn" onclick="openTab('users')">Utilizadores</button>
            <button class="tab-btn" onclick="openTab('newsletter')">Gestão Newsletter</button>
            <button class="tab-btn" onclick="openTab('finances')">Finanças</button>
        </div>

        <div id="appointments" class="tab-content active">
            <h2>Lista de Agendamentos</h2>
            <button class="modal-action-btn" onclick="openNewAppointmentModal()" style="margin-bottom: 20px;">Novo Agendamento</button>
            <table class="appointments-table" id="appointmentsTable">
                <thead>
                    <tr>
                        <th>Cliente</th>
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
                            <td><?php echo htmlspecialchars($appointment['cliente_email'] ?? 'Sem cliente'); ?></td>
                            <td><?php echo htmlspecialchars($appointment['barbeiro_email'] ?? 'Sem barbeiro'); ?></td>
                            <td><?php echo htmlspecialchars($appointment['servico'] ?? 'Sem serviço'); ?></td>
                            <td><?php echo htmlspecialchars($appointment['data'] ?? 'Sem data'); ?></td>
                            <td><?php echo htmlspecialchars($appointment['horario'] ?? 'Sem horário'); ?></td>
                            <td>
                                <?php 
                                if ($appointment['pagamento'] === 'local') {
                                    echo 'Pagar no local';
                                } elseif ($appointment['pagamento'] === 'online-stripe') {
                                    echo 'Online (Stripe)';
                                } elseif ($appointment['pagamento'] === 'online-paypal') {
                                    echo 'Online (PayPal)';
                                } else {
                                    echo 'Online';
                                }
                                ?>
                            </td>
                            <td><?php echo htmlspecialchars($appointment['estado'] ?? 'Sem estado'); ?></td>
                            <td>
                                <?php
                                $isEditable = !in_array($appointment['estado'], ['cancelado', 'concluído']);
                                $showConfirm = $appointment['estado'] === 'pendente';
                                ?>
                                <button class="edit" onclick="editAppointment(<?php echo $appointment['id']; ?>, '<?php echo $appointment['data']; ?>', '<?php echo $appointment['horario']; ?>', <?php echo $appointment['barbeiro_id'] ?? 0; ?>)" <?php echo $isEditable ? '' : 'disabled'; ?>>Editar</button>
                                <button class="cancel" onclick="cancelAppointment(<?php echo $appointment['id']; ?>)" <?php echo $isEditable ? '' : 'disabled'; ?>>Cancelar</button>
                                <?php if ($showConfirm): ?>
                                    <button class="confirm" onclick="confirmAppointment(<?php echo $appointment['id']; ?>)">Confirmar</button>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>

        <div id="calendar" class="tab-content">
            <h2>Calendário de Agendamentos</h2>
            <div class="calendar-section">
                <div class="calendar-header">
                    <button onclick="changeWeek(-1)">< Anterior</button>
                    <span id="weekRange">Semana Atual</span>
                    <button onclick="changeWeek(1)">Próxima ></button>
                </div>
                <div class="calendar-container" id="calendarContainer">
                    <div class="calendar-grid" id="calendarGrid"></div>
                </div>
                <div class="calendar-legend">
                    <?php foreach ($barbers as $barber_id => $barber_email): ?>
                        <div class="legend-item">
                            <span class="legend-color" style="background-color: <?php echo $barber_colors[$barber_id]; ?>;"></span>
                            <?php echo htmlspecialchars($barber_email); ?>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>

        <div id="users" class="tab-content">
            <h2>Gerenciar Utilizadores</h2>
            <table class="users-table" id="usersTable">
                <thead>
                    <tr>
                        <th>Email</th>
                        <th>Telefone</th>
                        <th>Função</th>
                        <th>Ações</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($users as $user): ?>
                        <tr data-id="<?php echo $user['id']; ?>">
                            <td><?php echo htmlspecialchars($user['email']); ?></td>
                            <td><?php echo htmlspecialchars($user['telefone'] ?? 'N/A'); ?></td>
                            <td><?php echo htmlspecialchars($user['role']); ?></td>
                            <td>
                                <button class="edit" onclick="editUser(<?php echo $user['id']; ?>)">Editar</button>
                                <button class="cancel" onclick="deleteUser(<?php echo $user['id']; ?>)">Excluir</button>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>

        <div id="newsletter" class="tab-content">
            <h2>Gestão Newsletter</h2>
            <div class="newsletter-section">
                <h3>Enviar Newsletter</h3>
                <form id="sendNewsletterForm" method="POST" action="send-newsletter.php" class="newsletter-form">
                    <div class="form-group">
                        <label for="subject">Assunto:</label>
                        <input type="text" id="subject" name="subject" required>
                    </div>
                    <div class="form-group">
                        <label for="message">Mensagem:</label>
                        <textarea id="message" name="message" required rows="4"></textarea>
                    </div>
                    <button type="submit" class="submit-btn">Enviar Newsletter</button>
                </form>
            </div>
            <div class="newsletter-section">
                <h3>Adicionar Assinante</h3>
                <form id="addSubscriberForm" class="newsletter-form">
                    <div class="form-group">
                        <input type="email" id="newSubscriberEmail" name="newSubscriberEmail" placeholder="Novo email" required>
                        <button type="submit" class="submit-btn">Adicionar</button>
                    </div>
                </form>
            </div>
            <div class="newsletter-section">
                <h3>Lista de Assinantes</h3>
                <table class="newsletter-table">
                    <thead>
                        <tr>
                            <th>Email</th>
                            <th>Data de Assinatura</th>
                            <th>Ações</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($newsletter_subscribers as $subscriber): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($subscriber['email']); ?></td>
                                <td><?php echo htmlspecialchars(date('d/m/Y H:i', strtotime($subscriber['subscribed_at'])) ?? 'N/A'); ?></td>
                                <td>
                                    <button class="cancel-btn" onclick="removeSubscriber(<?php echo $subscriber['id']; ?>)">Remover</button>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>

        <div id="finances" class="tab-content">
            <h2>Relatório Financeiro</h2>
            <div class="finance-section">
                <h3>Resumo Financeiro</h3>
                <div class="finance-summary">
                    <p><strong>Total de Receita (Confirmados/Concluídos):</strong> €<?php echo number_format($total_revenue, 2, ',', '.'); ?></p>
                    <p><strong>Pagamentos Pendentes:</strong> €<?php echo number_format($pending_payments, 2, ',', '.'); ?></p>
                    <p><strong>Pagamentos Locais (Confirmados/Concluídos):</strong> €<?php echo number_format($local_payments, 2, ',', '.'); ?></p>
                    <p><strong>Pagamentos Online (Confirmados/Concluídos):</strong> €<?php echo number_format($online_payments, 2, ',', '.'); ?></p>
                </div>
                <h3>Visualização Gráfica</h3>
                <div class="finance-charts">
                    <div class="chart-container">
                        <canvas id="paymentTypeChart"></canvas>
                    </div>
                    <div class="chart-container">
                        <canvas id="revenuePendingChart"></canvas>
                    </div>
                </div>
                <h3>Detalhes dos Agendamentos</h3>
                <table class="finance-table">
                    <thead>
                        <tr>
                            <th>Cliente</th>
                            <th>Serviço</th>
                            <th>Preço (€)</th>
                            <th>Data</th>
                            <th>Pagamento</th>
                            <th>Estado</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($appointments as $appointment): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($appointment['cliente_email'] ?? 'Sem cliente'); ?></td>
                                <td><?php echo htmlspecialchars($appointment['servico'] ?? 'Sem serviço'); ?></td>
                                <td><?php echo !empty($appointment['preco']) ? number_format($appointment['preco'], 2, ',', '.') : 'N/A'; ?>€</td>
                                <td><?php echo htmlspecialchars($appointment['data'] ?? 'Sem data'); ?></td>
                                <td>
                                    <?php 
                                    if ($appointment['pagamento'] === 'local') {
                                        echo 'Pagar no local';
                                    } elseif ($appointment['pagamento'] === 'online-stripe') {
                                        echo 'Online (Stripe)';
                                    } elseif ($appointment['pagamento'] === 'online-paypal') {
                                        echo 'Online (PayPal)';
                                    } else {
                                        echo 'Online';
                                    }
                                    ?>
                                </td>
                                <td><?php echo htmlspecialchars($appointment['estado'] ?? 'Sem estado'); ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Modal para edição de agendamento -->
        <div id="editModal" class="modal">
            <div class="modal-content">
                <span class="close-modal-btn" onclick="closeModal()">×</span>
                <h3>Editar Agendamento</h3>
                <input type="hidden" id="editAppointmentId">
                <label>Data:
                    <input type="date" id="editDate" required>
                </label>
                <label>Horário:
                    <select id="editTime" required></select>
                </label>
                <button class="modal-action-btn" onclick="saveEditedAppointment()">Salvar</button>
                <button class="modal-action-btn cancel" onclick="closeModal()">Cancelar</button>
            </div>
        </div>

        <!-- Modal para detalhes do agendamento -->
        <div id="appointmentDetailsModal" class="modal">
            <div class="modal-content">
                <span class="close-modal-btn" onclick="closeAppointmentDetailsModal()">×</span>
                <h3>Detalhes do Agendamento</h3>
                <p><strong>Cliente:</strong> <span id="detailClient"></span></p>
                <p><strong>Barbeiro:</strong> <span id="detailBarber"></span></p>
                <p><strong>Serviço:</strong> <span id="detailService"></span></p>
                <p><strong>Data:</strong> <span id="detailDate"></span></p>
                <p><strong>Horário:</strong> <span id="detailTime"></span></p>
                <p><strong>Pagamento:</strong> <span id="detailPayment"></span></p>
                <p><strong>Estado:</strong> <span id="detailStatus"></span></p>
                <p><strong>Preço:</strong> <span id="detailPrice"></span></p>
                <button class="modal-action-btn" onclick="editFromDetails()">Editar</button>
                <button class="modal-action-btn cancel" onclick="closeAppointmentDetailsModal()">Fechar</button>
            </div>
        </div>

        <!-- Modal para edição de usuário -->
        <div id="editUserModal" class="modal">
            <div class="modal-content">
                <span class="close-modal-btn" onclick="closeUserModal()">×</span>
                <h3>Editar Utilizador</h3>
                <input type="hidden" id="editUserId">
                <label>Email:
                    <input type="email" id="editUserEmail" required>
                </label>
                <label>Telefone:
                    <input type="text" id="editUserPhone">
                </label>
                <label>Função:
                    <select id="editUserRole" required>
                        <option value="cliente">Cliente</option>
                        <option value="barbeiro">Barbeiro</option>
                        <option value="admin">Admin</option>
                    </select>
                </label>
                <button class="modal-action-btn" onclick="saveEditedUser()">Salvar</button>
                <button class="modal-action-btn cancel" onclick="closeUserModal()">Cancelar</button>
            </div>
        </div>

        <!-- Modal para novo agendamento -->
        <div id="newAppointmentModal" class="modal">
            <div class="modal-content">
                <span class="close-modal-btn" onclick="closeNewAppointmentModal()">×</span>
                <h3>Novo Agendamento</h3>
                <form id="newAppointmentForm">
                    <label>Cliente:
                        <select id="newClient" name="client_id" required>
                            <option value="">Selecione um cliente</option>
                            <?php foreach ($users as $user): ?>
                                <?php if ($user['role'] === 'cliente'): ?>
                                    <option value="<?php echo $user['id']; ?>"><?php echo htmlspecialchars($user['email']); ?></option>
                                <?php endif; ?>
                            <?php endforeach; ?>
                        </select>
                    </label>
                    <label>Barbeiro:
                        <select id="newBarber" name="barber_id" required onchange="updateNewAppointmentTimes()">
                            <option value="">Selecione um barbeiro</option>
                            <?php foreach ($barbers as $id => $email): ?>
                                <option value="<?php echo $id; ?>"><?php echo htmlspecialchars($email); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </label>
                    <label>Serviço:
                        <select id="newService" name="service" required>
                            <option value="">Selecione um serviço</option>
                            <?php foreach ($services as $service): ?>
                                <option value="<?php echo htmlspecialchars($service['nome']); ?>">
                                    <?php echo htmlspecialchars($service['nome'] . ' (' . ($service['preco'] ?? 'N/A') . '€)'); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </label>
                    <label>Data:
                        <input type="date" id="newDate" name="date" required onchange="updateNewAppointmentTimes()">
                    </label>
                    <label>Horário:
                        <select id="newTime" name="time" required>
                            <option value="">Selecione barbeiro e data</option>
                        </select>
                    </label>
                    <label>Pagamento:
                        <select id="newPayment" name="payment" required>
                            <option value="local">Pagar no local</option>
                            <option value="online-stripe">Online (Stripe)</option>
                            <option value="online-paypal">Online (PayPal)</option>
                        </select>
                    </label>
                    <button type="button" class="modal-action-btn" onclick="saveNewAppointment()">Salvar</button>
                    <button type="button" class="modal-action-btn cancel" onclick="closeNewAppointmentModal()">Cancelar</button>
                </form>
            </div>
        </div>
    </div>

    <script>
        const appointments = <?php echo json_encode($appointments); ?>;
        const barberColors = <?php echo json_encode($barber_colors); ?>;
        let currentWeekStart = new Date();
        currentWeekStart.setDate(currentWeekStart.getDate() - currentWeekStart.getDay() + 1);

        function openTab(tabName) {
            document.querySelectorAll('.tab-content').forEach(tab => tab.classList.remove('active'));
            document.querySelectorAll('.tab-btn').forEach(btn => btn.classList.remove('active'));
            document.getElementById(tabName).classList.add('active');
            document.querySelector(`.tab-btn[onclick="openTab('${tabName}')"]`).classList.add('active');

            if (tabName === 'calendar') {
                renderCalendar();
            } else if (tabName === 'finances') {
                renderFinanceCharts();
            }
        }

        function renderCalendar() {
            const calendarGrid = document.getElementById('calendarGrid');
            calendarGrid.innerHTML = '';

            const weekEnd = new Date(currentWeekStart);
            weekEnd.setDate(weekEnd.getDate() + 6);
            document.getElementById('weekRange').textContent = `${currentWeekStart.toLocaleDateString('pt-PT')} - ${weekEnd.toLocaleDateString('pt-PT')}`;

            const timeSlots = [];
            for (let hour = 9; hour <= 18; hour++) {
                for (let minute = 0; minute < 60; minute += 30) {
                    timeSlots.push(`${hour.toString().padStart(2, '0')}:${minute.toString().padStart(2, '0')}`);
                }
            }

            let headerRow = '<div class="time-slot"></div>';
            for (let i = 0; i < 7; i++) {
                const day = new Date(currentWeekStart);
                day.setDate(day.getDate() + i);
                headerRow += `<div class="day-header">${day.toLocaleDateString('pt-PT', { weekday: 'short', day: 'numeric' })}</div>`;
            }
            calendarGrid.innerHTML += headerRow;

            timeSlots.forEach((time, index) => {
                let row = `<div class="time-slot">${time}</div>`;
                for (let i = 0; i < 7; i++) {
                    row += `<div class="calendar-cell" data-time="${time}" data-day="${i}"></div>`;
                }
                calendarGrid.innerHTML += row;
            });

            appointments.forEach(appointment => {
                const appointmentDate = new Date(appointment.data);
                if (appointmentDate >= currentWeekStart && appointmentDate <= weekEnd) {
                    const dayIndex = (appointmentDate.getDay() + 6) % 7;
                    const timeParts = appointment.horario.split(':');
                    const hour = parseInt(timeParts[0]);
                    const minute = parseInt(timeParts[1]);
                    const timeIndex = (hour - 9) * 2 + (minute >= 30 ? 1 : 0);

                    if (timeIndex >= 0 && timeIndex < timeSlots.length) {
                        const cell = document.querySelector(`.calendar-cell[data-time="${timeSlots[timeIndex]}"][data-day="${dayIndex}"]`);
                        if (cell) {
                            const block = document.createElement('div');
                            block.className = 'appointment-block';
                            block.style.backgroundColor = barberColors[appointment.barbeiro_id] || '#888';
                            block.style.top = `${(minute % 30) / 30 * 50}px`;
                            block.style.height = '40px';
                            block.innerHTML = `${appointment.cliente_email} - ${appointment.servico} (${appointment.preco ? appointment.preco + '€' : 'N/A'})`;
                            block.dataset.appointmentId = appointment.id;
                            block.addEventListener('click', () => showAppointmentDetails(appointment));
                            cell.appendChild(block);
                        }
                    }
                }
            });
        }

        function changeWeek(direction) {
            currentWeekStart.setDate(currentWeekStart.getDate() + direction * 7);
            renderCalendar();
        }

        function showAppointmentDetails(appointment) {
            document.getElementById('appointmentDetailsModal').style.display = 'flex';
            document.getElementById('detailClient').textContent = appointment.cliente_email || 'Sem cliente';
            document.getElementById('detailBarber').textContent = appointment.barbeiro_email || 'Sem barbeiro';
            document.getElementById('detailService').textContent = appointment.servico || 'Sem serviço';
            document.getElementById('detailDate').textContent = appointment.data || 'Sem data';
            document.getElementById('detailTime').textContent = appointment.horario || 'Sem horário';
            document.getElementById('detailPayment').textContent = appointment.pagamento === 'local' ? 'Pagar no local' : (appointment.pagamento === 'online-stripe' ? 'Online (Stripe)' : (appointment.pagamento === 'online-paypal' ? 'Online (PayPal)' : 'Online'));
            document.getElementById('detailStatus').textContent = appointment.estado || 'Sem estado';
            document.getElementById('detailPrice').textContent = appointment.preco ? `${appointment.preco}€` : 'N/A';
            document.getElementById('appointmentDetailsModal').dataset.appointmentId = appointment.id;
            document.getElementById('appointmentDetailsModal').dataset.barberId = appointment.barbeiro_id;
            document.getElementById('appointmentDetailsModal').dataset.currentDate = appointment.data;
            document.getElementById('appointmentDetailsModal').dataset.currentTime = appointment.horario;
        }

        function closeAppointmentDetailsModal() {
            document.getElementById('appointmentDetailsModal').style.display = 'none';
        }

        function editFromDetails() {
            const modal = document.getElementById('appointmentDetailsModal');
            const appointmentId = modal.dataset.appointmentId;
            const barberId = modal.dataset.barberId;
            const currentDate = modal.dataset.currentDate;
            const currentTime = modal.dataset.currentTime;
            closeAppointmentDetailsModal();
            editAppointment(appointmentId, currentDate, currentTime, barberId);
        }

        function editAppointment(appointmentId, currentDate, currentTime, barberId) {
            document.getElementById('editModal').style.display = 'flex';
            document.getElementById('editAppointmentId').value = appointmentId;
            document.getElementById('editDate').value = currentDate || new Date().toISOString().split('T')[0];
            updateAvailableTimes(barberId, currentDate, appointmentId, currentTime);

            document.getElementById('editDate').addEventListener('change', (e) => {
                const newDate = e.target.value;
                updateAvailableTimes(barberId, newDate, appointmentId, currentTime);
            });
        }

        function updateAvailableTimes(barberId, date, appointmentId, currentTime) {
            const timeSelect = document.getElementById('editTime');
            timeSelect.innerHTML = '<option value="">Carregando horários...</option>';

            fetch(`check-availability.php?barber=${barberId}&date=${date}&appointmentId=${appointmentId}`)
                .then(response => response.json())
                .then(data => {
                    timeSelect.innerHTML = '';
                    if (data.success) {
                        const allTimes = generateTimeSlots(9, 18, 30);
                        const bookedTimes = data.bookedTimes || [];
                        const concludedTimes = data.concludedTimes || [];
                        const occupiedTimes = [...bookedTimes, ...concludedTimes];
                        const availableTimes = allTimes.filter(time => !occupiedTimes.includes(time) || time === currentTime);

                        if (availableTimes.length === 0) {
                            const option = document.createElement('option');
                            option.textContent = 'Sem horários disponíveis';
                            option.disabled = true;
                            timeSelect.appendChild(option);
                        } else {
                            availableTimes.forEach(time => {
                                const option = document.createElement('option');
                                option.value = time;
                                option.textContent = time;
                                if (time === currentTime) option.selected = true;
                                timeSelect.appendChild(option);
                            });
                        }
                    } else {
                        const option = document.createElement('option');
                        option.textContent = data.message || 'Erro ao carregar horários';
                        option.disabled = true;
                        timeSelect.appendChild(option);
                    }
                })
                .catch(error => {
                    console.error('Erro ao carregar horários:', error);
                    timeSelect.innerHTML = '<option value="">Erro ao carregar horários</option>';
                });
        }

        function generateTimeSlots(startHour, endHour, interval) {
            const timeSlots = [];
            for (let hour = startHour; hour <= endHour; hour++) {
                for (let minute = 0; minute < 60; minute += interval) {
                    timeSlots.push(`${hour.toString().padStart(2, '0')}:${minute.toString().padStart(2, '0')}`);
                }
            }
            return timeSlots;
        }

        function saveEditedAppointment() {
            const appointmentId = document.getElementById('editAppointmentId').value;
            const newDate = document.getElementById('editDate').value;
            const newTime = document.getElementById('editTime').value;

            if (!newDate || !newTime) {
                alert('Por favor, seleciona uma data e horário válidos.');
                return;
            }

            fetch('update-appointment.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ id: appointmentId, data: newDate, horario: newTime })
            })
            .then(response => response.json())
            .then(data => {
                alert(data.message);
                if (data.success) {
                    closeModal();
                    window.location.reload();
                }
            })
            .catch(error => {
                console.error('Erro ao atualizar agendamento:', error);
                alert('Ocorreu um erro ao tentar atualizar o agendamento.');
            });
        }

        function closeModal() {
            document.getElementById('editModal').style.display = 'none';
        }

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

        function confirmAppointment(id) {
            if (confirm('Tens a certeza de que queres confirmar este agendamento?')) {
                fetch('confirm-appointment.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ id: id })
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        alert('Agendamento confirmado com sucesso!');
                        window.location.reload(); // Recarrega a página para atualizar os totais
                    } else {
                        alert(data.message || 'Erro ao confirmar agendamento.');
                    }
                })
                .catch(error => {
                    console.error('Erro ao confirmar agendamento:', error);
                    alert('Erro ao conectar ao servidor. Tente novamente.');
                });
            }
        }

        function openNewAppointmentModal() {
            document.getElementById('newAppointmentModal').style.display = 'flex';
            document.getElementById('newDate').value = new Date().toISOString().split('T')[0];
            updateNewAppointmentTimes();
        }

        function closeNewAppointmentModal() {
            document.getElementById('newAppointmentModal').style.display = 'none';
            document.getElementById('newAppointmentForm').reset();
        }

        function updateNewAppointmentTimes() {
            const barberId = document.getElementById('newBarber').value;
            const date = document.getElementById('newDate').value;
            const timeSelect = document.getElementById('newTime');

            if (!barberId || !date) {
                timeSelect.innerHTML = '<option value="">Selecione barbeiro e data</option>';
                return;
            }

            timeSelect.innerHTML = '<option value="">Carregando horários...</option>';

            fetch(`check-availability.php?barber=${barberId}&date=${date}`)
                .then(response => response.json())
                .then(data => {
                    timeSelect.innerHTML = '<option value="">Selecione um horário</option>';
                    if (data.success) {
                        const allTimes = generateTimeSlots(9, 18, 30);
                        const bookedTimes = data.bookedTimes || [];
                        const concludedTimes = data.concludedTimes || [];
                        const occupiedTimes = [...bookedTimes, ...concludedTimes];
                        const availableTimes = allTimes.filter(time => !occupiedTimes.includes(time));

                        if (availableTimes.length === 0) {
                            timeSelect.innerHTML = '<option value="" disabled>Sem horários disponíveis</option>';
                        } else {
                            availableTimes.forEach(time => {
                                const option = document.createElement('option');
                                option.value = time;
                                option.textContent = time;
                                timeSelect.appendChild(option);
                            });
                        }
                    } else {
                        timeSelect.innerHTML = '<option value="" disabled>Erro ao carregar horários</option>';
                    }
                })
                .catch(error => {
                    console.error('Erro ao carregar horários:', error);
                    timeSelect.innerHTML = '<option value="" disabled>Erro ao carregar horários</option>';
                });
        }

        function saveNewAppointment() {
            const form = document.getElementById('newAppointmentForm');
            const clientId = document.getElementById('newClient').value;
            const barberId = document.getElementById('newBarber').value;
            const service = document.getElementById('newService').value;
            const date = document.getElementById('newDate').value;
            const time = document.getElementById('newTime').value;
            const payment = document.getElementById('newPayment').value;

            if (!form.checkValidity()) {
                alert('Por favor, preencha todos os campos obrigatórios.');
                return;
            }

            const appointmentData = {
                client_id: clientId,
                barber_id: barberId,
                service: service,
                date: date,
                time: time,
                payment: payment,
                status: 'pendente'
            };

            fetch('create-appointment.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify(appointmentData)
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    alert('Agendamento criado com sucesso!');
                    closeNewAppointmentModal();
                    window.location.reload();
                } else {
                    alert(data.message || 'Erro ao criar agendamento.');
                }
            })
            .catch(error => {
                console.error('Erro ao criar agendamento:', error);
                alert('Erro ao conectar ao servidor. Tente novamente.');
            });
        }

        function editUser(id) {
            fetch(`get-user.php?id=${id}`)
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        document.getElementById('editUserModal').style.display = 'flex';
                        document.getElementById('editUserId').value = id;
                        document.getElementById('editUserEmail').value = data.user.email;
                        document.getElementById('editUserPhone').value = data.user.telefone || '';
                        document.getElementById('editUserRole').value = data.user.role;
                    } else {
                        alert(data.message || 'Erro ao carregar dados do usuário.');
                    }
                })
                .catch(error => {
                    console.error('Erro ao carregar usuário:', error);
                    alert('Erro ao conectar ao servidor. Tente novamente.');
                });
        }

        function closeUserModal() {
            document.getElementById('editUserModal').style.display = 'none';
        }

        function saveEditedUser() {
            const userId = document.getElementById('editUserId').value;
            const email = document.getElementById('editUserEmail').value;
            const phone = document.getElementById('editUserPhone').value;
            const role = document.getElementById('editUserRole').value;

            fetch('update-user.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ id: userId, email, telefone: phone, role })
            })
            .then(response => response.json())
            .then(data => {
                alert(data.message);
                if (data.success) {
                    closeUserModal();
                    window.location.reload();
                }
            })
            .catch(error => {
                console.error('Erro ao atualizar usuário:', error);
                alert('Erro ao conectar ao servidor. Tente novamente.');
            });
        }

        function deleteUser(id) {
            if (confirm('Tens a certeza de que queres excluir este usuário?')) {
                fetch('delete-user.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ id: id })
                })
                .then(response => {
                    if (!response.ok) {
                        throw new Error(`Erro HTTP: ${response.status} ${response.statusText}`);
                    }
                    return response.text(); // Usar text() primeiro para depuração
                })
                .then(text => {
                    console.log('Resposta do servidor:', text); // Log da resposta bruta
                    try {
                        const data = JSON.parse(text); // Tentar parsear como JSON
                        if (data.success) {
                            alert('Usuário excluído com sucesso!');
                            const row = document.querySelector(`tr[data-id="${id}"]`);
                            if (row) row.remove();
                        } else {
                            alert(data.message || 'Erro desconhecido ao excluir usuário.');
                        }
                    } catch (e) {
                        throw new Error('Erro ao parsear JSON: ' + e.message + ' - Resposta: ' + text);
                    }
                })
                .catch(error => {
                    console.error('Erro ao excluir usuário:', error);
                    alert(`Erro ao conectar ao servidor: ${error.message}. Verifica a consola para mais detalhes.`);
                });
            }
        }

        document.getElementById('addSubscriberForm').addEventListener('submit', function(e) {
            e.preventDefault();
            const email = document.getElementById('newSubscriberEmail').value;
            fetch('add-subscriber.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ email: email })
            })
            .then(response => response.json())
            .then(data => {
                alert(data.message);
                if (data.success) {
                    document.getElementById('newSubscriberEmail').value = '';
                    window.location.reload();
                }
            })
            .catch(error => {
                console.error('Erro ao adicionar assinante:', error);
                alert('Erro ao conectar ao servidor. Tente novamente.');
            });
        });

        function removeSubscriber(id) {
            if (confirm('Tens a certeza de que queres remover este assinante?')) {
                fetch('remove-subscriber.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ id: id })
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        alert('Assinante removido com sucesso!');
                        window.location.reload();
                    } else {
                        alert(data.message || 'Erro ao remover assinante.');
                    }
                })
                .catch(error => {
                    console.error('Erro ao remover assinante:', error);
                    alert('Erro ao conectar ao servidor. Tente novamente.');
                });
            }
        }

        // Função para renderizar os gráficos financeiros
        function renderFinanceCharts() {
            const localPayments = <?php echo $local_payments; ?>;
            const onlinePayments = <?php echo $online_payments; ?>;
            const totalRevenue = <?php echo $total_revenue; ?>;
            const pendingPayments = <?php echo $pending_payments; ?>;

            // Gráfico de Pizza: Distribuição de Pagamentos (Local vs Online)
            const paymentTypeCtx = document.getElementById('paymentTypeChart').getContext('2d');
            new Chart(paymentTypeCtx, {
                type: 'pie',
                data: {
                    labels: ['Pagamentos Locais', 'Pagamentos Online'],
                    datasets: [{
                        data: [localPayments, onlinePayments],
                        backgroundColor: ['#eda276', '#88B04B'],
                        borderColor: ['#b38062', '#6B5B95'],
                        borderWidth: 1
                    }]
                },
                options: {
                    responsive: true,
                    plugins: {
                        legend: {
                            position: 'top',
                            labels: {
                                color: '#b3a29b'
                            }
                        },
                        title: {
                            display: true,
                            text: 'Distribuição de Pagamentos (Confirmados/Concluídos)',
                            color: '#eda276'
                        }
                    }
                }
            });

            // Gráfico de Barras: Receita Total vs Pagamentos Pendentes
            const revenuePendingCtx = document.getElementById('revenuePendingChart').getContext('2d');
            new Chart(revenuePendingCtx, {
                type: 'bar',
                data: {
                    labels: ['Receita Total', 'Pagamentos Pendentes'],
                    datasets: [{
                        label: 'Valor (€)',
                        data: [totalRevenue, pendingPayments],
                        backgroundColor: ['#eda276', '#FF6F61'],
                        borderColor: ['#b38062', '#cc4444'],
                        borderWidth: 1
                    }]
                },
                options: {
                    responsive: true,
                    plugins: {
                        legend: {
                            display: false
                        },
                        title: {
                            display: true,
                            text: 'Receita Total vs Pagamentos Pendentes',
                            color: '#eda276'
                        }
                    },
                    scales: {
                        y: {
                            beginAtZero: true,
                            ticks: {
                                color: '#b3a29b'
                            },
                            grid: {
                                color: '#7a5a47'
                            }
                        },
                        x: {
                            ticks: {
                                color: '#b3a29b'
                            },
                            grid: {
                                color: '#7a5a47'
                            }
                        }
                    }
                }
            });
        }

        document.addEventListener('DOMContentLoaded', () => {
            renderCalendar();
            if (document.getElementById('finances').classList.contains('active')) {
                renderFinanceCharts();
            }
        });
    </script>
</body>
</html>