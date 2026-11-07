<?php
require_once __DIR__ . '/../../../config/bootstrap.php';
requireAuth();
requireAdmin($pdo);
$flash = consumeFlash();
$estadosValidos = ['pending', 'paid', 'cancelled'];
$filtro = $_GET['status'] ?? '';
if (!in_array($filtro, $estadosValidos, true)) {
    $filtro = '';
}

try {
    // Compras agrupadas por recital para desplegarlas dentro de su resumen.
    $sql = "SELECT c.id AS recital_id, s.id, s.sale_date, s.status, s.payment_method,
                   u.usuario, u.email,
                   SUM(d.quantity) AS cantidad,
                   SUM(d.quantity * d.price) AS subtotal
              FROM sales s
              JOIN users u ON u.id = s.user_id
              JOIN sale_details d ON d.sale_id = s.id
              JOIN tickets t ON t.id = d.ticket_id
              JOIN concerts c ON c.id = t.concert_id";
    $params = [];
    if ($filtro !== '') {
        $sql .= " WHERE s.status = :status";
        $params['status'] = $filtro;
    }
    $sql .= " GROUP BY c.id, s.id, s.sale_date, s.status, s.payment_method, u.usuario, u.email
              ORDER BY s.sale_date DESC";
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $comprasPorRecital = [];
    foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $compra) {
        $comprasPorRecital[$compra['recital_id']][] = $compra;
    }

    // Resumen por recital
    $resumen = $pdo->query(
        "SELECT c.id, c.name, c.date, c.time, c.status,
                (SELECT COALESCE(SUM(t.stock), 0)
                   FROM tickets t WHERE t.concert_id = c.id) AS stock,
                (SELECT COALESCE(SUM(d.quantity), 0)
                   FROM sale_details d
                   JOIN sales s ON s.id = d.sale_id
                   JOIN tickets t ON t.id = d.ticket_id
                  WHERE t.concert_id = c.id AND s.status = 'paid') AS vendidas,
                (SELECT COALESCE(SUM(d.quantity), 0)
                   FROM sale_details d
                   JOIN sales s ON s.id = d.sale_id
                   JOIN tickets t ON t.id = d.ticket_id
                  WHERE t.concert_id = c.id AND s.status = 'pending') AS pendientes,
                (SELECT COALESCE(SUM(d.quantity * d.price), 0)
                   FROM sale_details d
                   JOIN sales s ON s.id = d.sale_id
                   JOIN tickets t ON t.id = d.ticket_id
                  WHERE t.concert_id = c.id AND s.status = 'paid') AS recaudado
           FROM concerts c
          ORDER BY c.date DESC"
    )->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    error_log('Error al consultar las ventas: ' . $e->getMessage());
    http_response_code(500);
    exit('No se pudieron cargar las ventas.');
}

require_once __DIR__ . '/../../../views/admin/sales/index.php';