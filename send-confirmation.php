<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

require 'PHPMailer/src/Exception.php';
require 'PHPMailer/src/PHPMailer.php';
require 'PHPMailer/src/SMTP.php';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Receber dados do agendamento em formato JSON
    $input = file_get_contents('php://input');
    $data = json_decode($input, true);

    if (isset($data['client']['email']) && isset($data['service']) && isset($data['date']) && isset($data['time'])) {
        $email = filter_var($data['client']['email'], FILTER_SANITIZE_EMAIL);
        $name = filter_var($data['client']['name'], FILTER_SANITIZE_STRING);
        $service = filter_var($data['service'], FILTER_SANITIZE_STRING);
        $date = filter_var($data['date'], FILTER_SANITIZE_STRING);
        $time = filter_var($data['time'], FILTER_SANITIZE_STRING);
        $barber = isset($data['barberName']) ? filter_var($data['barberName'], FILTER_SANITIZE_STRING) : 'Qualquer disponível';

        if (filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $mail = new PHPMailer(true);
            try {
                $mail->isSMTP();
                $mail->Host       = 'smtp.gmail.com';
                $mail->SMTPAuth   = true;
                $mail->Username   = 'borad0293@gmail.com'; // Substitua pelo seu e-mail
                $mail->Password   = 'pyee hucj ppea oacy'; // Substitua pela sua senha de app
                $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
                $mail->Port       = 587;

                $mail->setFrom('borad0293@gmail.com', 'Rotation Barber');
                $mail->addAddress($email, $name);

                $mail->isHTML(true);
                $mail->Subject = 'Confirmação de Agendamento - Rotation Barber';

                // Obter o nome do serviço a partir do ID (se necessário, mapear para o nome real)
                $serviceName = '';
                $servicesMap = [
                    'hair-cut' => 'Hair Cutting & Fitting ($89)',
                    'hair-style' => 'Hair Styling ($60)',
                    'beard-trim' => 'Beard Trim ($30)',
                    'beard-shape' => 'Beard Shaping ($45)',
                    'shaving-facial' => 'Shaving & Facial ($45)'
                ];
                $serviceName = isset($servicesMap[$service]) ? $servicesMap[$service] : $service;

                $mail->Body    = "
                    <h2>Olá, $name!</h2>
                    <p>O seu agendamento foi confirmado com sucesso na Rotation Barber!</p>
                    <p><strong>Detalhes do Agendamento:</strong></p>
                    <ul>
                        <li><strong>Serviço:</strong> $serviceName</li>
                        <li><strong>Barbeiro:</strong> $barber</li>
                        <li><strong>Data:</strong> $date</li>
                        <li><strong>Horário:</strong> $time</li>
                    </ul>
                    <p>Estamos ansiosos para atendê-lo(a). Se precisar de alterar ou cancelar, entre em contato conosco.</p>
                    <p>Com estilo,<br>Equipe Rotation Barber</p>
                ";
                $mail->AltBody = "Olá, $name!\n\nO seu agendamento foi confirmado com sucesso na Rotation Barber!\n\nDetalhes do Agendamento:\n- Serviço: $serviceName\n- Barbeiro: $barber\n- Data: $date\n- Horário: $time\n\nEstamos ansiosos para atendê-lo(a). Se precisar de alterar ou cancelar, entre em contato conosco.\n\nCom estilo,\nEquipe Rotation Barber";

                $mail->send();
                
                // Retornar sucesso em formato JSON
                echo json_encode(['success' => true, 'message' => 'E-mail de confirmação enviado com sucesso!']);
                exit();
            } catch (Exception $e) {
                echo json_encode(['success' => false, 'message' => 'Erro ao enviar e-mail: ' . $mail->ErrorInfo]);
                exit();
            }
        } else {
            echo json_encode(['success' => false, 'message' => 'E-mail inválido']);
            exit();
        }
    } else {
        echo json_encode(['success' => false, 'message' => 'Dados incompletos']);
        exit();
    }
} else {
    echo json_encode(['success' => false, 'message' => 'Método inválido']);
    exit();
}
?>