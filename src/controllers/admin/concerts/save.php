<?php
require_once __DIR__ . '/../../../config/bootstrap.php';
requireAdmin($pdo);
require_once __DIR__ . '/../../../config/uploads.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    exit('Método no permitido');
}

$id = trim($_POST['id'] ?? '');

$data = [
    'artist_id'   => trim($_POST['artist_id'] ?? ''),
    'venue_id'    => trim($_POST['venue_id'] ?? ''),
    'name'        => trim($_POST['name'] ?? ''),
    'description' => trim($_POST['description'] ?? ''),
    'date'        => trim($_POST['date'] ?? ''),
    'time'        => trim($_POST['time'] ?? ''),
    'genre'       => trim($_POST['genre'] ?? ''),
    'status'      => trim($_POST['status'] ?? 'active'),
];

// Entradas: tickets[0..n][id|type|price|stock]
$tickets = [];
foreach ((array) ($_POST['tickets'] ?? []) as $row) {
    $tickets[] = [
        'id'    => trim($row['id'] ?? ''),
        'type'  => trim($row['type'] ?? ''),
        'price' => trim($row['price'] ?? ''),
        'stock' => trim($row['stock'] ?? ''),
    ];
}
$data['tickets'] = $tickets;

$errors = [];

if ($data['artist_id'] === '') {
    $errors['artist_id'] = 'Elegí un artista.';
}
if ($data['venue_id'] === '') {
    $errors['venue_id'] = 'Elegí un lugar.';
}
if ($data['name'] === '' || strlen($data['name']) > 200) {
    $errors['name'] = 'El nombre es obligatorio (máximo 200 caracteres).';
}

$d = DateTime::createFromFormat('Y-m-d', $data['date']);
if (!$d || $d->format('Y-m-d') !== $data['date']) {
    $errors['date'] = 'La fecha no es válida.';
}

$t = DateTime::createFromFormat('H:i', $data['time']);
if (!$t || $t->format('H:i') !== $data['time']) {
    $errors['time'] = 'La hora no es válida.';
}

if (strlen($data['genre']) > 50) {
    $errors['genre'] = 'Máximo 50 caracteres.';
}
if (!in_array($data['status'], ['active', 'cancelled', 'finished'], true)) {
    $errors['status'] = 'Estado no válido.';
}

// Validación de entradas
$aGuardar    = [];   // filas con datos
$aBorrar     = [];   // filas existentes que se vaciaron
$tiposUsados = [];

foreach ($tickets as $i => $tk) {
    $vacia = $tk['type'] === '' && $tk['price'] === '' && $tk['stock'] === '';

    if ($vacia) {
        if ($tk['id'] !== '') {
            $aBorrar[] = $tk['id'];
        }
        continue;
    }

    if ($tk['type'] === '' || strlen($tk['type']) > 100) {
        $errors['tickets'][$i] = 'Completá el tipo de entrada (máximo 100 caracteres).';
    } elseif (!is_numeric($tk['price']) || (float) $tk['price'] < 0) {
        $errors['tickets'][$i] = 'El precio debe ser un número mayor o igual a 0.';
    } elseif (filter_var($tk['stock'], FILTER_VALIDATE_INT) === false || (int) $tk['stock'] < 0) {
        $errors['tickets'][$i] = 'El stock debe ser un entero mayor o igual a 0.';
    } elseif (in_array(strtolower($tk['type']), $tiposUsados, true)) {
        $errors['tickets'][$i] = 'Ese tipo de entrada está repetido.';
    }

    $tiposUsados[] = strtolower($tk['type']);
    $aGuardar[] = $tk;
}

if (empty($aGuardar)) {
    $errors['general'] = 'Cargá al menos un tipo de entrada.';
}

// Imagen actual (si estamos editando)
$imagenActual = null;
if ($id !== '') {
    $stmt = $pdo->prepare('SELECT image FROM concerts WHERE id = :id');
    $stmt->execute(['id' => $id]);
    $imagenActual = $stmt->fetchColumn() ?: null;
}

// Imagen nueva: solo se guarda si todo lo demás es válido,
// para no dejar archivos sueltos en el disco
$imagenNueva = null;
if (empty($errors)) {
    try {
        $imagenNueva = guardarImagen($_FILES['image_file'] ?? [], 'concerts');
        if ($id === '' && $imagenNueva === null) {
            $errors['image'] = 'Subí una imagen del recital.';
        }
    } catch (RuntimeException $e) {
        $errors['image'] = $e->getMessage();
        $imagenNueva = null;
    }
}

$volver = '/src/controllers/admin/concerts/form.php' . ($id !== '' ? '?id=' . urlencode($id) : '');

if (!empty($errors)) {
    borrarImagenSubida($imagenNueva);
    $_SESSION['errors'] = $errors;
    $_SESSION['old']    = $data + ['id' => $id];
    header('Location: ' . $volver);
    exit;
}

