<?php
// Carga los recitales y sus relaciones para el catálogo administrativo.
require_once __DIR__ . '/../../../config/bootstrap.php';
requireAuth();
requireAdmin($pdo);

$flash = consumeFlash();

try {
    // Ordena los eventos por fecha e incluye artista y lugar en una sola consulta.
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
    error_log('Error al consultar los recitales: ' . $e->getMessage());
    http_response_code(500);
    exit('No se pudieron cargar los recitales.');
}

// Renderiza la tabla con estados y acciones disponibles para cada recital.
require_once __DIR__ . '/../../../views/admin/concerts/index.php';