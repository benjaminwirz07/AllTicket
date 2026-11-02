<?php
require_once __DIR__ . '/../../config/bootstrap.php';
require_once __DIR__ . '/../../config/news.php';

header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store');

$query = inputString($_GET, 'q');
$query = mb_substr($query, 0, 100, 'UTF-8');
$category = inputString($_GET, 'category');
$page = max(1, min(200, inputInt($_GET, 'page')));
$pageSize = 6;

if ($category !== '' && !in_array($category, NEWS_CATEGORIES, true)) {
    http_response_code(400);
    echo json_encode(['error' => 'Categoría no válida.'], JSON_UNESCAPED_UNICODE);
    exit;
}

try {
    $conditions = [];
    $params = [];
    if ($category !== '') {
        $conditions[] = 'p.category = :category';
        $params['category'] = $category;
    }
    if ($query !== '') {
        $conditions[] = '(p.title LIKE :title_query OR p.summary LIKE :summary_query OR p.content LIKE :content_query)';
        $search = '%' . $query . '%';
        $params['title_query'] = $search;
        $params['summary_query'] = $search;
        $params['content_query'] = $search;
    }
    $where = $conditions ? 'WHERE ' . implode(' AND ', $conditions) : '';

    $featuredWhere = $where !== '' ? $where . ' AND p.featured = 1' : 'WHERE p.featured = 1';
    $stmtFeatured = $pdo->prepare(
        "SELECT p.id, p.title, p.slug, p.content, p.summary, p.category, p.image,
                p.reading_minutes, p.created_at, u.usuario AS author
           FROM posts p
           JOIN users u ON u.id = p.user_id
          $featuredWhere
          ORDER BY p.created_at DESC
          LIMIT 1"
    );
    $stmtFeatured->execute($params);
    $featured = $stmtFeatured->fetch(PDO::FETCH_ASSOC) ?: null;

    $articleConditions = $conditions;
    $articleParams = $params;
    if ($featured !== null) {
        $articleConditions[] = 'p.id <> :featured_id';
        $articleParams['featured_id'] = $featured['id'];
    }
    $articleWhere = $articleConditions ? 'WHERE ' . implode(' AND ', $articleConditions) : '';
    $stmtArticles = $pdo->prepare(
        "SELECT p.id, p.title, p.slug, p.content, p.summary, p.category, p.image,
                p.reading_minutes, p.created_at, u.usuario AS author
           FROM posts p
           JOIN users u ON u.id = p.user_id
          $articleWhere
          ORDER BY p.created_at DESC
          LIMIT :limit OFFSET :offset"
    );
    foreach ($articleParams as $key => $value) {
        $stmtArticles->bindValue(':' . $key, $value);
    }
    $stmtArticles->bindValue(':limit', $pageSize + 1, PDO::PARAM_INT);
    $stmtArticles->bindValue(':offset', ($page - 1) * $pageSize, PDO::PARAM_INT);
    $stmtArticles->execute();
    $articles = $stmtArticles->fetchAll(PDO::FETCH_ASSOC);
    $hasMore = count($articles) > $pageSize;
    if ($hasMore) {
        array_pop($articles);
    }

    $formatPost = static function (array $post): array {
        $post['summary'] = newsSummary((string) ($post['summary'] ?? ''), (string) $post['content'], 500);
        $post['reading_minutes'] = max(1, (int) $post['reading_minutes']);
        $post['author'] = (string) ($post['author'] ?? 'AllTicket');
        $post['url'] = url('/src/controllers/posts/show.php?slug=' . rawurlencode((string) $post['slug']));
        $post['image'] = is_string($post['image']) && $post['image'] !== ''
            ? url('/' . ltrim($post['image'], '/'))
            : null;
        unset($post['content']);
        return $post;
    };

    echo json_encode([
        'featured' => $featured ? $formatPost($featured) : null,
        'articles' => array_map($formatPost, $articles),
        'hasMore' => $hasMore,
        'page' => $page,
    ], JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT);
} catch (PDOException $e) {
    error_log('Error al consultar el listado público de noticias: ' . $e->getMessage());
    http_response_code(500);
    echo json_encode(['error' => 'No se pudieron cargar las noticias.'], JSON_UNESCAPED_UNICODE);
}
