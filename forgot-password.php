<?php
session_start();
include 'config.php';
require 'PHPMailer/src/PHPMailer.php';
require 'PHPMailer/src/SMTP.php';
require 'PHPMailer/src/Exception.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

$site_url = "http://localhost/PAP"; // Substitua pelo URL do seu site

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $email = filter_var($_POST['email'], FILTER_SANITIZE_EMAIL);

    // Verificar se o email existe no banco de dados
    $sql = "SELECT id FROM usuarios WHERE email = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows === 0) {
        echo json_encode(['success' => false, 'message' => 'Email não encontrado!']);
        $stmt->close();
        $conn->close();
        exit();
    }

    // Gerar um token único
    $token = bin2hex(random_bytes(50));
    $expires_at = date('Y-m-d H:i:s', strtotime('+1 hour'));

    // Armazenar o token no banco de dados
    $sql = "INSERT INTO password_resets (email, token, expires_at) VALUES (?, ?, ?)";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("sss", $email, $token, $expires_at);
    $stmt->execute();

    // Criar o link de redefinição
    $reset_link = "$site_url/reset-password.php?token=$token";

    // Configurar o PHPMailer
    $mail = new PHPMailer(true);
    try {
        // Configurações do servidor SMTP (exemplo com Gmail)
        $mail->isSMTP();
        $mail->Host = 'smtp.gmail.com'; // Substitua pelo seu servidor SMTP
        $mail->SMTPAuth = true;
        $mail->Username = 'borad0293@gmail.com'; // Substitua pelo seu email
        $mail->Password = 'pyee hucj ppea oacy'; // Substitua pela sua senha de aplicativo do Gmail
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
        $mail->Port = 587;
        $mail->CharSet = 'UTF-8';
        

        // Remetente e destinatário
        $mail->setFrom('borad0293@gmail.com', 'Rotation Barber');
        $mail->addAddress($email);

        // Conteúdo do email
        $mail->isHTML(true);
        $mail->Subject = 'Redefinição de Senha - Rotation Barber';
        $mail->Body = "
            <h2>Redefinição de Senha</h2>
            <p>Olá,</p>
            <p>Você solicitou a redefinição de sua senha. Clique no link abaixo para criar uma nova senha:</p>
            <p><a href='$reset_link'>Redefinir Senha</a></p>
            <p>Este link expira em 1 hora. Se você não solicitou esta redefinição, ignore este email.</p>
            <p>Atenciosamente,<br>Equipe Rotation Barber</p>
        ";
        $mail->AltBody = "Olá,\n\nVocê solicitou a redefinição de sua senha. Acesse o link: $reset_link\n\nEste link expira em 1 hora.\n\nAtenciosamente,\nEquipe Rotation Barber";

        $mail->send();
        echo json_encode(['success' => true, 'message' => 'Um link de redefinição foi enviado para o seu email!']);
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'message' => "Erro ao enviar o email: {$mail->ErrorInfo}"]);
    }

    $stmt->close();
    $conn->close();
    exit();
}
?>