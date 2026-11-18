<?php
// Carga catálogos y datos existentes para el formulario de alta o edición de recitales.
require_once __DIR__ . '/../../../config/bootstrap.php';
requireAuth();
requireAdmin($pdo);

$errors = $_SESSION['errors'] ?? [];
$old    = $_SESSION['old'] ?? [];
unset($_SESSION['errors'], $_SESSION['old']);

// Los selectores necesitan las opciones disponibles de artistas y lugares.
$artists = $pdo->query('SELECT id, name FROM artists ORDER BY name')->fetchAll(PDO::FETCH_ASSOC);
$venues  = $pdo->query('SELECT id, name, city FROM venues ORDER BY name')->fetchAll(PDO::FETCH_ASSOC);

$concert = null;

if (isset($_GET['id'])) {
    // En edición se valida el identificador, se carga el recital y sus tipos de entrada.
    $id = inputUuid($_GET, 'id');
    if ($id === '') {
        http_response_code(400);
        exit('Solicitud no válida');
    }
    $stmt = $pdo->prepare('SELECT * FROM concerts WHERE id = :id LIMIT 1');
    $stmt->execute(['id' => $id]);
    $concert = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$concert) {
        http_response_code(404);
        exit('Recital no encontrado');
    }

    $stmt = $pdo->prepare('SELECT id, type, price, stock, available FROM tickets WHERE concert_id = :id ORDER BY price ASC');
    $stmt->execute(['id' => $concert['id']]);
    $concert['tickets'] = array_map(fn($t) => [
        'id'     => $t['id'],
        'type'   => $t['type'],
        'price'  => $t['price'],
        'stock'  => $t['stock'],
        'sold'   => (int) $t['stock'] - (int) $t['available'],
    ], $stmt->fetchAll(PDO::FETCH_ASSOC));
}

// Tras un error conserva los valores enviados, pero mantiene imagen y estado persistidos.
if (!empty($old)) {
    $imagenGuardada = $concert['image'] ?? null;
    $estadoGuardado = $concert['status'] ?? 'active';
    $concert = $old;
    $concert['image'] = $imagenGuardada;
    $concert['status'] = $estadoGuardado;
}

// Presenta el formulario con catálogos, datos y posibles errores.
require_once __DIR__ . '/../../../views/admin/concerts/form.php';