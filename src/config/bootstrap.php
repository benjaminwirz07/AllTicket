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
        'path' => '/',
        'httponly' => true,                                // el JavaScript no puede leer la cookie
        'samesite' => 'Lax',                               // no se envía en peticiones cruzadas peligrosas
        'secure' => !empty($_SERVER['HTTPS']) && strtolower((string) $_SERVER['HTTPS']) !== 'off',
    ]);
    session_start();
}

// Conexión a la base de datos, disponible en todo el proyecto como $pdo
require_once __DIR__ . '/database.php';

// ============================================================
// CONTROL DE ACCESO CENTRALIZADO
// ============================================================

function currentUser(): ?array
{
    if (!isset($_SESSION['user']) || !is_array($_SESSION['user'])) {
        return null;
    }

    $user = $_SESSION['user'];
    $id = $user['id'] ?? null;
    if (!is_scalar($id) || (string) $id === '') {
        return null;
    }

    $_SESSION['user'] = [
        'id' => (string) $id,
        'role' => is_string($user['role'] ?? null) ? $user['role'] : '',
    ];

    return $_SESSION['user'];
}

function currentUserId(): ?string
{
    $id = currentUser()['id'] ?? null;
    return is_scalar($id) ? (string) $id : null;
}

function currentUserRole(): string
{
    $role = currentUser()['role'] ?? '';
    return is_string($role) ? $role : '';
}

function isAuthenticated(): bool
{
    return currentUser() !== null;
}

function url(string $path): string
{
    if (preg_match('/[\r\n]/', $path) === 1) {
        throw new InvalidArgumentException('La ruta contiene caracteres no válidos.');
    }

    $normalized = '/' . ltrim($path, '/');
    $scriptName = str_replace('\\', '/', (string) ($_SERVER['SCRIPT_NAME'] ?? ''));
    $srcPosition = strpos($scriptName, '/src/');
    if ($srcPosition !== false) {
        $basePath = rtrim(substr($scriptName, 0, $srcPosition), '/');
    } elseif (substr($scriptName, -10) === '/index.php') {
        $basePath = rtrim(str_replace('\\', '/', dirname($scriptName)), '/');
    } else {
        $basePath = '';
    }

    return $basePath . $normalized;
}

function redirectTo(string $target): void
{
    header('Location: ' . url($target));
    exit;
}

function redirectToLogin(string $target = '/src/views/auth/login.php'): void
{
    redirectTo($target);
}

function requireAuth(): void
{
    if (!isAuthenticated()) {
        redirectToLogin();
    }
}

function requireGuest(): void
{
    if (isAuthenticated()) {
        redirectTo('/src/views/index.php');
    }
}

function setFlash(string $type, string $message): void
{
    $_SESSION['flash'] = [
        'type' => $type,
        'msg' => $message,
    ];
}

function consumeFlash(): ?array
{
    if (!isset($_SESSION['flash']) || !is_array($_SESSION['flash'])) {
        return null;
    }

    $flash = $_SESSION['flash'];
    unset($_SESSION['flash']);

    if (!isset($flash['msg']) && isset($flash['message'])) {
        $flash['msg'] = $flash['message'];
    }

    if (
        !isset($flash['type'], $flash['msg'])
        || !is_string($flash['type'])
        || !is_string($flash['msg'])
    ) {
        return null;
    }

    return $flash;
}

function redirectWithFlash(string $target, string $type, string $message): void
{
    setFlash($type, $message);
    redirectTo($target);
}

function requireMethod(string $method): void
{
    $requestMethod = $_SERVER['REQUEST_METHOD'] ?? 'GET';
    $requiredMethod = strtoupper($method);

    if (!is_string($requestMethod) || strtoupper($requestMethod) !== $requiredMethod) {
        http_response_code(405);
        header('Allow: ' . $requiredMethod);
        exit('Método no permitido');
    }
}

function inputString(array $source, string $key, bool $trim = true): string
{
    $value = $source[$key] ?? '';
    if (!is_string($value)) {
        return '';
    }

    $value = $trim ? trim($value) : $value;
    return $value;
}

