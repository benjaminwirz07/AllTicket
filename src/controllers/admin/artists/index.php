<?php
require_once __DIR__ . '/../../../config/bootstrap.php';
requireAuth();
requireAdmin($pdo);

$flash = consumeFlash();

try {
    $stmt = $pdo->query('SELECT * FROM artists ORDER BY name ASC');
    $artists = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    error_log('Error al consultar los artistas: ' . $e->getMessage());
    http_response_code(500);
    exit('No se pudieron cargar los artistas.');
}

require_once __DIR__ . '/../../../views/admin/artists/index.php';