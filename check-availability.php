<?php
session_start();
include 'config.php';

header('Content-Type: application/json');

// Habilitar exibição de erros para depuração (remover em produção)
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

if (!$conn || $conn->connect_error) {
    http_response_code(500);
    die(json_encode(['success' => false, 'message' => 'Erro na conexão com a base de dados']));
}

$barberId = isset($_GET['barber']) ? intval($_GET['barber']) : 0;
$date = isset($_GET['date']) ? $_GET['date'] : '';
$startDate = isset($_GET['startDate']) ? $_GET['startDate'] : '';
$endDate = isset($_GET['endDate']) ? $_GET['endDate'] : '';
$appointmentId = isset($_GET['appointmentId']) ? intval($_GET['appointmentId']) : 0;
$checkGeneralAvailability = isset($_GET['checkGeneralAvailability']) ? true : false;

if (!$barberId) {
    http_response_code(400);
    die(json_encode(['success' => false, 'message' => 'Barbeiro não especificado']));
}

function generateTimeSlots($startHour, $endHour, $interval) {
    $timeSlots = [];
    for ($hour = $startHour; $hour <= $endHour; $hour++) {
        for ($minute = 0; $minute < 60; $minute += $interval) {
            $timeSlots[] = sprintf("%02d:%02d", $hour, $minute);
        }
    }
    return $timeSlots;
}

function generateTimeSlotsBetween($start, $end, $interval_minutes) {
    $times = [];
    $start_time = strtotime($start);
    $end_time = strtotime($end);
    $interval_seconds = $interval_minutes * 60;

    if ($start_time === false || $end_time === false) {
        error_log("Erro ao converter horários: start=$start, end=$end");
        return $times;
    }

    while ($start_time < $end_time) { // Alterado para '<' para não incluir o último horário
        $times[] = date('H:i', $start_time);
        $start_time += $interval_seconds;
    }
    return $times;
}

