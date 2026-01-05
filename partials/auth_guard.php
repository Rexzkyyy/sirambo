<?php
// partials/auth_guard.php
if (session_status() === PHP_SESSION_NONE) session_start();

if (!isset($_SESSION['id_user'])) {
    header("Location: /sirambo/auth/login.php");
    exit();
}
