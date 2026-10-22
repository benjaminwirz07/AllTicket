<?php
require_once __DIR__ . '/../../../config/bootstrap.php';
requireAuth();
requireAdmin($pdo);
$flash = consumeFlash();
$id = inputUuid($_GET, 'id');
if ($id === '') {
    redirectTo('/src/controllers/admin/sales/index.php');
}

try {
    $stmt = $pdo->prepare(
        "SELECT s.id, s.sale_date, s.total, s.status, s.payment_method,
                u.usuario, u.first_name, u.last_name, u.email, u.dni, u.phone
           FROM sales s
           JOIN users u ON u.id = s.user_id
          WHERE s.id = :id LIMIT 1"
    );
    $stmt->execute(['id' => $id]);
    $sale = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$sale) {
        http_response_code(404);
        exit('Venta no encontrada');
    }

    $stmt = $pdo->prepare(
        "SELECT d.ticket_id, d.quantity, d.price,
                t.type, c.id AS concert_id, c.name AS concert, c.date, c.time,
                v.name AS venue
           FROM sale_details d
           JOIN tickets t ON t.id = d.ticket_id
           JOIN concerts c ON c.id = t.concert_id
           JOIN venues v ON v.id = c.venue_id
          WHERE d.sale_id = :id"
    );
    $stmt->execute(['id' => $id]);
    $details = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $stmt = $pdo->prepare(
        "SELECT ticket_id, seat_code, status
           FROM seat_reservations
          WHERE sale_id = :id
          ORDER BY seat_code"
    );
    $stmt->execute(['id' => $id]);
    $seatsByTicket = [];
    foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $r) {
        $seatsByTicket[$r['ticket_id']][] = $r;
    }
} catch (PDOException $e) {
    error_log('Error al consultar la venta: ' . $e->getMessage());
    http_response_code(500);
    exit('No se pudo cargar la venta.');
}

require_once __DIR__ . '/../../../views/admin/sales/show.php';