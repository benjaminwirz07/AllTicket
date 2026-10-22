<?php
require_once __DIR__ . '/../../../config/bootstrap.php';
requireAuth();
requireAdmin($pdo);

$flash = consumeFlash();

try {
    $stmt = $pdo->query('SELECT * FROM venues ORDER BY name ASC');
    $venues = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    error_log('Error al consultar los lugares: ' . $e->getMessage());
    http_response_code(500);
    exit('No se pudieron cargar los lugares.');
}

require_once __DIR__ . '/../../../views/admin/venues/index.php';