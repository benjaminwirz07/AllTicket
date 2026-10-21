<?php ob_start(); ?>

<main class="container-xl py-5 text-light">
  <header class="row mb-4">
    <div class="col">
      <h1 class="h3 fw-bold text-white">Detalle del post</h1>
    </div>
    <div class="col d-flex justify-content-end align-items-center">
      <a class="btn btn-outline-violet rounded-pill px-4" href="/src/controllers/posts/index.php">Volver al listado</a>
    </div>
  </header>

  <?php if (!empty($post)): ?>
    <article class="card bg-card-dark text-light border-0 rounded-4 shadow">
      <div class="card-body p-4">
        <div class="row mb-3">
          <div class="col">
            <h2 class="h4 card-title"><?= e($post['title']) ?></h2>
          </div>
          <?php if (isAdmin()): ?>
            <div class="col d-flex justify-content-end align-items-center">
              <a class="btn btn-sm btn-secondary me-2" href="/src/controllers/posts/form.php?id=<?= e($post['id']) ?>">Editar</a>
              <form method="POST" action="/src/controllers/posts/delete.php" class="d-inline"
                    onsubmit="return confirm('¿Estás seguro de que deseas eliminar este post?');">
                <input type="hidden" name="id" value="<?= e($post['id']) ?>">
                <button class="btn btn-sm btn-danger" type="submit">Eliminar</button>
              </form>
            </div>
          <?php endif; ?>
        </div>

        <p class="card-text"><?= nl2br(e($post['content'])) ?></p>
        <p class="card-text"><strong>Autor:</strong> <?= e($post['user_name']) ?></p>
        <p class="text-secondary-custom mb-0">Publicado el <?= e($post['created_at']) ?></p>
      </div>
    </article>
  <?php else: ?>
    <p class="text-secondary-custom">No se encontró el post solicitado.</p>
  <?php endif; ?>
</main>

<?php
$pageContent = ob_get_clean();
include __DIR__ . '/../_layouts/layout.php';