if ($checkGeneralAvailability) {
    $startDate = date('Y-m-d');
    $endDate = date('Y-m-d', strtotime('+30 days'));

    // Buscar agendamentos confirmados
    $sql = "SELECT data, TIME_FORMAT(horario, '%H:%i') as horario FROM agendamentos WHERE barbeiro_id = ? AND data BETWEEN ? AND ? AND estado = 'confirmado'";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("iss", $barberId, $startDate, $endDate);
    $stmt->execute();
    $result = $stmt->get_result();
    $bookedSlots = [];
    while ($row = $result->fetch_assoc()) {
        $bookedSlots[$row['data']][] = $row['horario'];
    }
    $stmt->close();

    // Buscar indisponibilidades
    $sql_unavailability = "SELECT data, hora_inicio, hora_fim FROM barber_unavailability WHERE barbeiro_id = ? AND data BETWEEN ? AND ?";
    $stmt_unavailability = $conn->prepare($sql_unavailability);
    $stmt_unavailability->bind_param("iss", $barberId, $startDate, $endDate);
    $stmt_unavailability->execute();
    $result_unavailability = $stmt_unavailability->get_result();
    $unavailabilitySlots = [];
    while ($row = $result_unavailability->fetch_assoc()) {
        $unavailabilitySlots[$row['data']][] = [
            'hora_inicio' => $row['hora_inicio'],
            'hora_fim' => $row['hora_fim']
        ];
    }
    $stmt_unavailability->close();

    // Gerar intervalo de datas
    $start = new DateTime($startDate);
    $end = new DateTime($endDate);
    $interval = new DateInterval('P1D');
    $dateRange = new DatePeriod($start, $interval, $end->modify('+1 day'));
    $today = new DateTime();
    $hasAvailability = false;

    $startHour = 9;
    $endHour = 18;
    $intervalMinutes = 30;
    $totalSlotsPerDay = (($endHour - $startHour) * 60) / $intervalMinutes;
    $allTimes = generateTimeSlots($startHour, $endHour, $intervalMinutes);

    foreach ($dateRange as $date) {
        $dateStr = $date->format('Y-m-d');
        if ($date < $today) continue;

        $bookedTimes = isset($bookedSlots[$dateStr]) ? $bookedSlots[$dateStr] : [];
        $unavailableTimes = [];
        if (isset($unavailabilitySlots[$dateStr])) {
            foreach ($unavailabilitySlots[$dateStr] as $unavailability) {
                $interval_slots = generateTimeSlotsBetween($unavailability['hora_inicio'], $unavailability['hora_fim'], $intervalMinutes);
                $unavailableTimes = array_merge($unavailableTimes, $interval_slots);
            }
            $unavailableTimes = array_unique($unavailableTimes);
        }

        $occupiedTimes = array_unique(array_merge($bookedTimes, $unavailableTimes));
        $availableTimes = array_diff($allTimes, $occupiedTimes);

        if (!empty($availableTimes)) {
            $hasAvailability = true;
            break;
        }
    }

    echo json_encode(['success' => true, 'hasAvailability' => $hasAvailability]);
} elseif ($startDate && $endDate) {
    // Buscar datas com agendamentos confirmados
    $sql = "SELECT DISTINCT data FROM agendamentos WHERE barbeiro_id = ? AND data BETWEEN ? AND ? AND estado = 'confirmado'";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("iss", $barberId, $startDate, $endDate);
    $stmt->execute();
    $result = $stmt->get_result();
    $bookedDates = [];
    while ($row = $result->fetch_assoc()) {
        $bookedDates[] = $row['data'];
    }
    $stmt->close();

    // Buscar indisponibilidades
    $sql_unavailability = "SELECT data, hora_inicio, hora_fim FROM barber_unavailability WHERE barbeiro_id = ? AND data BETWEEN ? AND ?";
    $stmt_unavailability = $conn->prepare($sql_unavailability);
    $stmt_unavailability->bind_param("iss", $barberId, $startDate, $endDate);
    $stmt_unavailability->execute();
    $result_unavailability = $stmt_unavailability->get_result();
    $unavailabilitySlots = [];
    while ($row = $result_unavailability->fetch_assoc()) {
        $unavailabilitySlots[$row['data']][] = [
            'hora_inicio' => $row['hora_inicio'],
            'hora_fim' => $row['hora_fim']
        ];
    }
    $stmt_unavailability->close();

    // Gerar intervalo de datas
    $start = new DateTime($startDate);
    $end = new DateTime($endDate);
    $interval = new DateInterval('P1D');
    $dateRange = new DatePeriod($start, $interval, $end->modify('+1 day'));
    $availableDays = [];
    $today = new DateTime();

    $startHour = 9;
    $endHour = 18;
    $intervalMinutes = 30;
    $totalSlotsPerDay = (($endHour - $startHour) * 60) / $intervalMinutes;
    $allTimes = generateTimeSlots($startHour, $endHour, $intervalMinutes);

    foreach ($dateRange as $date) {
        $dateStr = $date->format('Y-m-d');
        if ($date < $today) continue;

        // Contar agendamentos confirmados
        $sqlCount = "SELECT COUNT(*) as count FROM agendamentos WHERE barbeiro_id = ? AND data = ? AND estado = 'confirmado'";
        $stmtCount = $conn->prepare($sqlCount);
        $stmtCount->bind_param("is", $barberId, $dateStr);
        $stmtCount->execute();
        $countResult = $stmtCount->get_result()->fetch_assoc();
        $bookingCount = $countResult['count'];
        $stmtCount->close();

        // Contar slots ocupados por indisponibilidades
        $unavailableSlotsCount = 0;
        if (isset($unavailabilitySlots[$dateStr])) {
            $unavailableTimes = [];
            foreach ($unavailabilitySlots[$dateStr] as $unavailability) {
                $interval_slots = generateTimeSlotsBetween($unavailability['hora_inicio'], $unavailability['hora_fim'], $intervalMinutes);
                $unavailableTimes = array_merge($unavailableTimes, $interval_slots);
            }
            $unavailableTimes = array_unique($unavailableTimes);
            $unavailableSlotsCount = count($unavailableTimes);
        }

        $totalOccupiedSlots = $bookingCount + $unavailableSlotsCount;

        if ($totalOccupiedSlots < $totalSlotsPerDay) {
            $availableDays[] = $dateStr;
        }
    }

    echo json_encode(['success' => true, 'availableDays' => $availableDays]);
} elseif ($date) {
    // Buscar agendamentos pendentes/confirmados
    $sql = "
        SELECT TIME_FORMAT(horario, '%H:%i') as horario 
        FROM agendamentos 
        WHERE barbeiro_id = ? AND data = ? AND estado IN ('pendente', 'confirmado')
    ";
    if ($appointmentId > 0) {
        $sql .= " AND id != ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("isi", $barberId, $date, $appointmentId);
    } else {
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("is", $barberId, $date);
    }
    $stmt->execute();
    $result = $stmt->get_result();
    $bookedTimes = [];
    while ($row = $result->fetch_assoc()) {
        $bookedTimes[] = $row['horario'];
    }
    $stmt->close();

    // Buscar agendamentos concluídos
    $sqlConcluded = "
        SELECT TIME_FORMAT(horario, '%H:%i') as horario 
        FROM agendamentos 
        WHERE barbeiro_id = ? AND data = ? AND estado = 'concluído'
    ";
    if ($appointmentId > 0) {
        $sqlConcluded .= " AND id != ?";
        $stmtConcluded = $conn->prepare($sqlConcluded);
        $stmtConcluded->bind_param("isi", $barberId, $date, $appointmentId);
    } else {
        $stmtConcluded = $conn->prepare($sqlConcluded);
        $stmtConcluded->bind_param("is", $barberId, $date);
    }
    $stmtConcluded->execute();
    $resultConcluded = $stmtConcluded->get_result();
    $concludedTimes = [];
    while ($row = $resultConcluded->fetch_assoc()) {
        $concludedTimes[] = $row['horario'];
    }
    $stmtConcluded->close();

    // Buscar indisponibilidades
    $sql_unavailability = "
        SELECT hora_inicio, hora_fim 
        FROM barber_unavailability 
        WHERE barbeiro_id = ? AND data = ?
    ";
    $stmt_unavailability = $conn->prepare($sql_unavailability);
    $stmt_unavailability->bind_param("is", $barberId, $date);
    $stmt_unavailability->execute();
    $result_unavailability = $stmt_unavailability->get_result();
    $unavailabilityTimes = [];
    while ($row = $result_unavailability->fetch_assoc()) {
        $start = $row['hora_inicio'];
        $end = $row['hora_fim'];
        $interval_slots = generateTimeSlotsBetween($start, $end, 30);
        $unavailabilityTimes = array_merge($unavailabilityTimes, $interval_slots);
    }
    $stmt_unavailability->close();
    $unavailabilityTimes = array_unique($unavailabilityTimes);

    // Gerar todos os horários possíveis (das 9h às 18h, com intervalo de 30 minutos)
    $startHour = 9;
    $endHour = 18;
    $intervalMinutes = 30;
    $allTimes = generateTimeSlots($startHour, $endHour, $intervalMinutes);

    // Combinar horários ocupados (pendentes/confirmados + indisponibilidades)
    $occupiedTimes = array_unique(array_merge($bookedTimes, $unavailabilityTimes));

    // Calcular horários disponíveis
    $availableTimes = array_diff($allTimes, $occupiedTimes);

    // Log para depuração
    error_log("Horários confirmados/pendentes para $barberId em $date: " . json_encode($bookedTimes));
    error_log("Horários concluídos para $barberId em $date: " . json_encode($concludedTimes));
    error_log("Horários indisponíveis para $barberId em $date: " . json_encode($unavailabilityTimes));
    error_log("Horários disponíveis para $barberId em $date: " . json_encode($availableTimes));

    // Retornar resposta com horários disponíveis
    echo json_encode([
        'success' => true, 
        'bookedTimes' => $bookedTimes, 
        'concludedTimes' => $concludedTimes,
        'unavailabilityTimes' => $unavailabilityTimes,
        'availableTimes' => array_values($availableTimes)
    ]);
} else {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Parâmetros inválidos']);
}

$conn->close();
?>