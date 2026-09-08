<?php
require_once __DIR__ . '/../../config/bootstrap.php'; # Acá linkea las configuraciones de bootstrap.php

# Si no estoy logueado, me saca
if (!isset($_SESSION['user'])) {
  header('Location: /src/views/auth/login.php');
  exit;
}

function logout() {
  session_destroy();
  header('Location: /src/views/auth/login.php');
  exit;
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600&family=Sora:wght@600;700&display=swap" rel="stylesheet">
  <link rel="stylesheet" href=<?= '/assets/css/bootstrap.min.css' ?> >
  
  <script src=<?= '/assets/js/bootstrap.min.js' ?>></script>
  <title>AllTicket</title>
</head>
<body>
  <nav class="navbar app-navbar">
    <div class="container-fluid">
      <a class="navbar-brand" href="/src/views/index.php">
      
      </a>
      <a href="/src/controllers/auth/logout.php" class="btn btn-outline-danger btn-sm">Cerrar sesión</a>
    </div>
  </nav>

  <main class="container mt-4 mb-5">
    <!-- Acá se cargan los sitios, pueden modificar lo que gusten -->