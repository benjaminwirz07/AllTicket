<?php
require_once __DIR__ . '/../../../config/bootstrap.php';
requireAuth();
requireAdmin($pdo);

requireMethod('POST');
requireCsrfToken();

$id = inputUuid($_POST, 'id');
if ($id === '') {
    http_response_code(400);
    exit('Solicitud no válida');
}

try {
    $pdo->beginTransaction();

    $stmt = $pdo->prepare('SELECT status FROM concerts WHERE id = :id FOR UPDATE');
    $stmt->execute(['id' => $id]);
    $estado = $stmt->fetchColumn();

    if ($estado === false) {
        throw new RuntimeException('No encontramos ese recital.');
    }
    if ($estado !== 'active') {
        throw new RuntimeException('Este recital ya no está activo.');
    }

    vencerReservas($pdo);

    $stmt = $pdo->prepare(
        "SELECT s.id
           FROM sales s
           JOIN sale_details d ON d.sale_id = s.id
           JOIN tickets t ON t.id = d.ticket_id
          WHERE t.concert_id = :concert_id AND s.status = 'pending'
          FOR UPDATE"
    );
    $stmt->execute(['concert_id' => $id]);
    $ventasPendientes = $stmt->fetchAll(PDO::FETCH_COLUMN);

    $cancelarVenta = $pdo->prepare("UPDATE sales SET status = 'cancelled' WHERE id = :id AND status = 'pending'");
    $devolverStock = $pdo->prepare(
        'UPDATE tickets t
           JOIN sale_details d ON d.ticket_id = t.id
            SET t.available = LEAST(t.stock, t.available + d.quantity)
          WHERE d.sale_id = :sale_id AND t.concert_id = :concert_id'
    );
    $liberarButacas = $pdo->prepare(
        "UPDATE seat_reservations
            SET status = 'released', expires_at = NULL
          WHERE sale_id = :sale_id AND status = 'held'"
    );

    $ventasCanceladas = 0;
    foreach ($ventasPendientes as $ventaId) {
        $cancelarVenta->execute(['id' => $ventaId]);
        if ($cancelarVenta->rowCount() === 1) {
            $devolverStock->execute(['sale_id' => $ventaId, 'concert_id' => $id]);
            $liberarButacas->execute(['sale_id' => $ventaId]);
            $ventasCanceladas++;
        }
    }

    $stmt = $pdo->prepare("UPDATE concerts SET status = 'cancelled' WHERE id = :id AND status = 'active'");
    $stmt->execute(['id' => $id]);
    if ($stmt->rowCount() !== 1) {
        throw new RuntimeException('No se pudo cancelar el recital porque su estado cambió.');
    }

    $stmt = $pdo->prepare(
        "SELECT COUNT(DISTINCT s.id)
           FROM sales s
           JOIN sale_details d ON d.sale_id = s.id
           JOIN tickets t ON t.id = d.ticket_id
          WHERE t.concert_id = :concert_id AND s.status = 'paid'"
    );
    $stmt->execute(['concert_id' => $id]);
    $ventasPagadas = (int) $stmt->fetchColumn();

    $pdo->commit();

    $mensaje = 'Recital cancelado. Se cancelaron ' . $ventasCanceladas
        . ' ventas pendientes; se liberaron sus butacas y se devolvió el stock.';
    if ($ventasPagadas > 0) {
        $mensaje .= ' Las ventas pagadas permanecen registradas; no se procesaron reembolsos.';
    }
    setFlash('success', $mensaje);
} catch (RuntimeException $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    setFlash('danger', $e->getMessage());
} catch (PDOException $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    error_log('Error al cancelar el recital: ' . $e->getMessage());
    setFlash('danger', 'No se pudo cancelar el recital.');
}

redirectTo('/src/controllers/admin/concerts/index.php');