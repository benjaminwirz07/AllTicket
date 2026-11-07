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
    'mercadopago' => 'Mercado Pago (registro anterior de demo)',
    'tarjeta' => 'Tarjeta (registro anterior de demo)',
    'efectivo' => 'Efectivo (registro anterior de demo)',
];
[$txtEstado, $colorEstado] = $estados[$sale['status']] ?? [$sale['status'], 'secondary'];
$nombreCompleto = trim($sale['first_name'] . ' ' . $sale['last_name']);
ob_start();
?>

<main class="container-xl py-5 text-light">
    <header class="admin-page-header mb-4">
        <div>
            <p class="admin-page-kicker mb-1">VENTAS / DETALLE</p>
            <h1 class="h3 fw-bold text-white mb-1">
                Pedido <?= e(strtoupper(substr($sale['id'], 0, 8))) ?>
                <span class="badge text-bg-<?= e($colorEstado) ?> fs-6 align-middle ms-2"><?= e($txtEstado) ?></span>
            </h1>
            <p class="admin-page-description mb-0">Datos de la compra y del comprador.</p>
        </div>
        <a class="btn btn-sm btn-outline-secondary rounded-pill" href="/src/controllers/admin/sales/index.php">
            <i class="bi bi-arrow-left me-1" aria-hidden="true"></i>Ventas
        </a>
    </header>
    <?php if (!empty($flash)): ?>
        <div class="alert alert-<?= e($flash['type']) ?>" role="alert"><?= e($flash['msg']) ?></div>
    <?php endif; ?>

    <?php if ($sale['status'] === 'pending'): ?>
        <div class="d-flex flex-wrap gap-2 mb-4">
            <form method="POST" action="/src/controllers/admin/sales/update_status.php"
                onsubmit="return confirm('¿Marcar como pagada para simular la confirmación? No se recibió ni se procesó dinero.');">
                <input type="hidden" name="csrf_token" value="<?= e(csrfToken()) ?>">
                <input type="hidden" name="id" value="<?= e($sale['id']) ?>">
                <input type="hidden" name="action" value="confirm">
                <button class="btn btn-success rounded-pill px-4" type="submit">Simular confirmación de pago</button>
            </form>

            <form method="POST" action="/src/controllers/admin/sales/update_status.php"
                onsubmit="return confirm('¿Cancelar este pedido pendiente? Se liberan las butacas y se devuelve el stock.');">
                <input type="hidden" name="csrf_token" value="<?= e(csrfToken()) ?>">
                <input type="hidden" name="id" value="<?= e($sale['id']) ?>">
                <input type="hidden" name="action" value="cancel">
                <button class="btn btn-danger rounded-pill px-4" type="submit">Cancelar pedido pendiente</button>
            </form>
        </div>
    <?php elseif ($sale['status'] === 'paid'): ?>
        <p class="alert alert-info" role="status">Esta compra se confirmó en modo demo. No se procesó ningún cobro y no se puede cancelar desde el panel.</p>
    <?php endif; ?>

    <div class="row g-4">
        <div class="col-12 col-lg-5">
            <div class="card bg-card-dark text-light border-0 rounded-4 shadow h-100">
                <div class="card-body p-4">
                    <h2 class="h5 text-white mb-3">Comprador</h2>
                    <ul class="list-unstyled small mb-4">
                        <li class="d-flex justify-content-between mb-1">
                            <span>Nombre</span><strong><?= e($nombreCompleto) ?></strong>
                        </li>
                        <li class="d-flex justify-content-between mb-1">
                            <span>Usuario</span><strong><?= e($sale['usuario']) ?></strong>
                        </li>
                        <li class="d-flex justify-content-between mb-1">
                            <span>Email</span><strong><?= e($sale['email']) ?></strong>
                        </li>
                        <li class="d-flex justify-content-between mb-1">
                            <span>DNI</span><strong><?= e($sale['dni']) ?></strong>
                        </li>
                        <li class="d-flex justify-content-between">
                            <span>Teléfono</span><strong><?= e($sale['phone'] ?? '-') ?></strong>
                        </li>
                    </ul>

                    <h2 class="h5 text-white mb-3">Pago</h2>
                    <ul class="list-unstyled small mb-0">
                        <li class="d-flex justify-content-between mb-1">
                            <span>Fecha</span><strong><?= e($sale['sale_date']) ?></strong>
                        </li>
                        <li class="d-flex justify-content-between mb-1">
                            <span>Medio</span><strong><?= e($metodos[$sale['payment_method']] ?? ($sale['payment_method'] ?? '-')) ?></strong>
                        </li>
                        <li class="d-flex justify-content-between">
                            <span>Total</span><strong><?= e(formatPrecio($sale['total'])) ?></strong>
                        </li>
                    </ul>
                </div>
            </div>
        </div>

        <div class="col-12 col-lg-7">
            <div class="card bg-card-dark text-light border-0 rounded-4 shadow h-100">
                <div class="card-body p-4">
                    <h2 class="h5 text-white mb-3">Entradas</h2>

                    <?php foreach ($details as $d): ?>
                        <div class="mb-4">
                            <h3 class="h6 text-white mb-1"><?= e($d['concert']) ?></h3>
                            <p class="small text-secondary-custom mb-2">
                                <?= e(formatFecha($d['date'], $d['time'])) ?> · <?= e($d['venue']) ?>
                            </p>
                            <ul class="list-unstyled small mb-2">
                                <li class="d-flex justify-content-between">
                                    <span>Ubicación</span><strong><?= e($d['type']) ?></strong>
                                </li>
                                <li class="d-flex justify-content-between">
                                    <span>Cantidad</span><strong><?= (int) $d['quantity'] ?></strong>
                                </li>
                                <li class="d-flex justify-content-between"><span>Precio
                                        unitario</span><strong><?= e(formatPrecio($d['price'])) ?></strong></li>
                                <li class="d-flex justify-content-between">
                                    <span>Subtotal</span><strong><?= e(formatPrecio((float) $d['price'] * (int) $d['quantity'])) ?></strong>
                                </li>
                            </ul>

                            <?php if (!empty($seatsByTicket[$d['ticket_id']])): ?>
                                <p class="small text-secondary-custom mb-1">Butacas</p>
                                <div>
                                    <?php foreach ($seatsByTicket[$d['ticket_id']] as $b): ?>
                                        <?php $p = explode('-', $b['seat_code']); ?>
                                        <span class="ct-chip">Fila <?= e($p[0]) ?> · Butaca <?= e($p[1] ?? '') ?>
                                            (<?= e($b['status']) ?>)</span>
                                    <?php endforeach; ?>
                                </div>
                            <?php else: ?>
                                <p class="small text-secondary-custom mb-0">Ingreso libre, sin butaca asignada.</p>
                            <?php endif; ?>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>
    </div>
</main>

<?php
$pageContent = ob_get_clean();
include __DIR__ . '/../../_layouts/layout.php';