<?php
// Carga los parámetros de conexión desde el archivo local de entorno.
$env = @parse_ini_file(__DIR__ . '/../../.env');

// Si falta el .env o le faltan claves, se corta con un mensaje genérico y el detalle va al log
$requeridas = ['DB_HOST', 'DB_NAME', 'DB_USER', 'DB_PASS'];
if ($env === false) {
    error_log('database.php: no se pudo leer el archivo .env');
    http_response_code(500);
    die('No se pudo conectar con el servicio. Intentá nuevamente más tarde.');
}
// Falla de forma controlada cuando falta una clave necesaria para conectarse.
foreach ($requeridas as $clave) {
    if (!array_key_exists($clave, $env)) {
        error_log('database.php: falta la clave ' . $clave . ' en el .env');
        http_response_code(500);
        die('No se pudo conectar con el servicio. Intentá nuevamente más tarde.');
    }
}

$host    = $env['DB_HOST'];
$db      = $env['DB_NAME'];
$user    = $env['DB_USER'];
$pass    = $env['DB_PASS'];
$charset = $env['DB_CHARSET'] ?? 'utf8mb4';

# El DSN define el servidor, esquema y codificación de la conexión PDO.
$dsn = "mysql:host=$host;dbname=$db;charset=$charset";
$options = [
    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    PDO::ATTR_EMULATE_PREPARES   => false,
];

try {
    // Configura PDO y alinea la zona horaria de MySQL con la de la aplicación.
    $pdo = new PDO($dsn, $user, $pass, $options);
    // Misma zona horaria que PHP (Argentina no tiene horario de verano, el offset fijo es seguro)
    $pdo->exec("SET time_zone = '-03:00'");
} catch (\PDOException $e) {
    // El detalle técnico va al log, nunca al visitante
    error_log('Fallo de conexión a la base de datos: ' . $e->getMessage());
    http_response_code(500);
    die('No se pudo conectar con el servicio. Intentá nuevamente más tarde.');
}