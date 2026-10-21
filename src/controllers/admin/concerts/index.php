<?php
require_once __DIR__ . '/../../../config/bootstrap.php';
requireAdmin($pdo);

$flash = $_SESSION['flash'] ?? null;
unset($_SESSION['flash']);

try {
    $stmt = $pdo->query(
        'SELECT c.id, c.name, c.date, c.time, c.status, c.genre,
                a.name AS artist, v.name AS venue
           FROM concerts c
           JOIN artists a ON c.artist_id = a.id
           JOIN venues v ON c.venue_id = v.id
          ORDER BY c.date DESC'
    );
    $concerts = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    exit('Error al consultar los recitales');
}

require_once __DIR__ . '/../../../views/admin/concerts/index.php';