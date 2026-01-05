<?php
// partials/header.php
if (session_status() === PHP_SESSION_NONE) session_start();

$title = $title ?? "SIRAMBO";
$page  = $page  ?? ""; // dipakai untuk menu active
?>
<!doctype html>
<html lang="id">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title><?= htmlspecialchars($title) ?> - SIRAMBO</title>

  <!-- Bootstrap 5 via CDN (ringan & stabil) -->
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">

  <!-- Bootstrap Icons (lebih ringan dari FontAwesome) -->
  <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">

  <!-- CSS custom -->
  <link href="/sirambo/assets/css/app.css" rel="stylesheet">
</head>
<body class="bg-light">
