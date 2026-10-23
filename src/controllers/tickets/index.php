<?php
require_once __DIR__ . '/../../config/bootstrap.php';
requireAuth();

$userId = currentUserId();
$compras = [];
$entradasPorCompra = [];
$butacasPorEntrada = [];

try {
    $stmt = $pdo->prepare(
        'SELECT s.id, s.sale_date, s.total, s.status, s.payment_method,
                u.first_name, u.last_name
           FROM sales s
           JOIN users u ON u.id = s.user_id
          WHERE s.user_id = :user_id
          ORDER BY s.sale_date DESC'
    );
    $stmt->execute(['user_id' => $userId]);
    $compras = $stmt->fetchAll(PDO::FETCH_ASSOC);

    if ($compras !== []) {
        $stmt = $pdo->prepare(
            'SELECT s.id AS sale_id, d.ticket_id, d.quantity, d.price,
                    t.type, c.name AS concert, c.image, a.name AS artist, c.date, c.time,
                    v.name AS venue, v.address, v.city, v.province
               FROM sales s
               JOIN sale_details d ON d.sale_id = s.id
               JOIN tickets t ON t.id = d.ticket_id
               JOIN concerts c ON c.id = t.concert_id
               JOIN artists a ON a.id = c.artist_id
               JOIN venues v ON v.id = c.venue_id
              WHERE s.user_id = :user_id
              ORDER BY s.sale_date DESC, c.date ASC, t.type ASC'
        );
        $stmt->execute(['user_id' => $userId]);
        foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $entrada) {
            $entradasPorCompra[$entrada['sale_id']][] = $entrada;
        }

        $stmt = $pdo->prepare(
            'SELECT r.sale_id, r.ticket_id, r.seat_code, r.status
               FROM seat_reservations r
               JOIN sales s ON s.id = r.sale_id
              WHERE s.user_id = :user_id
              ORDER BY r.seat_code'
        );
        $stmt->execute(['user_id' => $userId]);
        foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $butaca) {
            $butacasPorEntrada[$butaca['sale_id']][$butaca['ticket_id']][] = $butaca;
        }
    }
} catch (PDOException $e) {
    error_log('Error al consultar las entradas del usuario: ' . $e->getMessage());
    http_response_code(500);
    exit('No se pudieron cargar tus entradas.');
}

require_once __DIR__ . '/../../views/tickets/index.php';
