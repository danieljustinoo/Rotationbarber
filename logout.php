<?php
session_start();
include 'config.php';

if (!$conn || $conn->connect_error) {
    die('Erro na conexão com o banco de dados');
}

// Destruir a sessão
session_destroy();

// Redirecionar para rotationbarber.html
header("Location: rotationbarber.html");
exit();
?>