<?php
if (!defined('ALLTICKET_CHECKOUT_RENDER')) {
    http_response_code(404);
    exit;
}
ob_start();
?>
<link rel="stylesheet" href="<?= e(url('/assets/css/index.css')) ?>">
<link rel="stylesheet" href="<?= e(url('/assets/css/concert.css')) ?>">
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">

<main class="bg-main-dark text-light min-vh-100 position-relative z-1 overflow-hidden">
    <div class="container-xl py-5">
    <?php if ($vista === 'login') { ?>
        <div class="text-center py-5">
            <i class="bi bi-person-lock fs-1 text-violet-neon"></i>
            <h1 class="h3 text-white fw-bold mt-3">Iniciá sesión para comprar</h1>
            <p class="text-secondary-custom">Necesitamos saber a nombre de quién va tu entrada.</p>
            <a href="<?= e($loginUrl) ?>" class="btn btn-violet-glow rounded-pill px-4">Iniciar sesión</a>
            <p class="small text-secondary-custom mt-3">Después de ingresar, volvé al recital y elegí tus entradas de nuevo.</p>
        </div>
    <?php } elseif ($vista === 'error') { ?>
        <div class="text-center py-5">
            <i class="bi bi-exclamation-octagon fs-1 text-danger"></i>
            <h1 class="h3 text-white fw-bold mt-3">No pudimos continuar</h1>
            <?php foreach ($errores as $err) { ?>
                <p class="text-secondary-custom mb-1"><?= e($err) ?></p>
            <?php } ?>
            <a href="<?= e(!empty($datos['concert_id']) ? url('/src/views/concerts/show.php?id=' . urlencode($datos['concert_id']) . '#entradas') : url('/src/views/index.php#eventos')) ?>"
                class="btn btn-outline-violet rounded-pill px-4 mt-3">Volver al recital</a>
        </div>
    <?php } elseif ($vista === 'listo') { ?>
        <?php
        $pagada = ($venta['status'] === 'paid');
        $nombreMetodo = $metodos[$venta['payment_method']]['nombre'] ?? $venta['payment_method'];
        ?>
        <div class="mx-auto" style="max-width: 640px;">
            <div class="text-center mb-4">
                <i class="bi <?= $pagada ? 'bi-check-circle' : 'bi-hourglass-split' ?> fs-1 <?= $pagada ? 'text-success' : 'text-warning' ?>"></i>
                <h1 class="h3 text-white fw-bold mt-3"><?= $pagada ? '¡Compra confirmada!' : 'Compra registrada: pago pendiente' ?></h1>
                <?php if ($pagada) { ?>
                    <p class="small text-warning">Pago de prueba: no se realizó ningún cobro real.</p>
                <?php } ?>
                <p class="text-secondary-custom mb-0">Número de pedido: <strong class="text-white"><?= e(strtoupper(substr($venta['id'], 0, 8))) ?></strong></p>
            </div>
            <div class="step-card p-4 rounded-4 border border-secondary-subtle">
                <h2 class="h5 fw-bold text-white"><?= e($venta['name']) ?></h2>
                <p class="small text-secondary-custom mb-3">
                    <i class="bi bi-calendar-event me-1"></i><?= e(formatFecha($venta['date'], $venta['time'])) ?><br>
                    <i class="bi bi-geo-alt me-1"></i><?= e($venta['venue']) ?>, <?= e($venta['city']) ?>
                </p>
                <ul class="list-unstyled small mb-3">
                    <?php if ($compradorNombre !== '') { ?><li class="d-flex justify-content-between"><span>A nombre de</span><strong><?= e($compradorNombre) ?></strong></li><?php } ?>
                    <?php if ($compradorUsuario !== '') { ?><li class="d-flex justify-content-between"><span>Usuario</span><strong><?= e($compradorUsuario) ?></strong></li><?php } ?>
                    <li class="d-flex justify-content-between"><span>Ubicación</span><strong><?= e($venta['type']) ?></strong></li>
                    <li class="d-flex justify-content-between"><span>Cantidad</span><strong><?= (int) $venta['quantity'] ?></strong></li>
                    <li class="d-flex justify-content-between"><span>Medio de pago</span><strong><?= e($nombreMetodo) ?></strong></li>
                    <li class="d-flex justify-content-between"><span>Total</span><strong><?= e(formatPrecio($venta['total'])) ?></strong></li>
                </ul>
                <?php if ($butacas) { ?>
                    <p class="small text-secondary-custom mb-1">Tus butacas</p>
                    <div><?php foreach ($butacas as $b) { ?><span class="ct-chip"><?= e(textoButaca($b)) ?></span><?php } ?></div>
                <?php } ?>
                <?php if ($pagada && $qrEntries) { ?>
                    <div class="mt-4">
                        <h3 class="h6 text-white">Tus entradas digitales</h3>
                        <p class="small text-secondary-custom">Mostrá cada código QR al ingresar al evento.</p>
                        <div class="d-flex flex-wrap gap-3">
                            <?php foreach ($qrEntries as $qrEntry) { ?>
                                <section class="ticket-print-card border border-secondary-subtle rounded-3 p-3 text-center"
                                         data-ticket-print>
                                    <p class="small text-white mb-2">AllTicket · Entrada digital</p>
                                    <h3 class="ticket-print-card__event"><?= e($venta['name']) ?></h3>
                                    <p class="ticket-print-card__detail">
                                        <?= e(formatFecha($venta['date'], $venta['time'])) ?>
                                        ·
                                        <?= e($venta['venue']) ?>, <?= e($venta['city']) ?>
                                    </p>
                                    <p class="ticket-print-card__detail">A nombre de <?= e($compradorNombre) ?></p>
                                    <p class="small text-white mb-2">
                                        Entrada <?= (int) $qrEntry['entry_number'] ?>
                                        <?php if (!empty($qrEntry['seat_code'])) { ?>
                                            · <?= e(textoButaca($qrEntry['seat_code'])) ?>
                                        <?php } ?>
                                    </p>
                                    <?php if ($qrEntry['used_at'] !== null) { ?>
                                        <p class="badge text-bg-secondary mb-0">Ya ingresó</p>
                                    <?php } else { ?>
                                        <div class="ticket-qr" role="img"
                                             aria-label="Código QR de la entrada <?= (int) $qrEntry['entry_number'] ?>"
                                             data-ticket-qr="<?= e(absoluteUrl('/src/controllers/tickets/validate.php?id=' . rawurlencode($qrEntry['id']))) ?>"></div>
                                        <p class="small text-secondary-custom mt-2 mb-2">Presentá este código al ingresar.</p>
                                        <button class="btn btn-outline-violet rounded-pill px-3"
                                                type="button" data-ticket-print-button>
                                            <i class="bi bi-printer me-1" aria-hidden="true"></i>Imprimir entrada
                                        </button>
                                    <?php } ?>
                                </section>
                            <?php } ?>
                        </div>
                    </div>
                    <p class="small text-secondary-custom mt-3">
                        También podés volver a verlas en <a href="<?= e(url('/src/controllers/tickets/index.php')) ?>">Mis entradas</a>.
                    </p>
                <?php } elseif (!$pagada) { ?>
                    <p class="alert alert-warning small mt-3 mb-0" role="status">
                        El QR estará disponible cuando se confirme el pago.
                    </p>
                <?php } ?>
            </div>
            <?php if (!$pagada) { ?>
                <div class="ct-panel">
                    <?php if ($venta['payment_method'] === 'transferencia') { ?>
                        <p class="mb-0 small text-secondary-custom">Esta transferencia es solo una simulación: no envíes dinero. El pedido queda pendiente hasta que un administrador lo marque como confirmado para la demo.</p>
                    <?php } else { ?>
                        <p class="mb-0 small text-secondary-custom">El pedido queda pendiente hasta que un administrador simule la confirmación del pago.</p>
                    <?php } ?>
                </div>
            <?php } ?>
            <div class="text-center mt-4">
                <a href="<?= e(url('/src/views/index.php#eventos')) ?>" class="btn btn-outline-violet rounded-pill px-4">Seguir viendo recitales</a>
            </div>
        </div>
    <?php } else { ?>
        <h1 class="h3 fw-bold text-white mb-1">Finalizar compra</h1>
        <p class="alert alert-warning mb-4" role="status">
            <strong>Modo de demostración:</strong> no se realiza ningún cobro. No ingreses datos reales de tarjetas ni transfieras dinero.
        </p>
        <p class="text-secondary-custom mb-4">Revisá tu pedido y elegí cómo simular el pago.</p>
        <form action="<?= e(url('/src/controllers/concerts/checkout.php')) ?>" method="POST" id="form-pago" novalidate>
            <input type="hidden" name="confirmar" value="1">
            <input type="hidden" name="csrf_token" value="<?= e($csrf) ?>">
            <input type="hidden" name="concert_id" value="<?= e($datos['concert_id']) ?>">
            <input type="hidden" name="ticket_id" value="<?= e($datos['ticket_id']) ?>">
            <input type="hidden" name="quantity" value="<?= (int) $cantidad ?>">
            <input type="hidden" name="seats" value="<?= e(implode(',', $butacas)) ?>">
            <div class="row g-4">
                <div class="col-12 col-lg-7">
                    <h2 class="h5 fw-bold text-white mb-3">Medio de pago</h2>
                    <div class="d-grid gap-3" role="radiogroup" aria-label="Medio de pago">
                        <?php foreach ($metodos as $clave => $m) { ?>
                            <div>
                                <input type="radio" class="btn-check" name="payment_method" id="pay-<?= e($clave) ?>" value="<?= e($clave) ?>" required>
                                <label for="pay-<?= e($clave) ?>" class="ct-pay d-flex align-items-center gap-3">
                                    <span class="ct-pay__icon"><i class="bi <?= e($m['icono']) ?>"></i></span>
                                    <span>
                                        <span class="d-block fw-semibold text-white"><?= e($m['nombre']) ?></span>
                                        <span class="d-block small text-secondary-custom"><?= e($m['desc']) ?></span>
                                    </span>
                                </label>
                            </div>
                        <?php } ?>
                    </div>
                    <div class="ct-panel" data-panel="prueba" hidden>
                        <p class="small text-secondary-custom mb-0">Al confirmar, la compra se marcará como pagada solo para probar las entradas y el código QR.</p>
                    </div>
                    <div class="ct-panel" data-panel="transferencia" hidden>
                        <p class="small text-secondary-custom mb-0">No se envía dinero. El pedido queda pendiente para que un administrador pueda simular la confirmación.</p>
                    </div>
                    <p class="ct-field-error" id="pay-error" role="alert" <?= $errores ? '' : 'hidden' ?>><?= e(implode(' ', $errores)) ?></p>
                    <div class="form-check mt-4">
                        <input class="form-check-input" type="checkbox" id="terminos" name="accept_terms" value="1" required>
                        <label class="form-check-label small text-secondary-custom" for="terminos">Acepto los términos y condiciones de compra.</label>
                    </div>
                </div>
                <div class="col-12 col-lg-5">
                    <div class="step-card p-4 rounded-4 border border-secondary-subtle position-sticky" style="top: 90px;">
                        <div class="d-flex gap-3 mb-3">
                            <img src="<?= e(url('/' . ltrim($datos['image'], '/'))) ?>" alt="" class="rounded-3" style="width: 88px; height: 88px; object-fit: cover;">
                            <div>
                                <h2 class="h6 fw-bold text-white mb-1"><?= e($datos['name']) ?></h2>
                                <p class="small text-secondary-custom mb-0">
                                    <?= e(formatFecha($datos['date'], $datos['time'])) ?><br>
                                    <?= e($datos['venue']) ?>, <?= e($datos['city']) ?>
                                </p>
                            </div>
                        </div>
                        <ul class="list-unstyled small mb-3">
                            <?php if ($compradorNombre !== '') { ?><li class="d-flex justify-content-between mb-1"><span>A nombre de</span><strong><?= e($compradorNombre) ?></strong></li><?php } ?>
                            <?php if ($compradorUsuario !== '') { ?><li class="d-flex justify-content-between mb-1"><span>Usuario</span><strong><?= e($compradorUsuario) ?></strong></li><?php } ?>
                            <li class="d-flex justify-content-between mb-1"><span>Ubicación</span><strong><?= e($datos['type']) ?></strong></li>
                            <li class="d-flex justify-content-between mb-1"><span>Cantidad</span><strong><?= (int) $cantidad ?></strong></li>
                            <li class="d-flex justify-content-between"><span>Precio unitario</span><strong><?= e(formatPrecio($datos['price'])) ?></strong></li>
                        </ul>
                        <?php if ($butacas) { ?>
                            <p class="small text-secondary-custom mb-1">Butacas</p>
                            <div class="mb-3"><?php foreach ($butacas as $b) { ?><span class="ct-chip"><?= e(textoButaca($b)) ?></span><?php } ?></div>
                        <?php } else { ?>
                            <p class="small text-secondary-custom mb-3"><i class="bi bi-people me-1"></i>Ingreso libre, sin butaca asignada.</p>
                        <?php } ?>
                        <div class="d-flex justify-content-between align-items-center border-top border-secondary-subtle pt-3">
                            <span class="text-secondary-custom">Total</span>
                            <span class="ct-summary__total text-white"><?= e(formatPrecio($total)) ?></span>
                        </div>
                        <button type="submit" class="btn btn-violet-glow btn-lg w-100 rounded-pill mt-3 ct-checkout-submit">Confirmar compra <i class="bi bi-check-circle ms-2"></i></button>
                        <?php if ($modoDemo) { ?>
                            <p class="small text-warning mt-3 mb-0"><i class="bi bi-info-circle me-1"></i>Modo demo: no se realiza ningún cobro real.</p>
                        <?php } ?>
                    </div>
                </div>
            </div>
        </form>
        <script src="<?= e(url('/assets/js/checkout.js')) ?>"></script>
    <?php } ?>
    </div>
</main>
<?php if ($vista === 'listo' && $pagada && $qrEntries) { ?>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/qrcodejs/1.0.0/qrcode.min.js" defer></script>
    <script src="<?= e(url('/assets/js/ticket-qr.js')) ?>" defer></script>
<?php } ?>
<?php
$pageContent = ob_get_clean();
include __DIR__ . '/../_layouts/layout.php';