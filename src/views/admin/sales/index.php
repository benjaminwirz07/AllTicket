<?php
$estados = [
    'pending' => ['Pendiente', 'warning'],
    'paid' => ['Pagada', 'success'],
    'cancelled' => ['Cancelada', 'secondary'],
];
$metodos = [
    'mercadopago' => 'Mercado Pago',
    'tarjeta' => 'Tarjeta',
    'transferencia' => 'Transferencia',
    'efectivo' => 'Efectivo',
];
ob_start();
?>

<main class="container-xl py-5 text-light">
    <header class="mb-4">
        <h1 class="h3 fw-bold text-white">Ventas</h1>
        <p class="text-secondary-custom mb-0">
            <a href="/src/controllers/admin/index.php">&larr; Volver al panel</a>
        </p>
    </header>

    <?php if (!empty($flash)): ?>
        <div class="alert alert-<?= e($flash['type']) ?>" role="alert"><?= e($flash['msg']) ?></div>
    <?php endif; ?>
    <!-- Resumen por recital -->
    <h2 class="h5 text-white mb-3">Resumen por recital</h2>
    <?php if (!empty($resumen)): ?>
        <div class="table-responsive mb-5">
            <table class="table table-dark align-middle">
                <thead>
                    <tr>
                        <th>Recital</th>
                        <th>Fecha</th>
                        <th class="text-end">Vendidas</th>
                        <th class="text-end">Pendientes</th>
                        <th class="text-end">Stock total</th>
                        <th class="text-end">Recaudado</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($resumen as $r): ?>
                        <tr>
                            <td><?= e($r['name']) ?></td>
                            <td><?= e(formatFecha($r['date'], $r['time'])) ?></td>
                            <td class="text-end"><?= (int) $r['vendidas'] ?></td>
                            <td class="text-end"><?= (int) $r['pendientes'] ?></td>
                            <td class="text-end"><?= (int) $r['stock'] ?></td>
                            <td class="text-end"><?= e(formatPrecio($r['recaudado'])) ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <p class="small text-secondary-custom mb-5">
            "Vendidas" y "Recaudado" cuentan solo las ventas pagadas. "Pendientes" son compras
            que esperan el pago y se liberan solas a las 24 hs.
        </p>
    <?php else: ?>
        <p class="text-secondary-custom mb-5">No hay recitales cargados.</p>
    <?php endif; ?>

    <!-- Listado de ventas -->
    <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3">
        <h2 class="h5 text-white mb-0">Compras</h2>
        <nav class="d-flex gap-2">
            <a class="btn btn-sm <?= $filtro === '' ? 'btn-violet-glow' : 'btn-outline-secondary' ?> rounded-pill"
                href="/src/controllers/admin/sales/index.php">Todas</a>
            <?php foreach ($estados as $clave => [$nombre]): ?>
                <a class="btn btn-sm <?= $filtro === $clave ? 'btn-violet-glow' : 'btn-outline-secondary' ?> rounded-pill"
                    href="/src/controllers/admin/sales/index.php?status=<?= e($clave) ?>"><?= e($nombre) ?></a>
            <?php endforeach; ?>
        </nav>
    </div>

    <?php if (!empty($sales)): ?>
        <div class="table-responsive">
            <table class="table table-dark align-middle">
                <thead>
                    <tr>
                        <th>Pedido</th>
                        <th>Fecha</th>
                        <th>Usuario</th>
                        <th>Recital</th>
                        <th class="text-end">Entradas</th>
                        <th class="text-end">Total</th>
                        <th>Pago</th>
                        <th>Estado</th>
                        <th class="text-end"></th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($sales as $s): ?>
                        <?php [$txtEstado, $colorEstado] = $estados[$s['status']] ?? [$s['status'], 'secondary']; ?>
                        <tr>
                            <td><strong><?= e(strtoupper(substr($s['id'], 0, 8))) ?></strong></td>
                            <td class="text-nowrap"><?= e($s['sale_date']) ?></td>
                            <td>
                                <?= e($s['usuario']) ?><br>
                                <small class="text-secondary-custom"><?= e($s['email']) ?></small>
                            </td>
                            <td><?= e($s['recitales']) ?></td>
                            <td class="text-end"><?= (int) $s['cantidad'] ?></td>
                            <td class="text-end"><?= e(formatPrecio($s['total'])) ?></td>
                            <td><?= e($metodos[$s['payment_method']] ?? ($s['payment_method'] ?? '-')) ?></td>
                            <td><span class="badge text-bg-<?= e($colorEstado) ?>"><?= e($txtEstado) ?></span></td>
                            <td class="text-end">
                                <a class="btn btn-sm btn-secondary"
                                    href="/src/controllers/admin/sales/show.php?id=<?= urlencode($s['id']) ?>">Ver</a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <p class="small text-secondary-custom">Se muestran las 200 compras más recientes.</p>
    <?php else: ?>
        <p class="text-secondary-custom">No hay compras para mostrar.</p>
    <?php endif; ?>
</main>

<?php
$pageContent = ob_get_clean();
include __DIR__ . '/../../_layouts/layout.php';