function inputEmail(array $source, string $key): string
{
    return strtolower(inputString($source, $key));
}

function inputUuid(array $source, string $key): string
{
    $value = inputString($source, $key);
    return isValidUuid($value) ? $value : '';
}

function inputInt(array $source, string $key): int
{
    $value = $source[$key] ?? '';
    if (is_int($value)) {
        return $value;
    }
    if (is_string($value) && preg_match('/^-?\d+$/', trim($value)) === 1) {
        return (int) trim($value);
    }

    return 0;
}

function inputDate(array $source, string $key): string
{
    $value = inputString($source, $key);
    if (strlen($value) !== 10) {
        return '';
    }
    if (
        preg_match('/^(\d{4})-(\d{2})-(\d{2})$/', $value, $parts) !== 1
        || !checkdate((int) $parts[2], (int) $parts[3], (int) $parts[1])
    ) {
        return '';
    }

    return $value;
}

function requireRole(PDO $pdo, string $requiredRole): void
{
    requireAuth();
    $id = currentUserId();
    static $verifiedRoles = [];
    $cacheKey = $id . ':' . $requiredRole;
    if (isset($verifiedRoles[$cacheKey])) {
        return;
    }

    $stmt = $pdo->prepare('SELECT role FROM users WHERE id = :id LIMIT 1');
    $stmt->execute(['id' => $id]);
    $role = $stmt->fetchColumn();

    if (is_string($role)) {
        $_SESSION['user']['role'] = $role;
    } else {
        unset($_SESSION['user']['role']);
    }

    if ($role === $requiredRole) {
        $verifiedRoles[$cacheKey] = true;
        return;
    }

    http_response_code(403);
    exit('Acceso denegado.');
}

/**
 * Corta la ejecución con 403 si el usuario logueado no es admin.
 * Consulta la base en cada request: si un usuario de baja en el rol, pierde el acceso
 * de inmediato, sin esperar a que cierre sesión.
 */
function requireAdmin(PDO $pdo): void
{
    requireRole($pdo, 'admin');
}
/**
 * Solo para mostrar u ocultar elementos en las vistas.
 * La seguridad real la da requireRole($pdo, 'admin') / requireAdmin($pdo).
 */
function isAdmin(): bool
{
    return currentUserRole() === 'admin';
}

function csrfToken(): string
{
    if (empty($_SESSION['csrf_token']) || !is_string($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }

    return $_SESSION['csrf_token'];
}

function requireCsrfToken(): void
{
    $token = $_POST['csrf_token'] ?? null;
    $sessionToken = $_SESSION['csrf_token'] ?? null;

    if (
        !is_string($token) ||
        !is_string($sessionToken) ||
        $sessionToken === '' ||
        !hash_equals($sessionToken, $token)
    ) {
        http_response_code(403);
        exit('La solicitud expiró o no es válida. Volvé a cargar el formulario.');
    }
}

function isValidUuid(string $value): bool
{
    return preg_match('/^[0-9a-f]{8}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{12}$/i', $value) === 1;
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
        1 => 'Enero',
        2 => 'Febrero',
        3 => 'Marzo',
        4 => 'Abril',
        5 => 'Mayo',
        6 => 'Junio',
        7 => 'Julio',
        8 => 'Agosto',
        9 => 'Septiembre',
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
// Regla única de "quedan pocas": 15% o menos del stock Y no más de 500 lugares
// (así un estadio con 7.000 libres no muestra "últimas entradas").
const STOCK_POCAS_RATIO = 0.15;
const STOCK_POCAS_MAX = 500;

if (!function_exists('estadoStock')) {
    function estadoStock($disponibles, $stock)
    {
        $disponibles = (int) $disponibles;
        $stock = (int) $stock;

        if ($disponibles <= 0) {
            return 'agotado';
        }
        if (
            $stock > 0
            && $disponibles <= STOCK_POCAS_MAX
            && ($disponibles / $stock) <= STOCK_POCAS_RATIO
        ) {
            return 'pocas';
        }
        return 'ok';
    }
}