<?php
require_once __DIR__ . '/../../../config/bootstrap.php';
requireAuth();
requireAdmin($pdo);
ob_start();
?>

<main class="container-xl py-5 text-light">
  <header class="admin-page-header mb-4">
    <div>
      <p class="admin-page-kicker mb-1">ESPACIOS</p>
      <h1 class="h3 fw-bold text-white mb-1">Lugares</h1>
      <p class="admin-page-description mb-0">Gestioná sedes, direcciones y capacidades.</p>
    </div>
    <div class="d-flex flex-wrap align-items-center gap-2">
      <a class="btn btn-sm btn-outline-secondary rounded-pill" href="/src/controllers/admin/index.php">
        <i class="bi bi-arrow-left me-1" aria-hidden="true"></i>Panel
      </a>
      <a class="btn btn-violet-glow rounded-pill px-4" href="/src/controllers/admin/venues/form.php">Nuevo lugar</a>
    </div>
  </header>

  <?php if (!empty($flash)): ?>
    <div class="alert alert-<?= e($flash['type']) ?>" role="alert"><?= e($flash['msg']) ?></div>
  <?php endif; ?>

  <?php if (!empty($venues)): ?>
    <div class="table-responsive admin-table-wrap">
      <table class="table table-dark align-middle admin-table">
        <thead>
          <tr>
            <th>Nombre</th>
            <th>Dirección</th>
            <th>Ciudad</th>
            <th>Provincia</th>
            <th class="text-end">Capacidad</th>
            <th class="text-end">Acciones</th>
          </tr>
        </thead>
        <tbody>
          <?php foreach ($venues as $venue): ?>
            <tr>
              <td><span class="admin-table__primary"><?= e($venue['name']) ?></span></td>
              <td><?= e($venue['address']) ?></td>
              <td><?= e($venue['city']) ?></td>
              <td><?= e($venue['province']) ?></td>
              <td class="text-end"><?= e(number_format((int) $venue['capacity'], 0, ',', '.')) ?></td>
              <td class="admin-table__actions">
                <a class="btn btn-sm btn-secondary me-1"
                   href="/src/controllers/admin/venues/form.php?id=<?= urlencode($venue['id']) ?>">Editar</a>
                <form method="POST" action="/src/controllers/admin/venues/delete.php" class="d-inline"
                      onsubmit="return confirm('¿Eliminar este lugar?');">
                  <input type="hidden" name="csrf_token" value="<?= e(csrfToken()) ?>">
                  <input type="hidden" name="id" value="<?= e($venue['id']) ?>">
                  <button class="btn btn-sm btn-danger" type="submit">Eliminar</button>
                </form>
              </td>
            </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>
  <?php else: ?>
    <p class="text-secondary-custom">No hay lugares cargados todavía.</p>
  <?php endif; ?>
</main>

<?php
$pageContent = ob_get_clean();
include __DIR__ . '/../../_layouts/layout.php';