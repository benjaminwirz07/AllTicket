<?php
// Presenta el catálogo de recitales al personal administrador.
require_once __DIR__ . '/../../../config/bootstrap.php';
requireAuth();
requireAdmin($pdo);
ob_start();
$estados = ['active' => 'Activo', 'cancelled' => 'Cancelado', 'finished' => 'Finalizado'];
?>

<main class="container-xl py-5 text-light">
  <!-- Navegación, alta de recital y mensajes del último cambio realizado. -->
  <header class="admin-page-header mb-4">
    <div>
      <p class="admin-page-kicker mb-1">PROGRAMACIÓN</p>
      <h1 class="h3 fw-bold text-white mb-1">Recitales</h1>
      <p class="admin-page-description mb-0">Administrá eventos, artistas, lugares y entradas.</p>
    </div>
    <div class="d-flex flex-wrap align-items-center gap-2">
      <a class="btn btn-sm btn-outline-secondary rounded-pill" href="/src/controllers/admin/index.php">
        <i class="bi bi-arrow-left me-1" aria-hidden="true"></i>Panel
      </a>
      <a class="btn btn-violet-glow rounded-pill px-4" href="/src/controllers/admin/concerts/form.php">Nuevo recital</a>
    </div>
  </header>

  <?php if (!empty($flash)): ?>
    <div class="alert alert-<?= e($flash['type']) ?>" role="alert"><?= e($flash['msg']) ?></div>
  <?php endif; ?>

  <!-- Tabla de eventos o estado vacío cuando todavía no hay recitales. -->
  <?php if (!empty($concerts)): ?>
    <div class="table-responsive admin-table-wrap">
      <table class="table table-dark align-middle admin-table">
        <thead>
          <tr>
            <th>Recital</th>
            <th>Artista</th>
            <th>Lugar</th>
            <th>Fecha</th>
            <th>Estado</th>
            <th class="text-end">Acciones</th>
          </tr>
        </thead>
        <tbody>
          <?php foreach ($concerts as $c): ?>
            <tr>
              <td data-label="Recital"><span class="admin-table__primary"><?= e($c['name']) ?></span></td>
              <td data-label="Artista"><?= e($c['artist']) ?></td>
              <td data-label="Lugar"><?= e($c['venue']) ?></td>
              <td data-label="Fecha"><?= e(formatFecha($c['date'], $c['time'])) ?></td>
              <td data-label="Estado"><span class="badge text-bg-<?= $c['status'] === 'active' ? 'success' : ($c['status'] === 'cancelled' ? 'secondary' : 'info') ?>"><?= e($estados[$c['status']] ?? $c['status']) ?></span></td>
              <td class="admin-table__actions" data-label="Acciones">
                <a class="btn btn-sm btn-secondary me-1"
                   href="/src/controllers/admin/concerts/form.php?id=<?= urlencode($c['id']) ?>">Editar</a>
              </td>
            </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>
  <?php else: ?>
    <p class="text-secondary-custom">No hay recitales cargados todavía.</p>
  <?php endif; ?>
</main>

<?php
// Usa el layout compartido para navegación y recursos del panel.
$pageContent = ob_get_clean();
include __DIR__ . '/../../_layouts/layout.php';