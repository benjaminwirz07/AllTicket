<?php
$venue  = $venue ?? null;
$errors = $errors ?? [];
$editando = !empty($venue['id']);
ob_start();
?>

<main class="container-xl py-5 text-light">
  <header class="mb-4">
    <h1 class="h3 fw-bold text-white"><?= $editando ? 'Editar lugar' : 'Nuevo lugar' ?></h1>
  </header>

  <form method="POST" action="/src/controllers/admin/venues/save.php" style="max-width: 640px;">
    <?php if ($editando): ?>
      <input type="hidden" name="id" value="<?= e($venue['id']) ?>">
    <?php endif; ?>

    <div class="mb-3">
      <label class="form-label" for="name">Nombre</label>
      <input class="form-control" type="text" id="name" name="name" maxlength="150" required
             value="<?= e($venue['name'] ?? '') ?>">
      <?php if (isset($errors['name'])): ?><div class="text-danger small mt-1"><?= e($errors['name']) ?></div><?php endif; ?>
    </div>

    <div class="mb-3">
      <label class="form-label" for="address">Dirección</label>
      <input class="form-control" type="text" id="address" name="address" maxlength="255" required
             value="<?= e($venue['address'] ?? '') ?>">
      <?php if (isset($errors['address'])): ?><div class="text-danger small mt-1"><?= e($errors['address']) ?></div><?php endif; ?>
    </div>

    <div class="row">
      <div class="col-md-6 mb-3">
        <label class="form-label" for="city">Ciudad</label>
        <input class="form-control" type="text" id="city" name="city" maxlength="100" required
               value="<?= e($venue['city'] ?? '') ?>">
        <?php if (isset($errors['city'])): ?><div class="text-danger small mt-1"><?= e($errors['city']) ?></div><?php endif; ?>
      </div>
      <div class="col-md-6 mb-3">
        <label class="form-label" for="province">Provincia (opcional)</label>
        <input class="form-control" type="text" id="province" name="province" maxlength="100"
               value="<?= e($venue['province'] ?? '') ?>">
        <?php if (isset($errors['province'])): ?><div class="text-danger small mt-1"><?= e($errors['province']) ?></div><?php endif; ?>
      </div>
    </div>

    <div class="mb-4">
      <label class="form-label" for="capacity">Capacidad</label>
      <input class="form-control" type="number" id="capacity" name="capacity" min="1" required
             value="<?= e((string) ($venue['capacity'] ?? '')) ?>">
      <?php if (isset($errors['capacity'])): ?><div class="text-danger small mt-1"><?= e($errors['capacity']) ?></div><?php endif; ?>
    </div>

    <button class="btn btn-violet-glow rounded-pill px-4" type="submit">Guardar</button>
    <a class="btn btn-outline-secondary rounded-pill px-4 ms-2" href="/src/controllers/admin/venues/index.php">Cancelar</a>
  </form>
</main>

<?php
$pageContent = ob_get_clean();
include __DIR__ . '/../../_layouts/layout.php';