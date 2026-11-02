<?php
require_once __DIR__ . '/../../config/bootstrap.php';
requireAuth();
requireAdmin($pdo);

$post = $post ?? null;
ob_start();
?>

<main class="container-xl py-5 text-light">
  <?php $flash = consumeFlash(); ?>
  <?php if ($flash): ?>
    <div class="alert alert-<?= e(in_array($flash['type'], ['success', 'danger', 'warning', 'info'], true) ? $flash['type'] : 'info') ?>" role="alert">
      <?= e($flash['msg']) ?>
    </div>
  <?php endif; ?>
  <header class="mb-4">
    <h1 class="h3 fw-bold text-white"><?= $post ? 'Editar Post' : 'Crear Post' ?></h1>
    <p class="text-secondary-custom">Completá el formulario</p>
  </header>

  <form method="POST" enctype="multipart/form-data"
        action="<?= e(url($post ? '/src/controllers/posts/update.php' : '/src/controllers/posts/store.php')) ?>">
    <input type="hidden" name="csrf_token" value="<?= e(csrfToken()) ?>">
    <?php if ($post): ?>
      <input type="hidden" name="id" value="<?= e($post['id']) ?>">
    <?php endif; ?>

    <div class="mb-3">
      <label class="form-label" for="title">Título</label>
      <input class="form-control" id="title" type="text" name="title" required
             value="<?= e($post['title'] ?? '') ?>" placeholder="Título">
    </div>

    <div class="mb-3">
      <label class="form-label" for="category">Categoría</label>
      <select class="form-select" id="category" name="category" required>
        <?php foreach (NEWS_CATEGORIES as $category): ?>
          <option value="<?= e($category) ?>" <?= ($post['category'] ?? 'AllTicket') === $category ? 'selected' : '' ?>>
            <?= e($category) ?>
          </option>
        <?php endforeach; ?>
      </select>
    </div>

    <div class="mb-3">
      <label class="form-label" for="summary">Resumen (máximo 500 caracteres)</label>
      <textarea class="form-control" id="summary" name="summary" rows="3" maxlength="500"
                placeholder="Si lo dejás vacío, se usará el comienzo del contenido."><?= e($post['summary'] ?? '') ?></textarea>
    </div>

    <div class="mb-3">
      <label class="form-label" for="content">Contenido</label>
      <textarea class="form-control" id="content" name="content" rows="6" required
                placeholder="Contenido"><?= e($post['content'] ?? '') ?></textarea>
    </div>

    <div class="form-check form-switch mb-4">
      <input class="form-check-input" type="checkbox" role="switch" id="featured" name="featured" value="1"
             <?= !empty($post['featured']) ? 'checked' : '' ?>>
      <label class="form-check-label" for="featured">Marcar como nota destacada</label>
    </div>

    <div class="mb-4">
      <label class="form-label" for="image_file">Imagen de la noticia (JPG, PNG o WebP, máximo 2 MB)</label>
      <?php if (!empty($post['image'])): ?>
        <div class="mb-2">
          <img src="<?= e(url('/' . ltrim($post['image'], '/'))) ?>" alt="Imagen actual de la noticia"
               style="max-width:100%;max-height:180px;border-radius:12px;">
        </div>
        <p class="small text-secondary-custom">Subí otra imagen solo si querés reemplazarla.</p>
      <?php endif; ?>
      <input class="form-control" type="file" id="image_file" name="image_file"
             accept="image/jpeg,image/png,image/webp">
    </div>

    <button class="btn btn-violet-glow rounded-pill px-4" type="submit">Guardar</button>
    <a class="btn btn-outline-secondary rounded-pill px-4 ms-2" href="<?= e(url('/src/controllers/posts/index.php')) ?>">Cancelar</a>
  </form>
</main>

<?php
$pageContent = ob_get_clean();
include __DIR__ . '/../_layouts/layout.php';