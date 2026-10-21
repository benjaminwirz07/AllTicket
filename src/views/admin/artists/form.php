<?php
$artist = $artist ?? null;
$errors = $errors ?? [];
$editando = !empty($artist['id']);
ob_start();
?>

<main class="container-xl py-5 text-light">
    <header class="mb-4">
        <h1 class="h3 fw-bold text-white"><?= $editando ? 'Editar artista' : 'Nuevo artista' ?></h1>
    </header>

    <form method="POST" action="/src/controllers/admin/artists/save.php" enctype="multipart/form-data"
        style="max-width: 640px;">
        <?php if ($editando): ?>
            <input type="hidden" name="id" value="<?= e($artist['id']) ?>">
        <?php endif; ?>

        <div class="mb-3">
            <label class="form-label" for="name">Nombre</label>
            <input class="form-control" type="text" id="name" name="name" maxlength="150" required
                value="<?= e($artist['name'] ?? '') ?>">
            <?php if (isset($errors['name'])): ?>
                <div class="text-danger small mt-1"><?= e($errors['name']) ?></div><?php endif; ?>
        </div>

        <div class="mb-3">
            <label class="form-label" for="description">Descripción (opcional)</label>
            <textarea class="form-control" id="description" name="description"
                rows="5"><?= e($artist['description'] ?? '') ?></textarea>
        </div>

        <div class="mb-4">
            <label class="form-label" for="image_file">Foto del artista (opcional, JPG, PNG o WebP, máx. 2 MB)</label>
            <?php if (!empty($artist['image'])): ?>
                <div class="mb-2"><img src="/<?= e($artist['image']) ?>" alt="Imagen actual"
                        style="max-height:120px;border-radius:8px;"></div>
                <div class="small text-secondary-custom mb-2">Imagen actual. Subí otra para reemplazarla.</div>
            <?php endif; ?>
            <input class="form-control" type="file" id="image_file" name="image_file"
                accept="image/jpeg,image/png,image/webp">
            <?php if (isset($errors['image'])): ?>
                <div class="text-danger small mt-1"><?= e($errors['image']) ?></div><?php endif; ?>
        </div>

        <button class="btn btn-violet-glow rounded-pill px-4" type="submit">Guardar</button>
        <a class="btn btn-outline-secondary rounded-pill px-4 ms-2"
            href="/src/controllers/admin/artists/index.php">Cancelar</a>
    </form>
</main>

<?php
$pageContent = ob_get_clean();
include __DIR__ . '/../../_layouts/layout.php';