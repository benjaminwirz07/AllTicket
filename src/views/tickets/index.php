<?php
$estados = [
    'pending' => ['Pendiente de pago', 'warning'],
    'paid' => ['Pagada', 'success'],
    'cancelled' => ['Cancelada', 'secondary'],
];
$metodos = [
    'mercadopago' => 'Mercado Pago',
    'tarjeta' => 'Tarjeta de crédito o débito',
    'transferencia' => 'Transferencia bancaria',
    'efectivo' => 'Efectivo (Rapipago / Pago Fácil)',
];
$estadosButaca = [
    'held' => 'Reservada',
    'confirmed' => 'Confirmada',
    'released' => 'Liberada',
    'expired' => 'Vencida',
];
ob_start();
?>

<main class="container-xl py-5 text-light">
    <header class="mb-4">
        <h1 class="h2 fw-bold text-white mb-1">Mis <span class="text-gradient">entradas</span></h1>
        <p class="text-secondary-custom mb-0">Consultá tus compras, entradas y datos de cada evento.</p>
    </header>

    <?php if ($compras === []): ?>
        <div class="card bg-card-dark text-light border-0 rounded-4 shadow">
            <div class="card-body text-center p-5">
                <i class="bi bi-ticket-perforated fs-1 text-violet-neon" aria-hidden="true"></i>
                <h2 class="h4 text-white mt-3">Todavía no tenés entradas</h2>
                <p class="text-secondary-custom">Cuando realices una compra, vas a poder verla acá.</p>
                <a href="<?= e(url('/src/views/index.php#eventos')) ?>"
                    class="btn btn-violet-glow rounded-pill px-4">Ver eventos</a>
            </div>
        </div>
    <?php else: ?>
        <div class="d-grid gap-4">
            <?php foreach ($compras as $compra): ?>
                <?php
                [$estadoTexto, $estadoColor] = $estados[$compra['status']] ?? [$compra['status'], 'secondary'];
                $nombreMetodo = $metodos[$compra['payment_method']] ?? ($compra['payment_method'] ?? 'No especificado');
                ?>
                <section class="card bg-card-dark text-light border border-secondary-subtle rounded-4 shadow">
                    <div class="card-header bg-transparent border-secondary-subtle p-4">
                        <div class="d-flex flex-wrap justify-content-between align-items-center gap-3">
                            <div>
                                <p class="small text-secondary-custom mb-1">Pedido</p>
                                <h2 class="h5 text-white mb-0">#<?= e(strtoupper(substr($compra['id'], 0, 8))) ?></h2>
                            </div>
                            <span class="badge text-bg-<?= e($estadoColor) ?>"><?= e($estadoTexto) ?></span>
                        </div>
                    </div>

                    <div class="card-body p-4">
                        <?php if (!empty($entradasPorCompra[$compra['id']])): ?>
                            <div class="d-grid gap-4">
                                <?php foreach ($entradasPorCompra[$compra['id']] as $entrada): ?>
                                    <article class="border-bottom border-secondary-subtle pb-4">
                                        <div class="d-flex flex-column flex-sm-row align-items-sm-center gap-3 mb-3">
                                            <?php if (!empty($entrada['image'])): ?>
                                                <img src="<?= e(url('/' . ltrim($entrada['image'], '/'))) ?>"
                                                    alt="<?= e($entrada['concert'] . ' - ' . $entrada['artist']) ?>"
                                                    class="rounded-3 flex-shrink-0"
                                                    style="width: 112px; height: 112px; object-fit: cover;"
                                                    loading="lazy">
                                            <?php endif; ?>
                                            <h3 class="h5 text-white mb-0"><?= e($entrada['concert']) ?></h3>
                                        </div>
                                        <p class="text-secondary-custom mb-3">
                                            <?= e($entrada['artist']) ?><br>
                                            <i class="bi bi-calendar-event me-1" aria-hidden="true"></i>
                                            <?= e(formatFecha($entrada['date'], $entrada['time'])) ?><br>
                                            <i class="bi bi-geo-alt me-1" aria-hidden="true"></i>
                                            <?= e($entrada['venue']) ?> · <?= e($entrada['address']) ?>,
                                            <?= e($entrada['city']) ?><?= !empty($entrada['province']) ? ', ' . e($entrada['province']) : '' ?>
                                        </p>

                                        <div class="row g-3">
                                            <div class="col-12 col-md-6">
                                                <p class="small text-secondary-custom mb-1">
                                                    A nombre de <?= e(trim($compra['first_name'] . ' ' . $compra['last_name'])) ?>
                                                </p>
                                                <ul class="list-unstyled small mb-0">
                                                    <li class="d-flex justify-content-between gap-3 mb-1">
                                                        <span>Tipo de entrada</span><strong><?= e($entrada['type']) ?></strong>
                                                    </li>
                                                    <li class="d-flex justify-content-between gap-3 mb-1">
                                                        <span>Cantidad</span><strong><?= (int) $entrada['quantity'] ?></strong>
                                                    </li>
                                                    <li class="d-flex justify-content-between gap-3 mb-1">
                                                        <span>Precio unitario</span><strong><?= e(formatPrecio($entrada['price'])) ?></strong>
                                                    </li>
                                                    <li class="d-flex justify-content-between gap-3">
                                                        <span>Subtotal</span>
                                                        <strong><?= e(formatPrecio((float) $entrada['price'] * (int) $entrada['quantity'])) ?></strong>
                                                    </li>
                                                </ul>
                                            </div>
                                            <div class="col-12 col-md-6">
                                                <?php $butacas = $butacasPorEntrada[$compra['id']][$entrada['ticket_id']] ?? []; ?>
                                                <?php if ($butacas !== []): ?>
                                                    <p class="small text-secondary-custom mb-1">Butacas asignadas</p>
                                                    <div class="d-flex flex-wrap gap-2">
                                                        <?php foreach ($butacas as $butaca): ?>
                                                            <?php $partesButaca = explode('-', $butaca['seat_code']); ?>
                                                            <span class="badge text-bg-dark border border-secondary-subtle">
                                                                Fila <?= e($partesButaca[0]) ?> · Butaca <?= e($partesButaca[1] ?? '') ?>
                                                                (<?= e($estadosButaca[$butaca['status']] ?? $butaca['status']) ?>)
                                                            </span>
                                                        <?php endforeach; ?>
                                                    </div>
                                                <?php else: ?>
                                                    <p class="small text-secondary-custom mb-0">Ingreso libre, sin butaca asignada.</p>
                                                <?php endif; ?>
                                            </div>
                                        </div>
                                    </article>
                                <?php endforeach; ?>
                            </div>
                        <?php else: ?>
                            <p class="text-secondary-custom mb-0">Este pedido no tiene entradas disponibles para mostrar.</p>
                        <?php endif; ?>

                        <div class="row g-3 pt-3">
                            <div class="col-12 col-md-6">
                                <p class="small text-secondary-custom mb-1">Fecha de compra</p>
                                <p class="mb-0"><?= e($compra['sale_date']) ?></p>
                            </div>
                            <div class="col-12 col-md-6">
                                <p class="small text-secondary-custom mb-1">Medio de pago</p>
                                <p class="mb-0"><?= e($nombreMetodo) ?></p>
                            </div>
                            <div class="col-12">
                                <p class="small text-secondary-custom mb-1">Total del pedido</p>
                                <p class="h5 text-white mb-0"><?= e(formatPrecio($compra['total'])) ?></p>
                            </div>
                        </div>

                        <?php if ($compra['status'] === 'pending'): ?>
                            <p class="alert alert-warning small mt-4 mb-0" role="status">
                                La compra queda pendiente hasta que se confirme el pago. La entrada todavía no es válida para ingresar.
                            </p>
                        <?php elseif ($compra['status'] === 'cancelled'): ?>
                            <p class="alert alert-secondary small mt-4 mb-0" role="status">
                                Este pedido fue cancelado y sus entradas no son válidas.
                            </p>
                        <?php endif; ?>
                    </div>
                </section>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</main>

<?php
$pageContent = ob_get_clean();
include __DIR__ . '/../_layouts/layout.php';
