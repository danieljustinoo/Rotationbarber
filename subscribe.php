<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Incluir a conexão com o banco de dados
include 'config.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

require 'PHPMailer/src/Exception.php';
require 'PHPMailer/src/PHPMailer.php';
require 'PHPMailer/src/SMTP.php';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    if (isset($_POST["email"])) {
        $email = filter_var($_POST["email"], FILTER_SANITIZE_EMAIL);
        
        if (filter_var($email, FILTER_VALIDATE_EMAIL)) {
            // Verificar se o email já está na tabela subscribers
            $sql = "SELECT email FROM subscribers WHERE email = ?";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("s", $email);
            $stmt->execute();
            $result = $stmt->get_result();

            if ($result->num_rows > 0) {
                // Email já registrado
                echo "Este email já está registrado.<br>";
                sleep(5);
                header("Location: RotationBarber.html?status=error&message=" . urlencode("Este email já está registrado."));
                exit();
            }

            // Inserir o email na tabela subscribers
            $sql = "INSERT INTO subscribers (email) VALUES (?)";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("s", $email);
            
            if ($stmt->execute()) {
                // Enviar email de confirmação
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
                    $mail->addAddress($email);

                    $mail->isHTML(true);
                    $mail->Subject = 'Bem-vindo à Rotation Barber!';
                    $mail->Body    = "<h2>Olá!</h2><p>Obrigado por subscrever a nossa newsletter. Fique atento às novidades e promoções da Rotation Barber!</p><p>Com estilo,<br>Equipe Rotation Barber</p>";
                    $mail->AltBody = "Olá!\n\nObrigado por subscrever a nossa newsletter. Fique atento às novidades e promoções da Rotation Barber!\n\nCom estilo,\nEquipe Rotation Barber";

                    $mail->send();
                    
                    // Exibir mensagem de agradecimento com contagem regressiva
                    echo "
                        <!DOCTYPE html>
                        <html lang='pt'>
                        <head>
                            <meta charset='UTF-8'>
                            <meta name='viewport' content='width=device-width, initial-scale=1.0'>
                            <meta http-equiv='refresh' content='5;url=RotationBarber.html'>
                            <title>Obrigado!</title>
                            <style>
                                body {
                                    font-family: Verdana, Geneva, Tahoma, sans-serif;
                                    background: rgb(155, 107, 77);
                                    color: #fff;
                                    display: flex;
                                    justify-content: center;
                                    align-items: center;
                                    height: 100vh;
                                    margin: 0;
                                }
                                .thank-you {
                                    text-align: center;
                                    padding: 20px;
                                    background: rgba(0, 0, 0, 0.7);
                                    border-radius: 10px;
                                }
                                .thank-you h1 {
                                    font-size: 2rem;
                                    margin-bottom: 10px;
                                }
                                .thank-you p {
                                    font-size: 1rem;
                                }
                                #countdown {
                                    font-weight: bold;
                                    color: #eda276;
                                }
                            </style>
                        </head>
                        <body>
                            <div class='thank-you'>
                                <h1>Obrigado por subscrever!</h1>
                                <p>Você agora faz parte da Rotation Barber. Fique atento às nossas novidades e promoções no seu email: $email</p>
                                <p>Será redirecionado em <span id='countdown'>5</span> segundos...</p>
                            </div>
                            <script>
                                let timeLeft = 5;
                                const countdown = document.getElementById('countdown');
                                const timer = setInterval(() => {
                                    timeLeft--;
                                    countdown.textContent = timeLeft;
                                    if (timeLeft <= 0) {
                                        clearInterval(timer);
                                    }
                                }, 1000);
                            </script>
                        </body>
                        </html>
                    ";
                    exit();
                } catch (Exception $e) {
                    echo "Erro ao enviar: " . $mail->ErrorInfo . "<br>";
                    sleep(5);
                    header("Location: RotationBarber.html?status=error&message=" . urlencode("Erro ao enviar: {$mail->ErrorInfo}"));
                    exit();
                }
            } else {
                echo "Erro ao salvar o email na base de dados.<br>";
                sleep(5);
                header("Location: RotationBarber.html?status=error&message=" . urlencode("Erro ao salvar o email."));
                exit();
            }
        } else {
            echo "Email inválido: $email<br>";
            sleep(5);
            header("Location: RotationBarber.html?status=invalid");
            exit();
        }
    } else {
        echo "Campo email não recebido<br>";
        sleep(5);
        header("Location: RotationBarber.html?status=error&message=" . urlencode("Email não fornecido"));
        exit();
    }
} else {
    echo "Acesso direto ao script<br>";
    sleep(5);
    header("Location: RotationBarber.html");
    exit();
}
?>