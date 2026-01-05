 <?php
/**
 * Header Partial - SIRAMBO (Tema Navy Blue)
 */
if (session_status() === PHP_SESSION_NONE) session_start();
$title = $title ?? "Dashboard";
?>
<!doctype html>
<html lang="id" class="h-100">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?= htmlspecialchars($title) ?> | SIRAMBO</title>
    
    <!-- Fonts - Plus Jakarta Sans -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700&display=swap" rel="stylesheet">

    <!-- Framework CSS & Icons -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">

    <style>
        :root {
            --navy-primary: #05005d;    /* Navy Gelap */
            --navy-secondary: #090937ff;  /* Navy Sedang */
            --accent-blue: #0e3d87ff;     /* Biru Aksen */
            --sidebar-width: 280px;
            --navbar-height: 70px;
            --bg-light: #f8fafc;
        }

        body {
            font-family: 'Plus Jakarta Sans', sans-serif;
            background-color: var(--bg-light);
            color: #334155;
            margin: 0;
            overflow-x: hidden;
        }

        /* Struktur Layout Utama */
        .sirambo-wrapper {
            display: flex;
            min-height: 100vh;
        }

        /* Sidebar - Fixed di samping */
        .sirambo-sidebar {
            width: var(--sidebar-width);
            background: var(--navy-primary);
            color: rgba(255, 255, 255, 0.7);
            height: 100vh;
            position: fixed;
            top: 0;
            left: 0;
            z-index: 1050;
            transition: transform 0.3s ease;
            display: flex;
            flex-direction: column;
            border-right: 1px solid rgba(255,255,255,0.05);
        }

        /* Main Section - Bergeser agar tidak tertutup sidebar */
        .sirambo-main {
            flex: 1;
            margin-left: var(--sidebar-width);
            display: flex;
            flex-direction: column;
            min-width: 0;
            transition: margin-left 0.3s ease;
        }

        .sirambo-content-area {
            padding: 2rem;
            flex: 1;
        }

        /* Navbar - Sticky di atas konten utama */
        .sirambo-navbar {
            height: var(--navbar-height);
            background: #ffffff;
            border-bottom: 1px solid #e2e8f0;
            padding: 0 1.5rem;
            display: flex;
            align-items: center;
        }

        /* Logo Box */
        .sirambo-logo-square {
            background: var(--accent-blue);
            color: white;
            width: 38px;
            height: 38px;
            display: flex;
            align-items: center;
            justify-content: center;
            border-radius: 10px;
            font-weight: 800;
            box-shadow: 0 4px 6px -1px rgba(59, 130, 246, 0.3);
        }

        /* Card Custom */
        .card-sirambo {
            border: none;
            border-radius: 16px;
            box-shadow: 0 1px 3px rgba(0,0,0,0.05);
            transition: transform 0.2s;
        }
        .card-sirambo:hover {
            transform: translateY(-2px);
        }

        /* Responsive */
        @media (max-width: 991.98px) {
            .sirambo-sidebar {
                transform: translateX(-100%);
            }
            .sirambo-sidebar.active {
                transform: translateX(0);
            }
            .sirambo-main {
                margin-left: 0;
            }
        }
    </style>
</head>
<body class="d-flex flex-column h-100">