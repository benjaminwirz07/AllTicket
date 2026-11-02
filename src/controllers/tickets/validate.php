<?php
require_once __DIR__ . '/../../config/bootstrap.php';
requireAuth();
requireAdmin($pdo);

$entryId = inputUuid($_GET, 'id');
$entrada = null;

if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST') {
    requireMethod('POST');
    requireCsrfToken();
    $entryId = inputUuid($_POST, 'id');
    if ($entryId === '') {
        http_response_code(400);
        exit('Solicitud no válida');
    }

    try {
        $pdo->beginTransaction();
        $stmt = $pdo->prepare(
            "SELECT te.used_at, s.status AS sale_status
               FROM ticket_entries te
               JOIN sales s ON s.id = te.sale_id
              WHERE te.id = :id
              FOR UPDATE"
        );
        $stmt->execute(['id' => $entryId]);
        $estado = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$estado) {
            throw new RuntimeException('No encontramos esta entrada.');
        }
        if ($estado['sale_status'] !== 'paid') {
            throw new RuntimeException(
                $estado['sale_status'] === 'pending'
                    ? 'El pago todavía está pendiente. Esta entrada no es válida para ingresar.'
                    : 'La compra fue cancelada. Esta entrada no es válida.'
            );
        }
        if ($estado['used_at'] !== null) {
            throw new RuntimeException('Esta entrada ya fue utilizada.');
        }

        $stmt = $pdo->prepare(
            'UPDATE ticket_entries SET used_at = CURRENT_TIMESTAMP
              WHERE id = :id AND used_at IS NULL'
        );
        $stmt->execute(['id' => $entryId]);
        if ($stmt->rowCount() !== 1) {
            throw new RuntimeException('Esta entrada ya fue utilizada.');
        }

        $pdo->commit();
        redirectWithFlash(
            '/src/controllers/tickets/validate.php?id=' . rawurlencode($entryId),
            'success',
            'Entrada validada. Puede ingresar al evento.'
        );
    } catch (RuntimeException $e) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }
        setFlash('danger', $e->getMessage());
        redirectTo('/src/controllers/tickets/validate.php?id=' . rawurlencode($entryId));
    } catch (PDOException $e) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }
        error_log('Error al validar la entrada: ' . $e->getMessage());
        setFlash('danger', 'No se pudo validar la entrada. Probá nuevamente.');
        redirectTo('/src/controllers/tickets/validate.php?id=' . rawurlencode($entryId));
    }
}

if (($_SERVER['REQUEST_METHOD'] ?? 'GET') !== 'GET') {
    http_response_code(405);
    header('Allow: GET, POST');
    exit('Método no permitido');
}

if ($entryId !== '') {
    try {
        $stmt = $pdo->prepare(
            "SELECT te.id, te.entry_number, te.seat_code, te.used_at,
                    s.status AS sale_status, s.id AS sale_id,
                    u.first_name, u.last_name,
                    t.type, c.name AS concert, c.date,
                    v.name AS venue, v.city
               FROM ticket_entries te
               JOIN sales s ON s.id = te.sale_id
               JOIN users u ON u.id = s.user_id
               JOIN tickets t ON t.id = te.ticket_id
               JOIN concerts c ON c.id = t.concert_id
               JOIN venues v ON v.id = c.venue_id
              WHERE te.id = :id
              LIMIT 1"
        );
        $stmt->execute(['id' => $entryId]);
        $entrada = $stmt->fetch(PDO::FETCH_ASSOC);
        if (!$entrada) {
            http_response_code(404);
        }
    } catch (PDOException $e) {
        error_log('Error al consultar la entrada para validar: ' . $e->getMessage());
        http_response_code(500);
        exit('No se pudo consultar la entrada.');
    }
}

require_once __DIR__ . '/../../views/tickets/validate.php';
