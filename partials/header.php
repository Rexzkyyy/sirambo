<?php
// partials/header.php
if (session_status() === PHP_SESSION_NONE) session_start();

$title = $title ?? "SIRAMBO";
$page  = $page  ?? ""; // untuk menu active
$user_name = $_SESSION['username'] ?? 'Pengguna';
$user_role = $_SESSION['role'] ?? 'user';
?>
<!doctype html>
<html lang="id">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <meta name="description" content="Sistem Rilis dan Rekonsiliasi PDRB Online">
  <title><?= htmlspecialchars($title) ?> - SIRAMBO</title>

  <!-- Inter font untuk tampilan lebih bersih -->
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">

  <!-- Bootstrap 5 via CDN (ringan & stabil) -->
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">

  <!-- Bootstrap Icons (lebih ringan dari FontAwesome) -->
  <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">

  <!-- CSS custom -->
  <link href="/sirambo/assets/css/app.css" rel="stylesheet">
  
  <style>
    :root {
      --sirambo: #0b3a7e;
      --sirambo-light: #3b82f6;
      --sirambo-soft: #eff6ff;
      --sirambo-gradient: linear-gradient(135deg, #0b3a7e 0%, #2563eb 100%);
      --sidebar-width: 260px;
      --topbar-height: 70px;
      --border-radius: 12px;
      --shadow-sm: 0 1px 3px rgba(0,0,0,0.12);
      --shadow-md: 0 4px 6px -1px rgba(0,0,0,0.1);
      --shadow-lg: 0 10px 15px -3px rgba(0,0,0,0.1);
    }
    
    * {
      font-family: 'Inter', -apple-system, BlinkMacSystemFont, sans-serif;
    }
    
    body {
      background-color: #f8fafc;
      color: #334155;
      line-height: 1.6;
    }
    
    /* ===== TOPBAR ===== */
    .sirambo-topbar {
      background: var(--sirambo-gradient);
      height: var(--topbar-height);
      backdrop-filter: blur(10px);
      border-bottom: 1px solid rgba(255,255,255,0.1);
      position: sticky;
      top: 0;
      z-index: 1030;
      box-shadow: 0 4px 20px rgba(11,58,126,0.15);
    }
    
    .sirambo-logo {
      display: inline-flex;
      align-items: center;
      gap: 12px;
      color: white;
      font-weight: 700;
      font-size: 1.5rem;
      text-decoration: none;
    }
    
    .logo-icon {
      width: 36px;
      height: 36px;
      background: rgba(255,255,255,0.2);
      border: 2px solid rgba(255,255,255,0.3);
      border-radius: 10px;
      display: flex;
      align-items: center;
      justify-content: center;
      font-weight: 800;
      font-size: 1.2rem;
      backdrop-filter: blur(5px);
    }
    
    .user-dropdown {
      background: rgba(255,255,255,0.15);
      border: 1px solid rgba(255,255,255,0.2);
      border-radius: 10px;
      padding: 8px 16px;
      color: white;
      transition: all 0.3s ease;
    }
    
    .user-dropdown:hover {
      background: rgba(255,255,255,0.25);
    }
    
    /* ===== LAYOUT ===== */
    .sirambo-layout {
      display: grid;
      grid-template-columns: var(--sidebar-width) 1fr;
      min-height: calc(100vh - var(--topbar-height));
    }
    
    /* ===== SIDEBAR ===== */
    .sirambo-sidebar {
      background: white;
      border-right: 1px solid #e2e8f0;
      position: sticky;
      top: var(--topbar-height);
      height: calc(100vh - var(--topbar-height));
      overflow-y: auto;
      box-shadow: var(--shadow-sm);
      z-index: 1020;
    }
    
    .sidebar-header {
      padding: 24px 20px 16px;
      border-bottom: 1px solid #f1f5f9;
    }
    
    .user-info {
      display: flex;
      align-items: center;
      gap: 12px;
    }
    
    .user-avatar {
      width: 44px;
      height: 44px;
      background: var(--sirambo-gradient);
      border-radius: 12px;
      display: flex;
      align-items: center;
      justify-content: center;
      color: white;
      font-weight: 600;
      font-size: 1.2rem;
    }
    
    .user-details h6 {
      margin: 0;
      font-weight: 600;
      color: #1e293b;
    }
    
    .user-details small {
      color: #64748b;
      font-size: 0.85rem;
    }
    
    .sidebar-nav {
      padding: 20px 16px;
    }
    
    .nav-section {
      margin-bottom: 24px;
    }
    
    .nav-section-title {
      color: #64748b;
      font-size: 0.75rem;
      text-transform: uppercase;
      letter-spacing: 0.5px;
      font-weight: 600;
      margin-bottom: 12px;
      padding-left: 12px;
    }
    
    .sirambo-nav {
      display: flex;
      align-items: center;
      gap: 12px;
      padding: 12px 16px;
      border-radius: var(--border-radius);
      text-decoration: none;
      color: #475569;
      margin-bottom: 4px;
      transition: all 0.2s ease;
      font-weight: 500;
    }
    
    .sirambo-nav:hover {
      background-color: #f1f5f9;
      color: var(--sirambo);
      transform: translateX(4px);
    }
    
    .sirambo-nav.active {
      background: linear-gradient(135deg, #eff6ff 0%, #dbeafe 100%);
      color: var(--sirambo);
      font-weight: 600;
      border-left: 4px solid var(--sirambo);
      box-shadow: var(--shadow-sm);
    }
    
    .nav-icon {
      width: 20px;
      height: 20px;
      display: flex;
      align-items: center;
      justify-content: center;
      color: inherit;
    }
    
    /* ===== CONTENT AREA ===== */
    .sirambo-content {
      padding: 30px;
      background: #f8fafc;
      min-height: calc(100vh - var(--topbar-height));
    }
    
    .content-header {
      display: flex;
      justify-content: space-between;
      align-items: center;
      margin-bottom: 30px;
    }
    
    .page-title h1 {
      font-size: 1.75rem;
      font-weight: 700;
      color: #1e293b;
      margin: 0;
    }
    
    .page-title p {
      color: #64748b;
      margin: 8px 0 0;
    }
    
    /* ===== CARDS ===== */
    .sirambo-card {
      background: white;
      border: 1px solid #e2e8f0;
      border-radius: var(--border-radius);
      box-shadow: var(--shadow-md);
      transition: all 0.3s ease;
      overflow: hidden;
    }
    
    .sirambo-card:hover {
      box-shadow: var(--shadow-lg);
      transform: translateY(-2px);
    }
    
    .card-header {
      background: #f8fafc;
      border-bottom: 1px solid #e2e8f0;
      padding: 20px 24px;
      display: flex;
      justify-content: space-between;
      align-items: center;
    }
    
    .card-header h5 {
      margin: 0;
      font-weight: 600;
      color: #1e293b;
    }
    
    .card-body {
      padding: 24px;
    }
    
    /* ===== BADGES ===== */
    .sirambo-badge {
      background: var(--sirambo-gradient);
      color: white;
      padding: 6px 12px;
      border-radius: 20px;
      font-size: 0.85rem;
      font-weight: 600;
    }
    
    /* ===== BUTTONS ===== */
    .btn-sirambo {
      background: var(--sirambo-gradient);
      color: white;
      border: none;
      padding: 10px 20px;
      border-radius: var(--border-radius);
      font-weight: 600;
      transition: all 0.3s ease;
    }
    
    .btn-sirambo:hover {
      color: white;
      transform: translateY(-2px);
      box-shadow: 0 6px 12px rgba(11,58,126,0.2);
    }
    
    /* ===== RESPONSIVE ===== */
    @media (max-width: 992px) {
      .sirambo-layout {
        grid-template-columns: 1fr;
      }
      
      .sirambo-sidebar {
        position: fixed;
        left: -100%;
        top: var(--topbar-height);
        width: 280px;
        transition: left 0.3s ease;
        z-index: 1050;
        box-shadow: var(--shadow-lg);
      }
      
      .sirambo-sidebar.active {
        left: 0;
      }
      
      .mobile-menu-btn {
        display: block;
      }
      
      .sirambo-content {
        padding: 20px;
      }
    }
    
    @media (max-width: 768px) {
      .sirambo-content {
        padding: 16px;
      }
      
      .content-header {
        flex-direction: column;
        align-items: flex-start;
        gap: 16px;
      }
    }
  </style>
</head>
<body class="sirambo-body">
