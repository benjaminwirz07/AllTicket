<?php ob_start(); ?>

<main class="container-xl py-5 text-light">
  <header class="row mb-4">
    <div class="col">
      <h1 class="h3 fw-bold text-white">Noticias</h1>
    </div>
    <div class="col d-flex justify-content-end align-items-center">
      <a class="btn btn-outline-violet rounded-pill px-4" href="<?= e(url('/src/controllers/news/index.php')) ?>">Volver a noticias</a>
    </div>
  </header>

  <?php if (!empty($post)): ?>
    <article class="card bg-card-dark text-light border-0 rounded-4 shadow">
      <div class="card-body p-4">
        <?php if (!empty($post['image'])): ?>
          <img class="img-fluid rounded-4 mb-4"
               src="<?= e(url('/' . ltrim($post['image'], '/'))) ?>"
               alt="Imagen de la noticia: <?= e($post['title']) ?>">
        <?php endif; ?>
        <div class="row mb-3">
          <div class="col">
            <p class="small text-uppercase text-violet-neon mb-2"><?= e($post['category'] ?? 'AllTicket') ?></p>
            <h2 class="h4 card-title"><?= e($post['title']) ?></h2>
            <p class="text-secondary-custom mb-0">
              <?= e($post['user_name']) ?> · <?= max(1, (int) ($post['reading_minutes'] ?? 1)) ?> min de lectura
            </p>
          </div>
          <?php if (isAdmin()): ?>
            <div class="col d-flex justify-content-end align-items-center">
              <a class="btn btn-sm btn-secondary me-2" href="<?= e(url('/src/controllers/posts/form.php?id=' . rawurlencode((string) $post['id']))) ?>">Editar</a>
              <form method="POST" action="<?= e(url('/src/controllers/posts/delete.php')) ?>" class="d-inline"
                    onsubmit="return confirm('¿Estás seguro de que deseas eliminar este post?');">
                <input type="hidden" name="csrf_token" value="<?= e(csrfToken()) ?>">
                <input type="hidden" name="id" value="<?= e($post['id']) ?>">
                <button class="btn btn-sm btn-danger" type="submit">Eliminar</button>
              </form>
            </div>
          <?php endif; ?>
        </div>

        <p class="card-text"><?= nl2br(e($post['content'])) ?></p>
        <p class="text-secondary-custom mb-0">Publicado el <?= e($post['created_at']) ?></p>
      </div>
    </article>
    <?php if (!empty($relatedPosts)): ?>
      <section class="mt-5" aria-labelledby="related-news-title">
        <h2 class="h4 text-white mb-3" id="related-news-title">Notas relacionadas</h2>
        <div class="row g-3">
          <?php foreach ($relatedPosts as $related): ?>
            <div class="col-12 col-md-4">
              <a class="d-block h-100 p-3 rounded-4 border border-secondary-subtle bg-card-dark"
                 href="<?= e(url('/src/controllers/posts/show.php?slug=' . rawurlencode((string) $related['slug']))) ?>">
                <span class="small text-violet-neon text-uppercase"><?= e($related['category']) ?></span>
                <h3 class="h6 text-white mt-2"><?= e($related['title']) ?></h3>
                <span class="small text-secondary-custom"><?= max(1, (int) $related['reading_minutes']) ?> min de lectura</span>
              </a>
            </div>
          <?php endforeach; ?>
        </div>
      </section>
    <?php endif; ?>
  <?php else: ?>
    <p class="text-secondary-custom">No se encontró el post solicitado.</p>
  <?php endif; ?>
</main>

<?php
$pageContent = ob_get_clean();
include __DIR__ . '/../_layouts/layout.php';