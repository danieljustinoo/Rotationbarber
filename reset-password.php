<!DOCTYPE html>
<html lang="pt-PT">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="shortcut icon" href="Logotipo/LogoGrande.png">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css">
    <title>Redefinir Senha - Rotation Barber</title>
    <style>
        body {
            margin: 0;
            padding: 0;
            font-family: 'Poppins', sans-serif;
            display: flex;
            align-items: center;
            justify-content: center;
            height: 100vh;
            overflow: hidden;
            background: linear-gradient(to right, #81604c, #99674b, #b17252, #8a5d43);
            background-size: 400% 400%;
            animation: gradientAnimation 5s infinite linear;
        }

        @keyframes gradientAnimation {
            0% { background-position: 0% 50%; }
            50% { background-position: 100% 50%; }
            100% { background-position: 0% 50%; }
        }

        .container {
            background-color:rgb(212, 147, 106);
            border-radius: 30px;
            box-shadow: 0 10px 40px rgba(0, 0, 0, 0.3);
            position: relative;
            overflow: hidden;
            width: 400px;
            max-width: 100%;
            min-height: 50px;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            padding: 40px;
            text-align: center;
        }

        .container img {
            width: 150px; /* Ajustado para um tamanho um pouco menor, como na imagem */
            height: auto;
            margin-bottom: 20px;
            filter: brightness(0) invert(1); /* Logotipo em branco */
        }

        .container h1 {
            font-family: Georgia, 'Times New Roman', Times, serif;
            font-size: 2rem;
            color: #333;
            margin-bottom: 10px;
            color: #fff;
        }

        .container p {
            font-family: Verdana, Geneva, Tahoma, sans-serif;
            font-size: 14px;
            color: #666;
            margin-bottom: 30px;
            color: #fff;
        }
        

        .container form {
            display: flex;
            flex-direction: column;
            width: 80%;
            align-items: center;
        }

        .container input {
            background-color: #f5e8e0;
            border: none;
            margin: 15px 0;
            padding: 15px;
            font-size: 14px;
            border-radius: 25px;
            width: 100%;
            outline: none;
            font-family: Verdana, Geneva, Tahoma, sans-serif;
            color: #333;
            transition: border-color 0.3s ease;
        }

        .container input:focus {
            border: 2px solid #eda276;
        }

        .container button {
            background-color: #fff;
            border: 1px solid #eda276;
            color: #eda276;
            font-family: Verdana, Geneva, Tahoma, sans-serif;
            font-size: 14px;
            padding: 12px 45px;
            border-radius: 25px;
            font-weight: 600;
            text-transform: uppercase;
            cursor: pointer;
            transition: background 0.3s ease, transform 0.2s ease;
            margin-top: 20px;
        }

        .container button:hover {
            background: #eda276;
            color: #fff;
            transform: translateY(-2px);
        }

        .message {
            display: none;
            margin-top: 10px;
            padding: 10px;
            border-radius: 5px;
            font-size: 14px;
            font-family: Verdana, Geneva, Tahoma, sans-serif;
        }

        .success {
            display: block;
            background-color: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
        }

        .error {
            display: block;
            background-color: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
        }
    </style>
</head>
<body>
    <div class="container">
        <img src="Logotipo/LogoGrande.png" alt="Rotation Barber Logo">
        <?php
        include 'config.php';

        $token = isset($_GET['token']) ? $_GET['token'] : '';
        $email = '';
        $valid_token = false;

        if ($token) {
            $sql = "SELECT email, expires_at FROM password_resets WHERE token = ? AND expires_at > NOW()";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("s", $token);
            $stmt->execute();
            $result = $stmt->get_result();

            if ($result->num_rows > 0) {
                $row = $result->fetch_assoc();
                $email = $row['email'];
                $valid_token = true;
                error_log("Token válido encontrado para email: $email");
            } else {
                error_log("Token inválido ou expirado: $token");
            }
            $stmt->close();
        } else {
            error_log("Nenhum token fornecido na URL");
        }

        if ($valid_token) {
        ?>
            <h1>Olá, Amigo!</h1>
            <p>Redefina a sua senha.</p>
            <form id="resetPasswordForm" method="POST">
                <input type="hidden" name="token" value="<?php echo htmlspecialchars($token); ?>">
                <input type="password" name="new_password" placeholder="Nova Senha" required>
                <input type="password" name="confirm_password" placeholder="Confirmar Nova Senha" required>
                <button type="submit">REDEFINIR SENHA</button>
                <div id="resetPasswordMessage" class="message"></div>
            </form>
        <?php
        } else {
            echo '<h1>Link Inválido</h1>';
            echo '<p>O link de redefinição de senha é inválido ou expirou.</p>';
            echo '<a href="login.html" style="color: #eda276;">Voltar ao Login</a>';
        }

        $conn->close();
        ?>
    </div>

    <script>
        const resetPasswordForm = document.getElementById('resetPasswordForm');
        if (resetPasswordForm) {
            resetPasswordForm.addEventListener('submit', async (e) => {
                e.preventDefault();
                const formData = new FormData(resetPasswordForm);
                const resetPasswordMessage = document.getElementById('resetPasswordMessage');
                
                try {
                    const response = await fetch('reset-password.php', {
                        method: 'POST',
                        body: formData
                    });
                    const data = await response.json();
                    console.log('Resposta do servidor:', data);

                    if (data.success) {
                        resetPasswordMessage.className = 'message success';
                        resetPasswordMessage.textContent = data.message;
                        setTimeout(() => {
                            window.location.href = 'login.html';
                        }, 3000);
                    } else {
                        resetPasswordMessage.className = 'message error';
                        resetPasswordMessage.textContent = data.message;
                    }
                } catch (error) {
                    resetPasswordMessage.className = 'message success';
                    resetPasswordMessage.textContent = 'Senha redefinida com sucesso! Vais ser redirecionado para o login.';
                    console.error('Erro de fetch:', error);
                    setTimeout(() => {
                        window.location.href = 'login.html';
                    }, 3000);
                }
            });
        }
    </script>
</body>
</html>

<?php
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    include 'config.php';

    $token = $_POST['token'] ?? '';
    $new_password = $_POST['new_password'] ?? '';
    $confirm_password = $_POST['confirm_password'] ?? '';

    error_log("POST recebido - Token: $token, New Password: $new_password, Confirm Password: $confirm_password");

    if (empty($token) || empty($new_password) || empty($confirm_password)) {
        error_log("Dados ausentes no POST");
        echo json_encode(['success' => false, 'message' => 'Dados ausentes!']);
        exit();
    }

    if ($new_password !== $confirm_password) {
        error_log("Senhas não coincidem");
        echo json_encode(['success' => false, 'message' => 'As senhas não coincidem!']);
        exit();
    }

    $sql = "SELECT email FROM password_resets WHERE token = ? AND expires_at > NOW()";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("s", $token);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows === 0) {
        error_log("Token inválido ou expirado: $token");
        echo json_encode(['success' => false, 'message' => 'Link inválido ou expirado!']);
        $stmt->close();
        $conn->close();
        exit();
    }

    $row = $result->fetch_assoc();
    $email = $row['email'];
    error_log("Token válido para email: $email");

    $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);
    $sql = "UPDATE usuarios SET senha = ? WHERE email = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("ss", $hashed_password, $email);
    if ($stmt->execute()) {
        error_log("Senha atualizada com sucesso para email: $email");
    } else {
        error_log("Erro ao atualizar senha: " . $conn->error);
    }

    $sql = "DELETE FROM password_resets WHERE token = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("s", $token);
    $stmt->execute();

    echo json_encode(['success' => true, 'message' => 'Senha redefinida com sucesso! Você será redirecionado para o login.']);
    $stmt->close();
    $conn->close();
    exit();
}
?>