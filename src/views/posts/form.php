<?php
require_once __DIR__ . '/../../config/bootstrap.php';
requireAuth();
requireAdmin($pdo);

$post = $post ?? null;
ob_start();
?>

<main class="container-xl py-5 text-light">
  <header class="mb-4">
    <h1 class="h3 fw-bold text-white"><?= $post ? 'Editar Post' : 'Crear Post' ?></h1>
    <p class="text-secondary-custom">Completá el formulario</p>
  </header>

  <form method="POST" action="<?= $post ? '/src/controllers/posts/update.php' : '/src/controllers/posts/store.php' ?>">
    <input type="hidden" name="csrf_token" value="<?= e(csrfToken()) ?>">
    <?php if ($post): ?>
      <input type="hidden" name="id" value="<?= e($post['id']) ?>">
    <?php endif; ?>

    <div class="mb-3">
      <input class="form-control" type="text" name="title" required
             value="<?= e($post['title'] ?? '') ?>" placeholder="Título">
    </div>

    <div class="mb-3">
      <textarea class="form-control" name="content" rows="6" required
                placeholder="Contenido"><?= e($post['content'] ?? '') ?></textarea>
    </div>

    <button class="btn btn-violet-glow rounded-pill px-4" type="submit">Guardar</button>
    <a class="btn btn-outline-secondary rounded-pill px-4 ms-2" href="/src/controllers/posts/index.php">Cancelar</a>
  </form>
</main>

<?php
$pageContent = ob_get_clean();
include __DIR__ . '/../_layouts/layout.php';