<?php
require_once __DIR__ . '/../../../config/bootstrap.php';
requireAdmin($pdo);
$flash = $_SESSION['flash'] ?? null;
unset($_SESSION['flash']);
$estadosValidos = ['pending', 'paid', 'cancelled'];
$filtro = $_GET['status'] ?? '';
if (!in_array($filtro, $estadosValidos, true)) {
    $filtro = '';
}

try {
    // Ventas (las 200 más recientes)
    $sql = "SELECT s.id, s.sale_date, s.total, s.status, s.payment_method,
                   u.usuario, u.email,
                   GROUP_CONCAT(DISTINCT c.name SEPARATOR ', ') AS recitales,
                   COALESCE(SUM(d.quantity), 0) AS cantidad
              FROM sales s
              JOIN users u ON u.id = s.user_id
              LEFT JOIN sale_details d ON d.sale_id = s.id
              LEFT JOIN tickets t ON t.id = d.ticket_id
              LEFT JOIN concerts c ON c.id = t.concert_id";
    $params = [];
    if ($filtro !== '') {
        $sql .= " WHERE s.status = :status";
        $params['status'] = $filtro;
    }
    $sql .= " GROUP BY s.id, s.sale_date, s.total, s.status, s.payment_method, u.usuario, u.email
              ORDER BY s.sale_date DESC
              LIMIT 200";
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $sales = $stmt->fetchAll(PDO::FETCH_ASSOC);

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
    exit('Error al consultar las ventas');
}

require_once __DIR__ . '/../../../views/admin/sales/index.php';