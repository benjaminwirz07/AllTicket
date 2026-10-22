<?php
require_once __DIR__ . '/../../../config/bootstrap.php';
requireAuth();
requireAdmin($pdo);

$errors = $_SESSION['errors'] ?? [];
$old    = $_SESSION['old'] ?? [];
unset($_SESSION['errors'], $_SESSION['old']);

$artists = $pdo->query('SELECT id, name FROM artists ORDER BY name')->fetchAll(PDO::FETCH_ASSOC);
$venues  = $pdo->query('SELECT id, name, city FROM venues ORDER BY name')->fetchAll(PDO::FETCH_ASSOC);

$concert = null;

if (isset($_GET['id'])) {
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

// Si hubo errores al guardar, mostramos lo que la persona había escrito
if (!empty($old)) {
    $imagenGuardada = $concert['image'] ?? null;
    $concert = $old;
    $concert['image'] = $imagenGuardada;
}

require_once __DIR__ . '/../../../views/admin/concerts/form.php';