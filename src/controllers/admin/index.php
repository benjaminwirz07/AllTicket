<?php
// Obtiene métricas generales para el inicio del panel de administración.
require_once __DIR__ . '/../../config/bootstrap.php';
requireAuth();
requireAdmin($pdo);

$stats = [
    'recitales_activos' => 0,
    'ventas_pagadas'    => 0,
    'recaudado'         => 0,
];

try {
    // Calcula actividad futura, cantidad de ventas abonadas y recaudación total.
    $stats = $pdo->query(
        "SELECT
            (SELECT COUNT(*) FROM concerts WHERE status = 'active' AND date >= CURDATE()) AS recitales_activos,
            (SELECT COUNT(*) FROM sales WHERE status = 'paid')    AS ventas_pagadas,
            (SELECT COALESCE(SUM(total), 0) FROM sales WHERE status = 'paid') AS recaudado"
    )->fetch(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    error_log('Error al consultar las estadísticas de administración: ' . $e->getMessage());
    http_response_code(500);
    exit('No se pudieron cargar las estadísticas.');
}

// Entrega las métricas agregadas a la vista del panel.
require_once __DIR__ . '/../../views/admin/index.php';