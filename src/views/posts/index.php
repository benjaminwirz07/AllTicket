<?php
require_once __DIR__ . '/../../config/bootstrap.php';
requireAuth();
requireAdmin($pdo);
ob_start();
?>

<main class="container-xl py-5 text-light">
  <header class="admin-page-header mb-4">
    <div>
      <p class="admin-page-kicker mb-1">CONTENIDO</p>
      <h1 class="h3 fw-bold text-white mb-1">Posts</h1>
      <p class="admin-page-description mb-0">Gestioná las publicaciones del sitio.</p>
    </div>
    <?php if (isAdmin()): ?>
      <div class="d-flex flex-wrap align-items-center gap-2">
        <a class="btn btn-sm btn-outline-secondary rounded-pill" href="/src/controllers/admin/index.php">
          <i class="bi bi-arrow-left me-1" aria-hidden="true"></i>Panel
        </a>
        <a class="btn btn-violet-glow rounded-pill px-4" href="/src/controllers/posts/form.php">Crear Post</a>
      </div>
    <?php else: ?>
      <a class="btn btn-sm btn-outline-secondary rounded-pill" href="/src/controllers/admin/index.php">
        <i class="bi bi-arrow-left me-1" aria-hidden="true"></i>Panel
      </a>
    <?php endif; ?>
  </header>

  <?php if (!empty($posts)): ?>
    <?php foreach ($posts as $post): ?>
      <article class="card bg-card-dark text-light border-0 rounded-4 shadow mb-3">
        <div class="card-body p-4">
          <h2 class="h5 card-title">
            <a class="text-white text-decoration-none" href="/src/controllers/posts/show.php?id=<?= e($post['id']) ?>"><?= e($post['title']) ?></a>
          </h2>
          <p class="card-text"><?= e($post['content']) ?></p>
          <small class="text-secondary-custom"><?= e($post['created_at']) ?></small>
          <p class="card-text mt-2"><strong>By:</strong> <?= e($post['user_name']) ?></p>

          <?php if (isAdmin()): ?>
            <a class="btn btn-sm btn-secondary me-2" href="/src/controllers/posts/form.php?id=<?= e($post['id']) ?>">Editar</a>
            <form method="POST" action="/src/controllers/posts/delete.php" class="d-inline"
                  onsubmit="return confirm('¿Estás seguro de que deseas eliminar este post?');">
              <input type="hidden" name="csrf_token" value="<?= e(csrfToken()) ?>">
              <input type="hidden" name="id" value="<?= e($post['id']) ?>">
              <button class="btn btn-sm btn-danger" type="submit">Eliminar</button>
            </form>
          <?php endif; ?>
        </div>
      </article>
    <?php endforeach; ?>
  <?php else: ?>
    <p class="text-secondary-custom">No hay posts todavía.</p>
  <?php endif; ?>
</main>

<?php
$pageContent = ob_get_clean();
include __DIR__ . '/../_layouts/layout.php';