<?php
require_once __DIR__ . '/../../../config/bootstrap.php';
requireAdmin($pdo);

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    exit('Método no permitido');
}

$id     = trim($_POST['id'] ?? '');
$accion = $_POST['action'] ?? '';

if ($id === '' || !in_array($accion, ['confirm', 'cancel'], true)) {
    http_response_code(400);
    exit('Solicitud no válida');
}

$volver = '/src/controllers/admin/sales/show.php?id=' . urlencode($id);

try {
    $pdo->beginTransaction();

    // Bloquea la venta mientras se procesa
    $stmt = $pdo->prepare('SELECT status FROM sales WHERE id = :id FOR UPDATE');
    $stmt->execute(['id' => $id]);
    $estado = $stmt->fetchColumn();

    if ($estado === false) {
        throw new RuntimeException('Venta no encontrada.');
    }

    if ($accion === 'confirm') {
        if ($estado !== 'pending') {
            throw new RuntimeException('Solo se puede confirmar una venta pendiente.');
        }

        $pdo->prepare("UPDATE sales SET status = 'paid' WHERE id = :id")
            ->execute(['id' => $id]);

        // Las butacas reservadas pasan a confirmadas y dejan de vencer
        $pdo->prepare(
            "UPDATE seat_reservations
                SET status = 'confirmed', expires_at = NULL
              WHERE sale_id = :id AND status = 'held'"
        )->execute(['id' => $id]);

        $msg = 'Pago confirmado. La venta quedó como pagada.';
    } else {
        if (!in_array($estado, ['pending', 'paid'], true)) {
            throw new RuntimeException('Esta venta ya está cancelada.');
        }

        $pdo->prepare("UPDATE sales SET status = 'cancelled' WHERE id = :id")
            ->execute(['id' => $id]);

        // Devuelve el stock a las entradas
        $pdo->prepare(
            "UPDATE tickets t
               JOIN sale_details d ON d.ticket_id = t.id
                SET t.available = LEAST(t.stock, t.available + d.quantity)
              WHERE d.sale_id = :id"
        )->execute(['id' => $id]);

        // Libera las butacas
        $pdo->prepare(
            "UPDATE seat_reservations
                SET status = 'released', expires_at = NULL
              WHERE sale_id = :id AND status IN ('held', 'confirmed')"
        )->execute(['id' => $id]);

        $msg = 'Venta cancelada. Se devolvió el stock y se liberaron las butacas.';
    }

    $pdo->commit();
    $_SESSION['flash'] = ['type' => 'success', 'msg' => $msg];
} catch (RuntimeException $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    $_SESSION['flash'] = ['type' => 'danger', 'msg' => $e->getMessage()];
} catch (PDOException $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    error_log('Error al actualizar venta: ' . $e->getMessage());
    $_SESSION['flash'] = ['type' => 'danger', 'msg' => 'No se pudo actualizar la venta.'];
}

header('Location: ' . $volver);
exit;