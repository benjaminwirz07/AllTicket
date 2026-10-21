<?php
require_once __DIR__ . '/../../config/bootstrap.php';
requireAdmin($pdo);

$stats = [
    'recitales_activos' => 0,
    'ventas_pagadas'    => 0,
    'ventas_pendientes' => 0,
    'recaudado'         => 0,
];

try {
    $stats = $pdo->query(
        "SELECT
            (SELECT COUNT(*) FROM concerts WHERE status = 'active' AND date >= CURDATE()) AS recitales_activos,
            (SELECT COUNT(*) FROM sales WHERE status = 'paid')    AS ventas_pagadas,
            (SELECT COUNT(*) FROM sales WHERE status = 'pending') AS ventas_pendientes,
            (SELECT COALESCE(SUM(total), 0) FROM sales WHERE status = 'paid') AS recaudado"
    )->fetch(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    // Si falla, el panel se muestra igual con los números en 0
}

require_once __DIR__ . '/../../views/admin/index.php';