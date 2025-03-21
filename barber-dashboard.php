<?php
session_start();
include 'config.php';

if (!$conn || $conn->connect_error) {
    die('Erro na conexão com a base de dados');
}

if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'barbeiro') {
    header('Location: login.html');
    exit();
}

// Obter o ID do barbeiro logado
$barber_id = $_SESSION['user_id'];

// Consultar o nome do barbeiro
$sql_barber = "SELECT nome, email FROM usuarios WHERE id = ?";
$stmt_barber = $conn->prepare($sql_barber);
$stmt_barber->bind_param("i", $barber_id);
$stmt_barber->execute();
$result_barber = $stmt_barber->get_result();
$barber_data = $result_barber->fetch_assoc();
$barber_name = $barber_data['nome'] ?? 'Barbeiro';
$barber_email = $barber_data['email'] ?? 'barbeiro@exemplo.com';
$stmt_barber->close();

// Mapear o barbeiro para a imagem correspondente
$image_files = [
    2 => 'Midia/Modelo.png',
    3 => 'Midia/Modelo2.png',
    4 => 'Midia/Modelo3.png'
];
$default_image = 'Logotipo/default-user.png';
$barber_image = $image_files[$barber_id] ?? $default_image;
if (!file_exists($_SERVER['DOCUMENT_ROOT'] . '/PAP/' . $barber_image)) {
    $barber_image = $default_image;
}

// Carregar agendamentos do barbeiro
$sql_appointments = "
    SELECT a.id, a.cliente_id, a.barbeiro_id, a.servico, a.data, a.horario, a.pagamento, a.estado,
           u.email AS cliente_email
    FROM agendamentos a
    LEFT JOIN usuarios u ON a.cliente_id = u.id
    WHERE a.barbeiro_id = ?
";
$stmt_appointments = $conn->prepare($sql_appointments);
$stmt_appointments->bind_param("i", $barber_id);
$stmt_appointments->execute();
$result_appointments = $stmt_appointments->get_result();
$appointments = [];
if ($result_appointments) {
    while ($row = $result_appointments->fetch_assoc()) {
        $appointments[] = $row;
    }
}
$stmt_appointments->close();

// Carregar indisponibilidades do barbeiro
$sql_unavailability = "SELECT id, data, hora_inicio, hora_fim FROM barber_unavailability WHERE barbeiro_id = ?";
$stmt_unavailability = $conn->prepare($sql_unavailability);
$stmt_unavailability->bind_param("i", $barber_id);
$stmt_unavailability->execute();
$result_unavailability = $stmt_unavailability->get_result();
$unavailabilities = [];
if ($result_unavailability) {
    while ($row = $result_unavailability->fetch_assoc()) {
        $unavailabilities[] = $row;
    }
}
$stmt_unavailability->close();

// Definir a cor do barbeiro (apenas para o barbeiro logado)
$barber_colors = [$barber_id => '#FF6F61']; // Cor fixa para o barbeiro logado

