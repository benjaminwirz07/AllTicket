<?php
$pageStyles = '<link rel="stylesheet" href="' . e(url('/assets/css/news.css')) . '">';
$categoriasNoticias = NEWS_CATEGORIES;
$mensajesNewsletter = [
    'ok' => ['success', 'Tu correo quedó guardado en la lista de la demo. Todavía no enviamos e-mails.'],
    'invalid' => ['danger', 'Ingresá un correo electrónico válido.'],
    'error' => ['danger', 'No pudimos completar la suscripción. Probá de nuevo más tarde.'],
    'rate_limited' => ['warning', 'Alcanzaste el límite de intentos. Probá de nuevo en unos minutos.'],
];
$mensajeNewsletter = $mensajesNewsletter[$estadoNewsletter] ?? null;
ob_start();
?>

<main class="news-page text-light">
  <div class="container-xl news-page__inner">
    <header class="news-heading">
      <span class="news-eyebrow"><i class="bi bi-megaphone-fill" aria-hidden="true"></i> Novedades</span>
      <h1>Noticias<span>.</span></h1>
      <p>Novedades de AllTicket y del mundo de la música.</p>
      <span class="news-heading__line" aria-hidden="true"></span>
    </header>

    <?php if ($totalNoticias > 0): ?>
      <section class="news-browser" data-news-browser
               data-list-url="<?= e(url('/src/controllers/news/list.php')) ?>"
               aria-label="Noticias AllTicket">
        <div class="news-toolbar">
          <div class="news-filters" role="group" aria-label="Filtrar noticias por categoría">
            <button type="button" class="news-chip is-active" data-news-category="" aria-pressed="true">Todas</button>
            <?php foreach ($categoriasNoticias as $categoria): ?>
              <button type="button" class="news-chip" data-news-category="<?= e($categoria) ?>" aria-pressed="false">
                <?= e($categoria) ?>
              </button>
            <?php endforeach; ?>
          </div>
          <form class="news-search" data-news-search role="search">
            <label class="visually-hidden" for="news-search-input">Buscar noticias</label>
            <i class="bi bi-search" aria-hidden="true"></i>
            <input id="news-search-input" type="search" name="q" placeholder="Buscar noticias"
                   autocomplete="off" maxlength="100">
            <button type="submit" aria-label="Buscar noticias"><i class="bi bi-arrow-right" aria-hidden="true"></i></button>
          </form>
        </div>

        <section class="news-featured-section" aria-label="Nota destacada">
          <div class="news-featured" data-news-featured aria-live="polite">
            <div class="news-featured__skeleton news-skeleton"></div>
          </div>
        </section>

        <section class="news-latest" aria-labelledby="latest-news-title">
          <div class="news-section-heading">
            <div>
              <p class="news-section-kicker">Lo que está pasando</p>
              <h2 id="latest-news-title">Últimas noticias</h2>
            </div>
          </div>
          <div class="news-grid" data-news-grid aria-live="polite" aria-busy="true">
            <?php for ($i = 0; $i < 3; $i++): ?>
              <div class="news-card-skeleton news-skeleton" aria-hidden="true">
                <span></span><i></i><b></b><small></small>
              </div>
            <?php endfor; ?>
          </div>
          <p class="news-status" data-news-status role="status" aria-live="polite"></p>
          <div class="news-load-more-wrap">
            <button type="button" class="news-load-more" data-news-load-more hidden>
              Cargar más noticias <i class="bi bi-arrow-down" aria-hidden="true"></i>
            </button>
          </div>
        </section>
      </section>
    <?php else: ?>
      <section class="news-empty">
        <i class="bi bi-newspaper" aria-hidden="true"></i>
        <h2>Todavía no hay noticias</h2>
        <p>Cuando publiquemos novedades, las vas a encontrar acá.</p>
        <a class="news-primary-button" href="<?= e(url('/src/views/index.php#eventos')) ?>">Ver eventos</a>
        <?php if (isAdmin()): ?>
          <a class="news-empty__admin" href="<?= e(url('/src/controllers/posts/form.php')) ?>">Publicar la primera noticia</a>
        <?php endif; ?>
      </section>
    <?php endif; ?>

    <section class="news-subscription" id="subscription" aria-labelledby="news-subscription-title">
      <div class="news-subscription__copy">
        <p class="news-section-kicker">Lista de novedades</p>
        <h2 id="news-subscription-title">Anotate a las novedades</h2>
        <p>Tu correo se guarda en la lista de la demo. Todavía no enviamos avisos por e-mail.</p>
      </div>
      <form class="news-subscription__form" action="<?= e(url('/src/controllers/newsletter/subscribe.php')) ?>" method="POST">
        <input type="hidden" name="csrf_token" value="<?= e(csrfToken()) ?>">
        <input type="hidden" name="return_to" value="/src/controllers/news/index.php">
        <label class="visually-hidden" for="news-subscription-email">Tu correo electrónico</label>
        <input id="news-subscription-email" name="email" type="email" maxlength="254"
               placeholder="tu@email.com" autocomplete="email" required>
        <button class="news-primary-button" type="submit">Anotarme</button>
      </form>
      <?php if ($mensajeNewsletter !== null): ?>
        <p class="news-subscription__message is-<?= e($mensajeNewsletter[0]) ?>" role="status">
          <?= e($mensajeNewsletter[1]) ?>
        </p>
      <?php endif; ?>
    </section>
  </div>
</main>
<script src="<?= e(url('/assets/js/news-page.js')) ?>" defer></script>

<?php
$pageContent = ob_get_clean();
include __DIR__ . '/../_layouts/layout.php';
