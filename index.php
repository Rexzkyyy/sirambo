<?php
session_start();

// Kalau sudah login → masuk dashboard
if (isset($_SESSION['id_user'])) {
    header("Location: pages/dashboard.php");
    exit();
}

// Kalau belum login → ke halaman login
header("Location: auth/login.php");
exit();
