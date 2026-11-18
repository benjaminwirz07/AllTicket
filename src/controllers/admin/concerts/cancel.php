<?php
// Cancela un recital activo dentro de una transacción para evitar cambios concurrentes inconsistentes.
require_once __DIR__ . '/../../../config/bootstrap.php';
requireAuth();
requireAdmin($pdo);

requireMethod('POST');
requireCsrfToken();

$id = inputUuid($_POST, 'id');
// Exige un UUID antes de iniciar la transacción.
if ($id === '') {
    http_response_code(400);
    exit('Solicitud no válida');
}

try {
    // Bloquea el recital, confirma su estado y evita cancelar un registro inexistente o ya cancelado.
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

    // Confirma el cambio antes de informar que las ventas abonadas no se reembolsaron.
    $pdo->commit();

    $mensaje = 'Recital cancelado.';
    if ($ventasPagadas > 0) {
        $mensaje .= ' Las ventas pagadas permanecen registradas; no se procesaron reembolsos.';
    }
    setFlash('success', $mensaje);
} catch (RuntimeException $e) {
    // Revierte cambios ante reglas de negocio incumplidas y muestra el motivo controlado.
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    setFlash('danger', $e->getMessage());
} catch (PDOException $e) {
    // Revierte errores de base de datos y registra el detalle solo en el servidor.
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    error_log('Error al cancelar el recital: ' . $e->getMessage());
    setFlash('danger', 'No se pudo cancelar el recital.');
}

redirectTo('/src/controllers/admin/concerts/index.php');