// Capturar mensagem de status
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
    <title>Painel de Controle - Barbeiro - Rotation Barber</title>
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
        .logo img { height: 50px; }
        .nav-links { list-style: none; display: flex; margin: 0; padding: 0; }
        .nav-links li { margin: 0 15px; }
        .nav-links a { color: #b3a29b; text-decoration: none; transition: color 0.3s ease; }
        .nav-links a:hover { color: #eda276; }
        .logout-btn {
            background: #eda276;
            color: #fff;
            border: none;
            padding: 8px 15px;
            border-radius: 20px;
            cursor: pointer;
            transition: background 0.3s ease;
        }
        .logout-btn:hover { background: #b38062; }
        .dashboard-container { padding: 20px; }
        .barber-profile {
            display: flex;
            align-items: center;
            margin-bottom: 20px;
            background: #5a3f2f;
            padding: 10px;
            border-radius: 10px;
        }
        .barber-profile img {
            width: 60px;
            height: 60px;
            border-radius: 50%;
            margin-right: 15px;
            object-fit: cover;
        }
        .barber-profile h2 { margin: 0; font-size: 1.2em; color: #eda276; }
        .tabs { margin-bottom: 20px; }
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
        .tab-btn.active { background: #eda276; color: #fff; }
        .tab-btn:hover:not(.active) { background: #8a5d43; }
        .tab-content { display: none; }
        .tab-content.active { display: block; }
        table {
            width: 100%;
            border-collapse: collapse;
            background: #5a3f2f;
            border-radius: 10px;
            overflow: hidden;
        }
        th, td { padding: 12px; text-align: left; border-bottom: 1px solid #7a5a47; }
        th { background: #7a5a47; color: #fff; }
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
        td button.edit { background: #eda276; }
        td button.edit:hover { background: #b38062; }
        td button.cancel { background: #ff5555; }
        td button.cancel:hover { background: #cc4444; }
        td button.confirm { background: #28a745; }
        td button.confirm:hover { background: #218838; }
        td button:disabled { background: #6c757d; cursor: not-allowed; }
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
        .close-modal-btn:hover { color: #eda276; }
        .modal-content label { display: block; margin: 10px 0 5px; }
        .modal-content input, .modal-content select {
            width: 100%;
            padding: 8px;
            border: none;
            border-radius: 5px;
            background: #2c1f1a;
            color: #b3a29b;
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
        .modal-action-btn:hover { background: #b38062; }
        .modal-action-btn.cancel { background: #ff5555; }
        .modal-action-btn.cancel:hover { background: #cc4444; }
        .status-message {
            margin: 10px 0;
            padding: 10px;
            border-radius: 5px;
            display: <?php echo $status ? 'block' : 'none'; ?>;
            background: <?php echo $status === 'success' ? '#28a745' : '#ff5555'; ?>;
            color: #fff;
        }
        .custom-unavailability-btn {
            background: #eda276;
            color: #fff;
            border: none;
            padding: 10px 20px;
            border-radius: 20px;
            cursor: pointer;
            transition: background 0.3s ease;
            font-size: 14px;
            margin-bottom: 10px;
        }
        .custom-unavailability-btn:hover { background: #b38062; }

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
    </style>
</head>
<body>
    <header>
        <div class="logo"><img src="Logotipo/LogoGrande.png" alt="Rotation Barber Logo"></div>
        <nav><ul class="nav-links">
            <li><a href="rotationbarber.html">Home</a></li>
            <li><a href="rotationbarber.html#about">Sobre Nós</a></li>
            <li><a href="rotationbarber.html#servicos">Serviços</a></li>
            <li><a href="rotationbarber.html#pricing">Preços</a></li>
            <li><a href="rotationbarber.html#gallery">Galeria</a></li>
            <li><a href="rotationbarber.html#team">Equipa</a></li>
        </ul></nav>
        <form action="logout.php" method="POST" style="display: inline;">
            <button type="submit" class="logout-btn">Sair</button>
        </form>
    </header>

    <div class="dashboard-container">
        <div class="barber-profile">
            <img src="<?php echo htmlspecialchars($barber_image); ?>" alt="Foto do Barbeiro">
            <h2>Bem-vindo, <?php echo htmlspecialchars($barber_name); ?>!</h2>
        </div>

        <h1>Painel de Controle - Barbeiro - Rotation Barber</h1>
        <p>Gerencie os seus agendamentos e defina a sua indisponibilidade aqui.</p>

        <?php if ($status): ?>
            <div class="status-message"><?php echo htmlspecialchars($message); ?></div>
        <?php endif; ?>

        <div class="tabs">
            <button class="tab-btn active" onclick="openTab('appointments')">Agendamentos</button>
            <button class="tab-btn" onclick="openTab('calendar')">Calendário</button>
            <button class="tab-btn" onclick="openTab('unavailability')">Indisponibilidade</button>
        </div>

        <div id="appointments" class="tab-content active">
            <h2>Meus Agendamentos</h2>
            <table>
                <thead><tr><th>Cliente</th><th>Serviço</th><th>Data</th><th>Horário</th><th>Pagamento</th><th>Estado</th><th>Ações</th></tr></thead>
                <tbody>
                    <?php foreach ($appointments as $appointment): ?>
                        <tr>
                            <td><?php echo htmlspecialchars($appointment['cliente_email'] ?? 'Sem cliente'); ?></td>
                            <td><?php echo htmlspecialchars($appointment['servico'] ?? 'Sem serviço'); ?></td>
                            <td><?php echo htmlspecialchars($appointment['data'] ?? 'Sem data'); ?></td>
                            <td><?php echo htmlspecialchars($appointment['horario'] ?? 'Sem horário'); ?></td>
                            <td><?php echo htmlspecialchars($appointment['pagamento'] === 'local' ? 'Pagar no local' : 'Online'); ?></td>
                            <td><?php echo htmlspecialchars($appointment['estado'] ?? 'Sem estado'); ?></td>
                            <td>
                                <?php $isEditable = !in_array($appointment['estado'], ['cancelado', 'concluído']); ?>
                                <button class="edit" onclick="editAppointment(<?php echo $appointment['id']; ?>, '<?php echo $appointment['data']; ?>', '<?php echo $appointment['horario']; ?>', <?php echo $barber_id; ?>)" <?php echo $isEditable ? '' : 'disabled'; ?>>Editar</button>
                                <button class="cancel" onclick="cancelAppointment(<?php echo $appointment['id']; ?>)" <?php echo $isEditable ? '' : 'disabled'; ?>>Cancelar</button>
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
                    <button onclick="changeWeek(-1)">Anterior</button>
                    <span id="weekRange">Semana Atual</span>
                    <button onclick="changeWeek(1)">Próxima</button>
                </div>
                <div class="calendar-container" id="calendarContainer">
                    <div class="calendar-grid" id="calendarGrid"></div>
                </div>
                <div class="calendar-legend">
                    <div class="legend-item">
                        <span class="legend-color" style="background-color: <?php echo $barber_colors[$barber_id]; ?>;"></span>
                        <?php echo htmlspecialchars($barber_email); ?>
                    </div>
                </div>
            </div>
        </div>

        <div id="unavailability" class="tab-content">
            <h2>Definir Indisponibilidade</h2>
            <button class="custom-unavailability-btn" onclick="openUnavailabilityModal()">Adicionar Indisponibilidade</button>
            <table>
                <thead><tr><th>Data</th><th>Ações</th></tr></thead>
                <tbody>
                    <?php foreach ($unavailabilities as $unavailability): ?>
                        <tr data-id="<?php echo $unavailability['id']; ?>">
                            <td><?php echo htmlspecialchars($unavailability['data']); ?></td>
                            <td><button class="cancel" onclick="removeUnavailability(<?php echo $unavailability['id']; ?>)">Remover</button></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>

        <div id="editModal" class="modal">
            <div class="modal-content">
                <span class="close-modal-btn" onclick="closeModal()">×</span>
                <h3>Editar Agendamento</h3>
                <input type="hidden" id="editAppointmentId">
                <label>Data: <input type="date" id="editDate" required></label>
                <label>Horário: <select id="editTime" required></select></label>
                <button class="modal-action-btn" onclick="saveEditedAppointment()">Salvar</button>
                <button class="modal-action-btn cancel" onclick="closeModal()">Cancelar</button>
            </div>
        </div>

        <div id="unavailabilityModal" class="modal">
            <div class="modal-content">
                <span class="close-modal-btn" onclick="closeUnavailabilityModal()">×</span>
                <h3>Definir Indisponibilidade</h3>
                <input type="hidden" id="unavailabilityId">
                <label>Data: <input type="date" id="unavailabilityDate" required></label>
                <button class="modal-action-btn" onclick="saveUnavailability()">Salvar</button>
                <button class="modal-action-btn cancel" onclick="closeUnavailabilityModal()">Cancelar</button>
            </div>
        </div>

        <!-- Modal para detalhes do agendamento -->
        <div id="appointmentDetailsModal" class="modal">
            <div class="modal-content">
                <span class="close-modal-btn" onclick="closeAppointmentDetailsModal()">×</span>
                <h3>Detalhes do Agendamento</h3>
                <p><strong>Cliente:</strong> <span id="detailClient"></span></p>
                <p><strong>Serviço:</strong> <span id="detailService"></span></p>
                <p><strong>Data:</strong> <span id="detailDate"></span></p>
                <p><strong>Horário:</strong> <span id="detailTime"></span></p>
                <p><strong>Pagamento:</strong> <span id="detailPayment"></span></p>
                <p><strong>Estado:</strong> <span id="detailStatus"></span></p>
                <button class="modal-action-btn" onclick="editFromDetails()">Editar</button>
                <button class="modal-action-btn cancel" onclick="closeAppointmentDetailsModal()">Fechar</button>
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
                            block.innerHTML = `${appointment.cliente_email} - ${appointment.servico}`;
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
            document.getElementById('detailService').textContent = appointment.servico || 'Sem serviço';
            document.getElementById('detailDate').textContent = appointment.data || 'Sem data';
            document.getElementById('detailTime').textContent = appointment.horario || 'Sem horário';
            document.getElementById('detailPayment').textContent = appointment.pagamento === 'local' ? 'Pagar no local' : 'Online';
            document.getElementById('detailStatus').textContent = appointment.estado || 'Sem estado';
            document.getElementById('appointmentDetailsModal').dataset.appointmentId = appointment.id;
            document.getElementById('appointmentDetailsModal').dataset.currentDate = appointment.data;
            document.getElementById('appointmentDetailsModal').dataset.currentTime = appointment.horario;
        }

        function closeAppointmentDetailsModal() {
            document.getElementById('appointmentDetailsModal').style.display = 'none';
        }

        function editFromDetails() {
            const modal = document.getElementById('appointmentDetailsModal');
            const appointmentId = modal.dataset.appointmentId;
            const currentDate = modal.dataset.currentDate;
            const currentTime = modal.dataset.currentTime;
            closeAppointmentDetailsModal();
            editAppointment(appointmentId, currentDate, currentTime, <?php echo $barber_id; ?>);
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
            timeSelect.innerHTML = '<option value="">A carregar horários...</option>';
            fetch(`check-availability.php?barber=${barberId}&date=${date}&appointmentId=${appointmentId}`)
                .then(response => response.json())
                .then(data => {
                    timeSelect.innerHTML = '';
                    if (data.success) {
                        const availableTimes = data.availableTimes;
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

        function saveEditedAppointment() {
            const appointmentId = document.getElementById('editAppointmentId').value;
            const newDate = document.getElementById('editDate').value;
            const newTime = document.getElementById('editTime').value;
            if (!newDate || !newTime) {
                alert('Por favor, selecione uma data e horário válidos.');
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

        function closeModal() { document.getElementById('editModal').style.display = 'none'; }
        function cancelAppointment(id) {
            if (confirm('Tem a certeza de que quer cancelar este agendamento?')) {
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

        function openUnavailabilityModal() {
            document.getElementById('unavailabilityModal').style.display = 'flex';
            document.getElementById('unavailabilityId').value = '';
            document.getElementById('unavailabilityDate').value = '';
        }

        function closeUnavailabilityModal() {
            document.getElementById('unavailabilityModal').style.display = 'none';
        }

        function saveUnavailability() {
            const date = document.getElementById('unavailabilityDate').value;
            if (!date) {
                alert('Por favor, preencha o campo de data.');
                return;
            }
            const data = {
                barbeiro_id: <?php echo $barber_id; ?>,
                data: date,
                hora_inicio: '00:00:00', // Dia inteiro
                hora_fim: '23:59:59'     // Dia inteiro
            };
            fetch('set-unavailability.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify(data)
            })
            .then(response => response.json())
            .then(data => {
                alert(data.message);
                if (data.success) {
                    closeUnavailabilityModal();
                    window.location.reload();
                }
            })
            .catch(error => {
                console.error('Erro ao definir indisponibilidade:', error);
                alert('Ocorreu um erro ao tentar definir a indisponibilidade.');
            });
        }

        function removeUnavailability(id) {
            if (confirm('Tem a certeza de que quer remover esta indisponibilidade?')) {
                fetch('remove-unavailability.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ id: id })
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        alert('Indisponibilidade removida com sucesso!');
                        window.location.reload();
                    } else {
                        alert(data.message || 'Erro ao remover indisponibilidade.');
                    }
                })
                .catch(error => {
                    console.error('Erro ao remover indisponibilidade:', error);
                    alert('Erro ao conectar ao servidor. Tente novamente.');
                });
            }
        }

        document.addEventListener('DOMContentLoaded', () => {
            renderCalendar();
        });
    </script>
</body>
</html>