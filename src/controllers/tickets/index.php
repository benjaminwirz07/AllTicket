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
            'SELECT s.id AS sale_id, d.id AS sale_detail_id, d.ticket_id, d.quantity, d.price,
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

        $pdo->beginTransaction();
        $crearEntrada = $pdo->prepare(
            'INSERT IGNORE INTO ticket_entries
                (sale_detail_id, sale_id, ticket_id, entry_number, seat_code)
             VALUES (:detail_id, :sale_id, :ticket_id, :entry_number, :seat_code)'
        );
        foreach ($entradasPorCompra as $saleId => $items) {
            $posicionAsiento = [];
            foreach ($items as $item) {
                $asientos = $butacasPorEntrada[$saleId][$item['ticket_id']] ?? [];
                $claveAsiento = $saleId . ':' . $item['ticket_id'];
                $offsetAsiento = $posicionAsiento[$claveAsiento] ?? 0;
                for ($numero = 1; $numero <= (int) $item['quantity']; $numero++) {
                    $crearEntrada->execute([
                        'detail_id' => $item['sale_detail_id'],
                        'sale_id' => $saleId,
                        'ticket_id' => $item['ticket_id'],
                        'entry_number' => $numero,
                        'seat_code' => $asientos[$offsetAsiento + $numero - 1]['seat_code'] ?? null,
                    ]);
                }
                $posicionAsiento[$claveAsiento] = $offsetAsiento + (int) $item['quantity'];
            }
        }
        $pdo->commit();

        $stmt = $pdo->prepare(
            'SELECT te.id, te.sale_detail_id, te.entry_number, te.seat_code, te.used_at
               FROM ticket_entries te
               JOIN sale_details d ON d.id = te.sale_detail_id
               JOIN sales s ON s.id = d.sale_id
              WHERE s.user_id = :user_id
              ORDER BY s.sale_date DESC, te.entry_number'
        );
        $stmt->execute(['user_id' => $userId]);
        $entradasQr = [];
        foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $qrEntry) {
            $entradasQr[$qrEntry['sale_detail_id']][] = $qrEntry;
        }
    }
} catch (PDOException $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    error_log('Error al consultar las entradas del usuario: ' . $e->getMessage());
    http_response_code(500);
    exit('No se pudieron cargar tus entradas.');
}

require_once __DIR__ . '/../../views/tickets/index.php';
