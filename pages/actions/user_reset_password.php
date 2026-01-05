<?php
require_once __DIR__ . '/../../config/config.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id = intval($_POST['id']);
    $default_password = password_hash('password123', PASSWORD_DEFAULT);
    
    $sql = "UPDATE users SET password = '$default_password' WHERE id_user = $id";
    
    if ($conn->query($sql)) {
        echo json_encode(['success' => true, 'message' => 'Password berhasil direset ke default (password123)']);
    } else {
        echo json_encode(['success' => false, 'message' => 'Gagal reset password: ' . $conn->error]);
    }
}