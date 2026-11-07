<?php
require_once __DIR__ . '/../../../config/bootstrap.php';
requireAuth();
requireAdmin($pdo);

$estados = [
    'pending' => ['Pendiente', 'warning'],
    'paid' => ['Pagada', 'success'],
    'cancelled' => ['Cancelada', 'secondary'],
];
$metodos = [
    'prueba' => 'Pago de prueba',
    'transferencia' => 'Transferencia simulada',
    'mercadopago' => 'Mercado Pago (demo anterior)',
    'tarjeta' => 'Tarjeta (demo anterior)',
    'efectivo' => 'Efectivo (demo anterior)',
];
ob_start();
?>

<main class="container-xl py-5 text-light">
    <header class="admin-page-header mb-4">
        <div>
            <p class="admin-page-kicker mb-1">ADMINISTRACIÓN</p>
            <h1 class="h3 fw-bold text-white mb-1">Ventas</h1>
            <p class="admin-page-description mb-0">Resumen y compras organizados por recital.</p>
        </div>
        <a class="btn btn-sm btn-outline-secondary rounded-pill" href="/src/controllers/admin/index.php">
            <i class="bi bi-arrow-left me-1" aria-hidden="true"></i>Panel
        </a>
    </header>

    <?php if (!empty($flash)): ?>
        <div class="alert alert-<?= e($flash['type']) ?>" role="alert"><?= e($flash['msg']) ?></div>
    <?php endif; ?>
    <div class="d-flex flex-wrap justify-content-between align-items-center gap-3 mb-3">
        <h2 class="h5 text-white mb-0">Resumen por recital</h2>
        <nav class="d-flex flex-wrap gap-2" aria-label="Filtrar compras por estado">
            <a class="btn btn-sm <?= $filtro === '' ? 'btn-violet-glow' : 'btn-outline-secondary' ?> rounded-pill"
                href="/src/controllers/admin/sales/index.php">Todas</a>
            <?php foreach ($estados as $clave => [$nombre]): ?>
                <a class="btn btn-sm <?= $filtro === $clave ? 'btn-violet-glow' : 'btn-outline-secondary' ?> rounded-pill"
                    href="/src/controllers/admin/sales/index.php?status=<?= e($clave) ?>"><?= e($nombre) ?></a>
            <?php endforeach; ?>
        </nav>
    </div>

    <?php if (!empty($resumen)): ?>
        <div class="sales-concert-list mb-4">
            <?php foreach ($resumen as $r): ?>
                <?php
                $collapseId = 'compras-recital-' . str_replace('-', '', $r['id']);
                $compras = $comprasPorRecital[$r['id']] ?? [];
                ?>
                <article class="sales-concert">
                    <button class="sales-concert-toggle collapsed" type="button" data-bs-toggle="collapse"
                        data-bs-target="#<?= e($collapseId) ?>" aria-expanded="false"
                        aria-controls="<?= e($collapseId) ?>">
                        <span class="sales-concert-heading">
                            <strong><?= e($r['name']) ?></strong>
                            <span class="sales-concert-meta">
                                <small><?= e(formatFecha($r['date'], $r['time'])) ?></small>
                                <span class="sales-concert-count">
                                    <i class="bi bi-receipt" aria-hidden="true"></i>
                                    <?= count($compras) ?> <?= count($compras) === 1 ? 'compra' : 'compras' ?>
                                </span>
                            </span>
                        </span>
                        <span class="sales-concert-metrics">
                            <span class="sales-concert-metric">
                                <strong><?= (int) $r['vendidas'] ?></strong><small>Vendidas</small>
                            </span>
                            <span class="sales-concert-metric">
                                <strong><?= (int) $r['pendientes'] ?></strong><small>Pendientes</small>
                            </span>
                            <span class="sales-concert-metric">
                                <strong><?= number_format((int) $r['stock'], 0, ',', '.') ?></strong><small>Stock total</small>
                            </span>
                            <span class="sales-concert-metric sales-concert-metric--amount">
                                <strong><?= e(formatPrecio($r['recaudado'])) ?></strong><small>Pagado (demo)</small>
                            </span>
                        </span>
                        <i class="bi bi-chevron-down sales-concert-chevron" aria-hidden="true"></i>
                    </button>

                    <div class="collapse" id="<?= e($collapseId) ?>">
                        <div class="sales-concert-purchases">
                            <?php if (!empty($compras)): ?>
                                <?php foreach ($compras as $compra): ?>
                                    <?php [$txtEstado, $colorEstado] = $estados[$compra['status']] ?? [$compra['status'], 'secondary']; ?>
                                    <article class="sales-purchase">
                                        <div class="sales-purchase-order">
                                            <strong>Pedido <?= e(strtoupper(substr($compra['id'], 0, 8))) ?></strong>
                                            <small><?= e($compra['sale_date']) ?></small>
                                        </div>
                                        <div class="sales-purchase-customer">
                                            <strong><?= e($compra['usuario']) ?></strong>
                                            <small><?= e($compra['email']) ?></small>
                                        </div>
                                        <div class="sales-purchase-facts">
                                            <span><small>Entradas</small><strong><?= (int) $compra['cantidad'] ?></strong></span>
                                            <span><small>Importe recital</small><strong><?= e(formatPrecio($compra['subtotal'])) ?></strong></span>
                                            <span><small>Pago</small><strong><?= e($metodos[$compra['payment_method']] ?? ($compra['payment_method'] ?? '-')) ?></strong></span>
                                            <span><small>Estado</small><span class="badge text-bg-<?= e($colorEstado) ?>"><?= e($txtEstado) ?></span></span>
                                        </div>
                                        <a class="btn btn-sm btn-secondary sales-purchase-action"
                                            href="/src/controllers/admin/sales/show.php?id=<?= urlencode($compra['id']) ?>">Ver compra</a>
                                    </article>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <p class="small text-secondary-custom mb-0">No hay compras para este recital con el filtro seleccionado.</p>
                            <?php endif; ?>
                        </div>
                    </div>
                </article>
            <?php endforeach; ?>
        </div>
        <p class="small text-secondary-custom mb-5">
            "Vendidas" y el total marcado como pagado son datos de demostración, no cobros reales.
            "Pendientes" son compras que esperan la confirmación simulada y se liberan solas a las 24 hs.
        </p>
    <?php else: ?>
        <p class="text-secondary-custom mb-5">No hay recitales cargados.</p>
    <?php endif; ?>
</main>

<?php
$pageContent = ob_get_clean();
include __DIR__ . '/../../_layouts/layout.php';