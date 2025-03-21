<?php
session_start();

if (!isset($_SESSION['user_id']) || !isset($_SESSION['user_role'])) {
    echo json_encode(['success' => false, 'message' => 'Não autenticado']);
} else {
    echo json_encode([
        'success' => true,
        'role' => $_SESSION['user_role'],
        'user_name' => $_SESSION['user_name']
    ]);
}
?>