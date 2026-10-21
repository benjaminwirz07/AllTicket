<?php
require_once __DIR__ . '/../../../config/bootstrap.php';
requireAdmin($pdo);

$flash = $_SESSION['flash'] ?? null;
unset($_SESSION['flash']);

try {
    $stmt = $pdo->query('SELECT * FROM artists ORDER BY name ASC');
    $artists = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    exit('Error al consultar los artistas');
}

require_once __DIR__ . '/../../../views/admin/artists/index.php';