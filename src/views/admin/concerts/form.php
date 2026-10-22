<?php
require_once __DIR__ . '/../../../config/bootstrap.php';
requireAuth();
requireAdmin($pdo);

$concert = $concert ?? null;
$errors = $errors ?? [];
$editando = !empty($concert['id']);
$filasEntradas = $concert['tickets'] ?? [];
while (count($filasEntradas) < 3) {
    $filasEntradas[] = ['id' => '', 'type' => '', 'price' => '', 'stock' => '', 'sold' => 0];
}
$placeholders = ['General', 'Campo', 'VIP'];
$errorDe = fn(string $k) => isset($errors[$k]) ? '<div class="text-danger small mt-1">' . e($errors[$k]) . '</div>' : '';
ob_start();
?>

<main class="container-xl py-5 text-light">
    <header class="mb-4">
        <h1 class="h3 fw-bold text-white"><?= $editando ? 'Editar recital' : 'Nuevo recital' ?></h1>
    </header>

    <?php if (empty($artists) || empty($venues)): ?>
        <div class="alert alert-warning">
            Para crear un recital primero tenés que cargar al menos un
            <a href="/src/controllers/admin/artists/form.php">artista</a> y un
            <a href="/src/controllers/admin/venues/form.php">lugar</a>.
        </div>
    <?php endif; ?>

    <form method="POST" action="/src/controllers/admin/concerts/save.php" enctype="multipart/form-data" style="max-width: 720px;">
        <input type="hidden" name="csrf_token" value="<?= e(csrfToken()) ?>">
        <?php if ($editando): ?>
            <input type="hidden" name="id" value="<?= e($concert['id']) ?>">
        <?php endif; ?>

        <div class="mb-3">
            <label class="form-label" for="name">Nombre del recital</label>
            <input class="form-control" type="text" id="name" name="name" maxlength="200" required
                value="<?= e($concert['name'] ?? '') ?>">
            <?= $errorDe('name') ?>
        </div>

        <div class="row">
            <div class="col-md-6 mb-3">
                <label class="form-label" for="artist_id">Artista</label>
                <select class="form-select" id="artist_id" name="artist_id" required>
                    <option value="">Elegí un artista</option>
                    <?php foreach ($artists as $a): ?>
                        <option value="<?= e($a['id']) ?>" <?= ($concert['artist_id'] ?? '') === $a['id'] ? 'selected' : '' ?>>
                            <?= e($a['name']) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
                <?= $errorDe('artist_id') ?>
            </div>
            <div class="col-md-6 mb-3">
                <label class="form-label" for="venue_id">Lugar</label>
                <select class="form-select" id="venue_id" name="venue_id" required>
                    <option value="">Elegí un lugar</option>
                    <?php foreach ($venues as $v): ?>
                        <option value="<?= e($v['id']) ?>" <?= ($concert['venue_id'] ?? '') === $v['id'] ? 'selected' : '' ?>>
                            <?= e($v['name']) ?> (<?= e($v['city']) ?>)
                        </option>
                    <?php endforeach; ?>
                </select>
                <?= $errorDe('venue_id') ?>
            </div>
        </div>

        <div class="row">
            <div class="col-md-6 mb-3">
                <label class="form-label" for="date">Fecha</label>
                <input class="form-control" type="date" id="date" name="date" required
                    value="<?= e($concert['date'] ?? '') ?>">
                <?= $errorDe('date') ?>
            </div>
            <div class="col-md-6 mb-3">
                <label class="form-label" for="time">Hora</label>
                <input class="form-control" type="time" id="time" name="time" required
                    value="<?= e(substr((string) ($concert['time'] ?? ''), 0, 5)) ?>">
                <?= $errorDe('time') ?>
            </div>
        </div>

        <div class="row">
            <div class="col-md-6 mb-3">
                <label class="form-label" for="genre">Género (Rock, Pop, Reggaetón, Electrónica)</label>
                <input class="form-control" type="text" id="genre" name="genre" maxlength="50"
                    value="<?= e($concert['genre'] ?? '') ?>">
                <?= $errorDe('genre') ?>
            </div>
            <div class="col-md-6 mb-3">
                <label class="form-label" for="status">Estado</label>
                <select class="form-select" id="status" name="status">
                    <?php foreach (['active' => 'Activo', 'cancelled' => 'Cancelado', 'finished' => 'Finalizado'] as $val => $label): ?>
                        <option value="<?= $val ?>" <?= ($concert['status'] ?? 'active') === $val ? 'selected' : '' ?>>
                            <?= $label ?>
                        </option>
                    <?php endforeach; ?>
                </select>
                <?= $errorDe('status') ?>
            </div>
        </div>

        <div class="mb-3">
            <label class="form-label" for="image_file">Imagen del recital (JPG, PNG o WebP, máx. 2 MB)</label>
            <?php if (!empty($concert['image'])): ?>
                <div class="mb-2"><img src="/<?= e($concert['image']) ?>" alt="Imagen actual"
                        style="max-height:120px;border-radius:8px;"></div>
                <div class="small text-secondary-custom mb-2">Imagen actual. Subí otra para reemplazarla.</div>
            <?php endif; ?>
            <input class="form-control" type="file" id="image_file" name="image_file"
                accept="image/jpeg,image/png,image/webp">
            <?= $errorDe('image') ?>
        </div>

        <div class="mb-4">
            <label class="form-label" for="description">Descripción (opcional)</label>
            <textarea class="form-control" id="description" name="description"
                rows="4"><?= e($concert['description'] ?? '') ?></textarea>
        </div>

        <h2 class="h5 text-white mb-1">Entradas</h2>
        <p class="small text-secondary-custom mb-3">
            Hasta 3 tipos (por ejemplo General, Campo, VIP). Las filas vacías se ignoran.
        </p>

        <?php if (isset($errors['general'])): ?>
            <div class="alert alert-danger"><?= e($errors['general']) ?></div>
        <?php endif; ?>

        <div class="mb-4">
            <?php foreach ($filasEntradas as $i => $tk): ?>
                <div class="row g-2 mb-2 align-items-end">
                    <input type="hidden" name="tickets[<?= $i ?>][id]" value="<?= e($tk['id'] ?? '') ?>">

                    <div class="col-md-4">
                        <label class="form-label small">Tipo</label>
                        <input class="form-control" type="text" name="tickets[<?= $i ?>][type]" maxlength="100"
                            placeholder="<?= e($placeholders[$i] ?? 'Otro') ?>" value="<?= e($tk['type'] ?? '') ?>">
                    </div>
                    <div class="col-md-4">
                        <label class="form-label small">Precio ($)</label>
                        <input class="form-control" type="number" name="tickets[<?= $i ?>][price]" min="0" step="0.01"
                            value="<?= e((string) ($tk['price'] ?? '')) ?>">
                    </div>
                    <div class="col-md-4">
                        <label class="form-label small">
                            Stock
                            <?php if (!empty($tk['id']) && !empty($tk['sold'])): ?>
                                <span class="text-secondary-custom">(vendidas: <?= (int) $tk['sold'] ?>)</span>
                            <?php endif; ?>
                        </label>
                        <input class="form-control" type="number" name="tickets[<?= $i ?>][stock]" min="0"
                            value="<?= e((string) ($tk['stock'] ?? '')) ?>">
                    </div>

                    <?php if (isset($errors['tickets'][$i])): ?>
                        <div class="col-12 text-danger small"><?= e($errors['tickets'][$i]) ?></div>
                    <?php endif; ?>
                </div>
            <?php endforeach; ?>
        </div>
        <button class="btn btn-violet-glow rounded-pill px-4" type="submit">Guardar</button>
        <a class="btn btn-outline-secondary rounded-pill px-4 ms-2"
            href="/src/controllers/admin/concerts/index.php">Cancelar</a>
    </form>
</main>

<?php
$pageContent = ob_get_clean();
include __DIR__ . '/../../_layouts/layout.php';