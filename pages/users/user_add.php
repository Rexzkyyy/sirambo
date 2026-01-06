<?php
session_start();
require_once __DIR__ . '/../../config/config.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Validasi input
    if (empty($_POST['username']) || empty($_POST['email']) || empty($_POST['password'])) {
        $_SESSION['error'] = 'Username, email, dan password wajib diisi';
        header('Location: users.php');
        exit;
    }
    
    $username = $conn->real_escape_string(trim($_POST['username']));
    $email = $conn->real_escape_string(trim($_POST['email']));
    $password = password_hash($_POST['password'], PASSWORD_DEFAULT);
    $role = $conn->real_escape_string($_POST['role']);
    $kode_wilayah = !empty($_POST['kode_wilayah']) ? $conn->real_escape_string(trim($_POST['kode_wilayah'])) : NULL;
    $is_active = isset($_POST['is_active']) ? 1 : 0;
    $current_time = date('Y-m-d H:i:s');
    
    // Validasi email
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $_SESSION['error'] = 'Format email tidak valid';
        header('Location: users.php');
        exit;
    }
    
    // Validasi panjang username
    if (strlen($username) < 3 || strlen($username) > 50) {
        $_SESSION['error'] = 'Username harus 3-50 karakter';
        header('Location: users.php');
        exit;
    }
    
    // Cek apakah username sudah ada
    $check_sql = "SELECT id_user FROM users WHERE username = '$username'";
    $check_result = $conn->query($check_sql);
    
    if ($check_result->num_rows > 0) {
        $_SESSION['error'] = 'Username sudah terdaftar';
        header('Location: users.php');
        exit;
    }
    
    // Cek apakah email sudah ada
    $check_email_sql = "SELECT id_user FROM users WHERE email = '$email'";
    $check_email_result = $conn->query($check_email_sql);
    
    if ($check_email_result->num_rows > 0) {
        $_SESSION['error'] = 'Email sudah terdaftar';
        header('Location: users.php');
        exit;
    }
    
    // Validasi role
    $allowed_roles = ['ADMIN', 'KABKOTA', 'PROV', 'VIEWER']; // Sesuaikan dengan role yang ada di sistem
    if (!in_array($role, $allowed_roles)) {
        $_SESSION['error'] = 'Role tidak valid';
        header('Location: users.php');
        exit;
    }
    
    // Jika role bukan admin, pastikan kode_wilayah diisi
    if ($role != 'ADMIN' && empty($kode_wilayah)) { // Diubah dari 'admin' menjadi 'ADMIN'
        $_SESSION['error'] = 'Kode wilayah wajib diisi untuk role ' . $role;
        header('Location: users.php');
        exit;
    }
    
    // Insert user baru dengan prepared statement
    $stmt = $conn->prepare("INSERT INTO users (username, email, password, role, kode_wilayah, is_active, created_at, updated_at) 
                           VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
    
    if ($stmt) {
        // Perbaikan: tipe parameter ke-7 dan ke-8 diubah dari "i" menjadi "s"
        $stmt->bind_param("sssssiss", $username, $email, $password, $role, $kode_wilayah, $is_active, $current_time, $current_time);
        
        if ($stmt->execute()) {
            $_SESSION['success'] = 'User berhasil ditambahkan';
            
            // Log activity (opsional)
            // log_activity($_SESSION['user_id'], "Menambahkan user baru: $username");
            
        } else {
            $_SESSION['error'] = 'Gagal menambahkan user: ' . $stmt->error;
        }
        
        $stmt->close();
    } else {
        $_SESSION['error'] = 'Gagal mempersiapkan query: ' . $conn->error;
    }
    
    header('Location: users.php');
    exit;
} else {
    // Jika bukan POST request, redirect
    header('Location: users.php');
    exit;
}