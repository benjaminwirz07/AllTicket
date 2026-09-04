<?php
require_once __DIR__ . '/../../config/bootstrap.php';

// Paso clave #1: Validar tipo de solicitud ----------------------
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {  // Si la solicitud no es POST, volves al register
  header('Location: /src/views/auth/login.php');
  exit;
}

// Paso clave #2: Tomar datos -----------------------------------
$data = [
  'email'           => trim($_POST['email'] ?? ''), // trim(str) saca los espacios al inicio y al final
  'password'        => $_POST['password'] ?? '',
];
// Paso clave #3: Validar datos
             
//paso clave #4 validar q el usuario exista

//paso clave #5 validar si el usuario existe y la contraseña es correcta

//paso clave #6 logear al usuario 

try {
  // Lógica de validación y autenticación
} catch (Exception $e) {
  $_SESSION['error'] = $e->getMessage();
  header('Location: /src/views/auth/login.php');
  exit;
}
?>