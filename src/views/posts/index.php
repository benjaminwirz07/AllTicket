<?php
require_once __DIR__ . '/../../config/bootstrap.php';
requireAuth();
requireAdmin($pdo);
ob_start();
?>

<main class="container-xl py-5 text-light">
  <header class="row mb-4">
    <div class="col">
      <h1 class="h3 fw-bold text-white">Listado de posts</h1>
      <p class="text-secondary-custom">Aquí puedes ver todos los posts publicados</p>
    </div>
    <?php if (isAdmin()): ?>
      <div class="col d-flex justify-content-end align-items-center">
        <a class="btn btn-violet-glow rounded-pill px-4" href="/src/controllers/posts/form.php">Crear Post</a>
      </div>
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