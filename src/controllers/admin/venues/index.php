<?php
require_once __DIR__ . '/../../../config/bootstrap.php';
requireAdmin($pdo);

$flash = $_SESSION['flash'] ?? null;
unset($_SESSION['flash']);

try {
    $stmt = $pdo->query('SELECT * FROM venues ORDER BY name ASC');
    $venues = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    exit('Error al consultar los lugares');
}

require_once __DIR__ . '/../../../views/admin/venues/index.php';