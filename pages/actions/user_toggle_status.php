<?php
require_once __DIR__ . '/../../config/config.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id = intval($_POST['id']);
    
    $sql = "UPDATE users SET is_active = NOT is_active WHERE id_user = $id";
    
    if ($conn->query($sql)) {
        echo json_encode(['success' => true, 'message' => 'Status berhasil diubah']);
    } else {
        echo json_encode(['success' => false, 'message' => 'Gagal mengubah status: ' . $conn->error]);
    }
}