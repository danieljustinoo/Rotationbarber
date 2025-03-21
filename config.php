<?php
// Configurações do banco de dados
$host = 'localhost'; // Endereço do servidor MySQL
$dbname = 'rotation_barber'; // Nome do banco de dados
$username = 'root'; // Usuário do MySQL
$password = ''; // Senha do MySQL

// Conexão com o banco de dados
$conn = new mysqli($host, $username, $password, $dbname);

// Verificar a conexão
if ($conn->connect_error) {
    die("Falha na conexão: " . $conn->connect_error);
}

// Definir o charset para UTF-8
$conn->set_charset("utf8");
?>