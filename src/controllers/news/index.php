<?php
require_once __DIR__ . '/../../config/bootstrap.php';
require_once __DIR__ . '/../../config/news.php';

try {
    $totalNoticias = (int) $pdo->query('SELECT COUNT(*) FROM posts')->fetchColumn();
    $estadosNewsletter = ['ok', 'invalid', 'error', 'rate_limited'];
    $estadoNewsletter = inputString($_GET, 'newsletter');
    if (!in_array($estadoNewsletter, $estadosNewsletter, true)) {
        $estadoNewsletter = '';
    }

    require_once __DIR__ . '/../../views/news/index.php';
} catch (PDOException $e) {
    error_log('Error al consultar las noticias: ' . $e->getMessage());
    http_response_code(500);
    exit('No se pudieron cargar las noticias.');
}