$params = [
    'artist_id'   => $data['artist_id'],
    'venue_id'    => $data['venue_id'],
    'name'        => $data['name'],
    'description' => $data['description'] !== '' ? $data['description'] : null,
    'date'        => $data['date'],
    'time'        => $data['time'] . ':00',
    'image'       => $imagenNueva ?? $imagenActual,
    'genre'       => $data['genre'] !== '' ? $data['genre'] : null,
    'status'      => $data['status'],
];

try {
    $pdo->beginTransaction();

    if ($id === '') {
        $concertId = $pdo->query('SELECT UUID()')->fetchColumn();

        $stmt = $pdo->prepare(
            'INSERT INTO concerts (id, artist_id, venue_id, name, description, date, time, image, genre, status)
             VALUES (:id, :artist_id, :venue_id, :name, :description, :date, :time, :image, :genre, :status)'
        );
        $stmt->execute($params + ['id' => $concertId]);
        $msg = 'Recital creado correctamente.';
    } else {
        $concertId = $id;

        $stmt = $pdo->prepare(
            'UPDATE concerts
                SET artist_id = :artist_id, venue_id = :venue_id, name = :name,
                    description = :description, date = :date, time = :time,
                    image = :image, genre = :genre, status = :status
              WHERE id = :id'
        );
        $stmt->execute($params + ['id' => $concertId]);
        $msg = 'Recital actualizado correctamente.';
    }

    // 1) Borrar entradas vaciadas (solo si nunca se vendieron ni reservaron)
    foreach ($aBorrar as $ticketId) {
        $stmt = $pdo->prepare(
            'SELECT (SELECT COUNT(*) FROM sale_details WHERE ticket_id = :a)
                  + (SELECT COUNT(*) FROM seat_reservations WHERE ticket_id = :b)'
        );
        $stmt->execute(['a' => $ticketId, 'b' => $ticketId]);

        if ((int) $stmt->fetchColumn() > 0) {
            throw new RuntimeException('No se puede quitar una entrada que ya tiene ventas o reservas. Dejala con stock igual a lo vendido.');
        }

        $stmt = $pdo->prepare('DELETE FROM tickets WHERE id = :id AND concert_id = :c');
        $stmt->execute(['id' => $ticketId, 'c' => $concertId]);
    }

    // 2) Crear o actualizar entradas
    foreach ($aGuardar as $tk) {
        $stock = (int) $tk['stock'];

        if ($tk['id'] === '') {
            $stmt = $pdo->prepare(
                'INSERT INTO tickets (concert_id, type, price, stock, available)
                 VALUES (:c, :type, :price, :stock, :available)'
            );
            $stmt->execute([
                'c'         => $concertId,
                'type'      => $tk['type'],
                'price'     => $tk['price'],
                'stock'     => $stock,
                'available' => $stock,
            ]);
            continue;
        }

        $stmt = $pdo->prepare('SELECT stock, available FROM tickets WHERE id = :id AND concert_id = :c');
        $stmt->execute(['id' => $tk['id'], 'c' => $concertId]);
        $actual = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$actual) {
            throw new RuntimeException('Una de las entradas no pertenece a este recital.');
        }

        // Vendidas o reservadas = stock - available. El nuevo stock no puede ser menor.
        $vendidas = (int) $actual['stock'] - (int) $actual['available'];
        if ($stock < $vendidas) {
            throw new RuntimeException('El stock de "' . $tk['type'] . '" no puede ser menor a las entradas ya vendidas o reservadas (' . $vendidas . ').');
        }

        $stmt = $pdo->prepare(
            'UPDATE tickets SET type = :type, price = :price, stock = :stock, available = :available
              WHERE id = :id AND concert_id = :c'
        );
        $stmt->execute([
            'type'      => $tk['type'],
            'price'     => $tk['price'],
            'stock'     => $stock,
            'available' => $stock - $vendidas,
            'id'        => $tk['id'],
            'c'         => $concertId,
        ]);
    }

    $pdo->commit();

    // Recién ahora, con todo guardado, se borra la imagen anterior
    if ($imagenNueva !== null && $imagenActual !== null) {
        borrarImagenSubida($imagenActual);
    }

    $_SESSION['flash'] = ['type' => 'success', 'msg' => $msg];
} catch (RuntimeException $e) {
    borrarImagenSubida($imagenNueva);
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    $_SESSION['errors'] = ['general' => $e->getMessage()];
    $_SESSION['old']    = $data + ['id' => $id];
    header('Location: ' . $volver);
    exit;
} catch (PDOException $e) {
    borrarImagenSubida($imagenNueva);
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    $_SESSION['flash'] = ['type' => 'danger', 'msg' => 'No se pudo guardar el recital.'];
}

header('Location: /src/controllers/admin/concerts/index.php');
exit;