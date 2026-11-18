<?php
// Valida y guarda un recital junto con sus tipos de entrada como una operación coherente.
require_once __DIR__ . '/../../../config/bootstrap.php';
requireAuth();
requireAdmin($pdo);
require_once __DIR__ . '/../../../config/uploads.php';

requireMethod('POST');
requireCsrfToken();

$id = inputString($_POST, 'id');
// Solo se acepta un identificador vacío para altas o un UUID válido para ediciones.
if ($id !== '' && !isValidUuid($id)) {
    http_response_code(400);
    exit('Solicitud no válida');
}

$data = [
    'artist_id'   => inputUuid($_POST, 'artist_id'),
    'venue_id'    => inputUuid($_POST, 'venue_id'),
    'name'        => inputString($_POST, 'name'),
    'description' => inputString($_POST, 'description'),
    'date'        => inputDate($_POST, 'date'),
    'time'        => inputString($_POST, 'time'),
    'genre'       => inputString($_POST, 'genre'),
    'status'      => inputString($_POST, 'status') ?: 'active',
];

// Limita la estructura recibida y normaliza cada fila de entradas antes de validarla.
$ticketsInput = $_POST['tickets'] ?? [];
if (!is_array($ticketsInput) || count($ticketsInput) > 50) {
    http_response_code(400);
    exit('Solicitud no válida');
}
$tickets = [];
foreach ($ticketsInput as $row) {
    if (!is_array($row)) {
        http_response_code(400);
        exit('Solicitud no válida');
    }
    $tickets[] = [
        'id'    => inputString($row, 'id'),
        'type'  => inputString($row, 'type'),
        'price' => inputString($row, 'price'),
        'stock' => inputString($row, 'stock'),
    ];
}
$data['tickets'] = $tickets;

// Valida los campos principales del recital y su estado permitido.
$errors = [];

if ($data['artist_id'] === '') {
    $errors['artist_id'] = 'Elegí un artista.';
}
if ($data['venue_id'] === '') {
    $errors['venue_id'] = 'Elegí un lugar.';
}
if ($data['name'] === '' || mb_strlen($data['name'], 'UTF-8') > 200) {
    $errors['name'] = 'El nombre es obligatorio (máximo 200 caracteres).';
}
if (mb_strlen($data['description'], 'UTF-8') > 20000) {
    $errors['description'] = 'La descripción no puede superar los 20.000 caracteres.';
}

$d = DateTime::createFromFormat('Y-m-d', $data['date']);
if (!$d || $d->format('Y-m-d') !== $data['date']) {
    $errors['date'] = 'La fecha no es válida.';
}

$t = DateTime::createFromFormat('H:i', $data['time']);
if (!$t || $t->format('H:i') !== $data['time']) {
    $errors['time'] = 'La hora no es válida.';
}

if (mb_strlen($data['genre'], 'UTF-8') > 50) {
    $errors['genre'] = 'Máximo 50 caracteres.';
}
if (!in_array($data['status'], ['active', 'cancelled', 'finished'], true)) {
    $errors['status'] = 'Estado no válido.';
}

// Separa las entradas a guardar de las filas vacías que se pueden eliminar.
$aGuardar    = [];   // filas con datos
$aBorrar     = [];   // filas existentes que se vaciaron
$tiposUsados = [];

foreach ($tickets as $i => $tk) {
    if ($tk['id'] !== '' && !isValidUuid($tk['id'])) {
        $errors['tickets'][$i] = 'La entrada seleccionada no es válida.';
        continue;
    }
    $vacia = $tk['type'] === '' && $tk['price'] === '' && $tk['stock'] === '';

    if ($vacia) {
        if ($tk['id'] !== '') {
            $aBorrar[] = $tk['id'];
        }
        continue;
    }

    if ($tk['type'] === '' || mb_strlen($tk['type'], 'UTF-8') > 100) {
        $errors['tickets'][$i] = 'Completá el tipo de entrada (máximo 100 caracteres).';
    } elseif (
        preg_match('/^\d{1,8}(?:\.\d{1,2})?$/', $tk['price']) !== 1
        || (float) $tk['price'] < 0
    ) {
        $errors['tickets'][$i] = 'El precio debe ser un número mayor o igual a 0.';
    } elseif (
        filter_var($tk['stock'], FILTER_VALIDATE_INT) === false
        || (int) $tk['stock'] < 0
        || (int) $tk['stock'] > 2147483647
    ) {
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

// Recupera imagen y estado persistidos para preservar la imagen y respetar cancelaciones.
$imagenActual = null;
$estadoActual = null;
if ($id !== '') {
    $stmt = $pdo->prepare('SELECT image, status FROM concerts WHERE id = :id');
    $stmt->execute(['id' => $id]);
    $concertActual = $stmt->fetch(PDO::FETCH_ASSOC);
    if (!$concertActual) {
        http_response_code(404);
        exit('Recital no encontrado');
    }
    $imagenActual = $concertActual['image'] ?: null;
    $estadoActual = $concertActual['status'];
}

if ($data['status'] === 'cancelled' && $estadoActual !== 'cancelled') {
    $errors['status'] = 'Usá la acción “Cancelar show” para cancelar el recital.';
} elseif ($estadoActual === 'cancelled' && $data['status'] !== 'cancelled') {
    $errors['status'] = 'Un recital cancelado no se puede reactivar desde este formulario.';
}

// Sube la imagen solo después de validar los demás campos para evitar archivos huérfanos.
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
    // Limpia cualquier archivo temporalmente subido y devuelve errores y datos al formulario.
    borrarImagenSubida($imagenNueva);
    $_SESSION['errors'] = $errors;
    $_SESSION['old']    = $data + ['id' => $id];
    redirectTo($volver);
}

$params = [
    // Normaliza campos opcionales y prepara el conjunto común de valores para la consulta.
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
    // Guarda el recital y sus entradas dentro de una transacción para mantener la integridad.
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

    // Elimina solo entradas vaciadas que todavía no tengan ventas ni reservas.
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

    // Crea entradas nuevas o actualiza las existentes sin reducir lo ya vendido o reservado.
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

    // Reemplaza la imagen anterior únicamente después de confirmar todos los cambios.
    if ($imagenNueva !== null && $imagenActual !== null) {
        borrarImagenSubida($imagenActual);
    }

    setFlash('success', $msg);
} catch (RuntimeException $e) {
    // Revierte la transacción y recupera el formulario ante una regla de negocio incumplida.
    borrarImagenSubida($imagenNueva);
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    $_SESSION['errors'] = ['general' => $e->getMessage()];
    $_SESSION['old']    = $data + ['id' => $id];
    redirectTo($volver);
} catch (PDOException $e) {
    // Ante errores SQL, elimina la imagen nueva y registra el detalle en el servidor.
    borrarImagenSubida($imagenNueva);
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    error_log('Error al guardar el recital: ' . $e->getMessage());
    setFlash('danger', 'No se pudo guardar el recital.');
}

redirectTo('/src/controllers/admin/concerts/index.php');