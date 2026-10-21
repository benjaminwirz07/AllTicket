<?php ob_start(); ?>

<main class="container-xl py-5 text-light">
  <header class="row mb-4">
    <div class="col">
      <h1 class="h3 fw-bold text-white">Artistas</h1>
      <p class="text-secondary-custom mb-0">
        <a href="/src/controllers/admin/index.php">&larr; Volver al panel</a>
      </p>
    </div>
    <div class="col d-flex justify-content-end align-items-center">
      <a class="btn btn-violet-glow rounded-pill px-4" href="/src/controllers/admin/artists/form.php">Nuevo artista</a>
    </div>
  </header>

  <?php if (!empty($flash)): ?>
    <div class="alert alert-<?= e($flash['type']) ?>" role="alert"><?= e($flash['msg']) ?></div>
  <?php endif; ?>

  <?php if (!empty($artists)): ?>
    <div class="table-responsive">
      <table class="table table-dark align-middle">
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
              <td><?= e($artist['name']) ?></td>
              <td><?= e(mb_strimwidth((string) $artist['description'], 0, 80, '…')) ?></td>
              <td><?= e($artist['image']) ?></td>
              <td class="text-end text-nowrap">
                <a class="btn btn-sm btn-secondary me-1"
                   href="/src/controllers/admin/artists/form.php?id=<?= urlencode($artist['id']) ?>">Editar</a>
                <form method="POST" action="/src/controllers/admin/artists/delete.php" class="d-inline"
                      onsubmit="return confirm('¿Eliminar este artista?');">
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