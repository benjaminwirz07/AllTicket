<?php ob_start(); ?>

<main class="container-xl py-5 text-light">
  <header class="row mb-4">
    <div class="col">
      <h1 class="h3 fw-bold text-white">Lugares</h1>
      <p class="text-secondary-custom mb-0">
        <a href="/src/controllers/admin/index.php">&larr; Volver al panel</a>
      </p>
    </div>
    <div class="col d-flex justify-content-end align-items-center">
      <a class="btn btn-violet-glow rounded-pill px-4" href="/src/controllers/admin/venues/form.php">Nuevo lugar</a>
    </div>
  </header>

  <?php if (!empty($flash)): ?>
    <div class="alert alert-<?= e($flash['type']) ?>" role="alert"><?= e($flash['msg']) ?></div>
  <?php endif; ?>

  <?php if (!empty($venues)): ?>
    <div class="table-responsive">
      <table class="table table-dark align-middle">
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
              <td><?= e($venue['name']) ?></td>
              <td><?= e($venue['address']) ?></td>
              <td><?= e($venue['city']) ?></td>
              <td><?= e($venue['province']) ?></td>
              <td class="text-end"><?= e(number_format((int) $venue['capacity'], 0, ',', '.')) ?></td>
              <td class="text-end text-nowrap">
                <a class="btn btn-sm btn-secondary me-1"
                   href="/src/controllers/admin/venues/form.php?id=<?= urlencode($venue['id']) ?>">Editar</a>
                <form method="POST" action="/src/controllers/admin/venues/delete.php" class="d-inline"
                      onsubmit="return confirm('¿Eliminar este lugar?');">
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