

<?php
session_start(); // Mulai sesi untuk menyimpan status login
require_once __DIR__ . '/../app/config/config.php';
require_once __DIR__ . '/../app/config/database.php';
require_once __DIR__ . '/../app/core/Router.php';
require_once __DIR__ . '/../app/core/Controller.php';

// routes
$router = new Router();
require_once __DIR__ . '/../app/routes/web.php';

$router->dispatch($_SERVER['REQUEST_METHOD'], $_SERVER['REQUEST_URI']);
