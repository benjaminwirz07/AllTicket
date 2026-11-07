<?php
require_once __DIR__ . '/../../../config/bootstrap.php';
requireAuth();
requireAdmin($pdo);
ob_start();
?>

<main class="container-xl py-5 text-light">
  <header class="admin-page-header mb-4">
    <div>
      <p class="admin-page-kicker mb-1">CATÁLOGO</p>
      <h1 class="h3 fw-bold text-white mb-1">Artistas</h1>
      <p class="admin-page-description mb-0">Gestioná los artistas que aparecen en la cartelera.</p>
    </div>
    <div class="d-flex flex-wrap align-items-center gap-2">
      <a class="btn btn-sm btn-outline-secondary rounded-pill" href="/src/controllers/admin/index.php">
        <i class="bi bi-arrow-left me-1" aria-hidden="true"></i>Panel
      </a>
      <a class="btn btn-violet-glow rounded-pill px-4" href="/src/controllers/admin/artists/form.php">Nuevo artista</a>
    </div>
  </header>

  <?php if (!empty($flash)): ?>
    <div class="alert alert-<?= e($flash['type']) ?>" role="alert"><?= e($flash['msg']) ?></div>
  <?php endif; ?>

  <?php if (!empty($artists)): ?>
    <div class="table-responsive admin-table-wrap">
      <table class="table table-dark align-middle admin-table">
        <thead>
          <tr>
            <th>Nombre</th>
            <th>Descripción</th>
            <th>Imagen</th>
            <th class="text-end">Acciones</th>
          </tr>
        </thead>
        <tbody>
          <?php foreach ($artists as $artist): ?>
            <tr>
              <td><span class="admin-table__primary"><?= e($artist['name']) ?></span></td>
              <td><?= e(mb_strimwidth((string) $artist['description'], 0, 80, '…')) ?></td>
              <td><?= e($artist['image']) ?></td>
              <td class="admin-table__actions">
                <a class="btn btn-sm btn-secondary me-1"
                   href="/src/controllers/admin/artists/form.php?id=<?= urlencode($artist['id']) ?>">Editar</a>
                <form method="POST" action="/src/controllers/admin/artists/delete.php" class="d-inline"
                      onsubmit="return confirm('¿Eliminar este artista?');">
                  <input type="hidden" name="csrf_token" value="<?= e(csrfToken()) ?>">
                  <input type="hidden" name="id" value="<?= e($artist['id']) ?>">
                  <button class="btn btn-sm btn-danger" type="submit">Eliminar</button>
                </form>
              </td>
            </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>
  <?php else: ?>
    <p class="text-secondary-custom">No hay artistas cargados todavía.</p>
  <?php endif; ?>
</main>

<?php
$pageContent = ob_get_clean();
include __DIR__ . '/../../_layouts/layout.php';