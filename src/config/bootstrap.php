<?php
/**
 * Bootstrap global del proyecto.
 *
 * Se incluye al principio de cada "entry point" (archivos a los que
 * se accede directamente por URL: index.php, controllers/*.php).
 * Las vistas que se incluyen con require/include (layout.php, login.php, etc.)
 * NO necesitan llamarlo: ya van a recibir la sesión abierta.
 */

// Zona horaria
date_default_timezone_set('America/Argentina/Buenos_Aires');

// Iniciamos la sesión si todavía no existe una, con la cookie configurada de forma segura
if (session_status() === PHP_SESSION_NONE) {
    ini_set('session.use_strict_mode', '1');   // rechaza ids de sesión que el servidor no generó
    session_set_cookie_params([
        'lifetime' => 0,                                   // se borra al cerrar el navegador
        'path'     => '/',
        'httponly' => true,                                // el JavaScript no puede leer la cookie
        'samesite' => 'Lax',                               // no se envía en peticiones cruzadas peligrosas
        'secure'   => !empty($_SERVER['HTTPS']),           // true automáticamente cuando hay HTTPS
    ]);
    session_start();
}

// Conexión a la base de datos, disponible en todo el proyecto como $pdo
require_once __DIR__ . '/database.php';


// ============================================================
// CONTROL DE ACCESO
// ============================================================

/**
 * Corta la ejecución con 403 si el usuario logueado no es admin.
 * Consulta la base en cada request: si se degrada a un admin, pierde el acceso
 * de inmediato, sin esperar a que cierre sesión.
 * Requiere la columna users.role (ver migración del esquema).
 * Llamarla al inicio de cada página o endpoint de administración.
 */
function requireAdmin(PDO $pdo): void
{
    $id = $_SESSION['user']['id'] ?? null;
    if ($id !== null) {
        $stmt = $pdo->prepare('SELECT role FROM users WHERE id = :id LIMIT 1');
        $stmt->execute(['id' => $id]);
        if ($stmt->fetchColumn() === 'admin') {
            return;
        }
    }
    http_response_code(403);
    exit('Acceso denegado.');
}
/**
 * Solo para mostrar u ocultar elementos en las vistas.
 * La seguridad real la da requireAdmin($pdo) en los controladores.
 */
function isAdmin(): bool
{
    return ($_SESSION['user']['role'] ?? '') === 'admin';
}


// ============================================================
// HELPERS DE FORMATO
// Centralizan el formato de fechas, precios y escapado de HTML
// para que no se repitan en cada vista.
// ============================================================

/**
 * Convierte '2026-11-15' + '21:00:00' en '15 de Noviembre, 21:00 hs'
 */
function formatFecha(string $date, ?string $time = null): string
{
    $timestamp = strtotime($date . ($time ? ' ' . $time : ''));

    if ($timestamp === false) {
        return $date;
    }

    $meses = [
        1  => 'Enero',
        2  => 'Febrero',
        3  => 'Marzo',
        4  => 'Abril',
        5  => 'Mayo',
        6  => 'Junio',
        7  => 'Julio',
        8  => 'Agosto',
        9  => 'Septiembre',
        10 => 'Octubre',
        11 => 'Noviembre',
        12 => 'Diciembre',
    ];

    $texto = date('d', $timestamp) . ' de ' . $meses[(int) date('n', $timestamp)];

    if ($time) {
        $texto .= ', ' . substr($time, 0, 5) . ' hs';
    }

    return $texto;
}

/**
 * Convierte 25000.00 en '$ 25.000'
 */
function formatPrecio(float|string|null $precio): string
{
    if ($precio === null || $precio === '') {
        return 'Precio a definir';
    }

    return '$ ' . number_format((float) $precio, 0, ',', '.');
}

/**
 * Escapa texto para imprimirlo dentro de HTML (previene XSS)
 */
function e(?string $texto): string
{
    return htmlspecialchars((string) $texto, ENT_QUOTES, 'UTF-8');
}
