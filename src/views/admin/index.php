<?php
require_once __DIR__ . '/../../config/bootstrap.php';
requireAuth();
requireAdmin($pdo);

$secciones = [
    ['/src/controllers/admin/concerts/index.php', 'bi-ticket-perforated', 'Recitales', 'Crear, editar y cancelar recitales y sus entradas.'],
    ['/src/controllers/admin/sales/index.php',    'bi-receipt',           'Ventas',    'Compras, estados de pago y entradas vendidas.'],
    ['/src/controllers/admin/venues/index.php',   'bi-geo-alt',           'Lugares',   'Estadios, teatros y salas.'],
    ['/src/controllers/admin/artists/index.php',  'bi-mic',               'Artistas',  'Bandas y solistas que dan los recitales.'],
    ['/src/controllers/posts/index.php',          'bi-newspaper',         'Posts',     'Publicaciones y noticias.'],
];
ob_start();
?>

<main class="container-xl py-5 text-light">
    <header class="mb-4">
        <h1 class="h3 fw-bold text-white mb-1">Panel de administración</h1>
        <p class="text-secondary-custom mb-0">Hola, admin. Este es el estado de AllTicket.</p>
    </header>

    <!-- Números -->
    <div class="row g-3 mb-5">
        <div class="col-6 col-lg-3">
            <a class="admin-stat" href="/src/controllers/admin/concerts/index.php">
                <div class="admin-stat__label">Recitales activos</div>
                <div class="admin-stat__value"><?= (int) $stats['recitales_activos'] ?></div>
            </a>
        </div>
        <div class="col-6 col-lg-3">
            <a class="admin-stat" href="/src/controllers/admin/sales/index.php?status=paid">
                <div class="admin-stat__label">Ventas pagadas</div>
                <div class="admin-stat__value"><?= (int) $stats['ventas_pagadas'] ?></div>
            </a>
        </div>
        <div class="col-6 col-lg-3">
            <a class="admin-stat" href="/src/controllers/admin/sales/index.php?status=pending">
                <div class="admin-stat__label">Pagos pendientes</div>
                <div class="admin-stat__value"><?= (int) $stats['ventas_pendientes'] ?></div>
            </a>
        </div>
        <div class="col-6 col-lg-3">
            <div class="admin-stat">
                <div class="admin-stat__label">Recaudado</div>
                <div class="admin-stat__value admin-stat__value--accent"><?= e(formatPrecio($stats['recaudado'])) ?></div>
            </div>
        </div>
    </div>

    <!-- Secciones -->
    <h2 class="h5 text-white mb-3">Gestionar</h2>
    <div class="row g-4">
        <?php foreach ($secciones as [$url, $icono, $titulo, $texto]): ?>
            <div class="col-12 col-md-6 col-lg-4">
                <a href="<?= e($url) ?>" class="text-decoration-none">
                    <div class="card admin-card bg-card-dark text-light rounded-4 shadow h-100">
                        <div class="card-body p-4">
                            <span class="admin-card__icon"><i class="bi <?= e($icono) ?>"></i></span>
                            <h3 class="h5 text-white"><?= e($titulo) ?></h3>
                            <p class="small text-secondary-custom mb-0"><?= e($texto) ?></p>
                        </div>
                    </div>
                </a>
            </div>
        <?php endforeach; ?>
    </div>
</main>

<?php
$pageContent = ob_get_clean();
include __DIR__ . '/../_layouts/layout.php';