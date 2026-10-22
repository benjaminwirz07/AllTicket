<?php
require_once __DIR__ . '/../../../config/bootstrap.php';
requireAuth();
requireAdmin($pdo);
ob_start();
$estados = ['active' => 'Activo', 'cancelled' => 'Cancelado', 'finished' => 'Finalizado'];
?>

<main class="container-xl py-5 text-light">
  <header class="row mb-4">
    <div class="col">
      <h1 class="h3 fw-bold text-white">Recitales</h1>
      <p class="text-secondary-custom mb-0">
        <a href="/src/controllers/admin/index.php">&larr; Volver al panel</a>
      </p>
    </div>
    <div class="col d-flex justify-content-end align-items-center">
      <a class="btn btn-violet-glow rounded-pill px-4" href="/src/controllers/admin/concerts/form.php">Nuevo recital</a>
    </div>
  </header>

  <?php if (!empty($flash)): ?>
    <div class="alert alert-<?= e($flash['type']) ?>" role="alert"><?= e($flash['msg']) ?></div>
  <?php endif; ?>

  <?php if (!empty($concerts)): ?>
    <div class="table-responsive">
      <table class="table table-dark align-middle">
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
              <td><?= e($c['name']) ?></td>
              <td><?= e($c['artist']) ?></td>
              <td><?= e($c['venue']) ?></td>
              <td><?= e(formatFecha($c['date'], $c['time'])) ?></td>
              <td><?= e($estados[$c['status']] ?? $c['status']) ?></td>
              <td class="text-end text-nowrap">
                <a class="btn btn-sm btn-secondary me-1"
                   href="/src/controllers/admin/concerts/form.php?id=<?= urlencode($c['id']) ?>">Editar</a>
                <?php if ($c['status'] === 'active'): ?>
                  <form method="POST" action="/src/controllers/admin/concerts/cancel.php" class="d-inline"
                        onsubmit="return confirm('¿Cancelar este recital? Dejará de verse en el home.');">
                    <input type="hidden" name="csrf_token" value="<?= e(csrfToken()) ?>">
                    <input type="hidden" name="id" value="<?= e($c['id']) ?>">
                    <button class="btn btn-sm btn-danger" type="submit">Cancelar</button>
                  </form>
                <?php endif; ?>
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
$pageContent = ob_get_clean();
include __DIR__ . '/../../_layouts/layout.php';