<?php
session_start();
include 'config.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

require 'PHPMailer/src/Exception.php';
require 'PHPMailer/src/PHPMailer.php';
require 'PHPMailer/src/SMTP.php';

// Verificar se o usuário é admin
if (!isset($_SESSION['user_role']) || $_SESSION['user_role'] !== 'admin') {
    header("Location: login.html");
    exit();
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $subject = filter_var($_POST['subject'], FILTER_SANITIZE_STRING);
    $message = filter_var($_POST['message'], FILTER_SANITIZE_STRING);

    // Buscar todos os emails dos assinantes
    $sql = "SELECT email FROM subscribers";
    $result = $conn->query($sql);
    $subscribers = $result->fetch_all(MYSQLI_ASSOC);

    if (count($subscribers) > 0) {
        $mail = new PHPMailer(true);
        try {
            $mail->isSMTP();
            $mail->Host       = 'smtp.gmail.com';
            $mail->SMTPAuth   = true;
            $mail->Username   = 'borad0293@gmail.com';
            $mail->Password   = 'pyee hucj ppea oacy';
            $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
            $mail->Port       = 587;
            $mail->CharSet = 'UTF-8';


            $mail->setFrom('borad0293@gmail.com', 'Rotation Barber');
            $mail->isHTML(true);
            $mail->Subject = $subject;
            $mail->Body    = "<h2>Olá!</h2><p>$message</p><p>Com estilo,<br>Equipe Rotation Barber</p>";
            $mail->AltBody = "Olá!\n\n$message\n\nCom estilo,\nEquipe Rotation Barber";

            // Enviar para cada assinante
            $success_count = 0;
            foreach ($subscribers as $subscriber) {
                $mail->addAddress($subscriber['email']);
                if ($mail->send()) {
                    $success_count++;
                }
                $mail->clearAddresses(); // Limpar o endereço para o próximo envio
            }

            // Redirecionar com mensagem de sucesso
            header("Location: dashboard.php?status=success&message=" . urlencode("Newsletter enviada para $success_count assinantes com sucesso!"));
            exit();
        } catch (Exception $e) {
            header("Location: dashboard.php?status=error&message=" . urlencode("Erro ao enviar a newsletter: {$mail->ErrorInfo}"));
            exit();
        }
    } else {
        header("Location: dashboard.php?status=error&message=" . urlencode("Nenhum assinante encontrado."));
        exit();
    }
} else {
    header("Location: dashboard.php");
    exit();
}

$conn->close();
?>