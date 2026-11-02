<?php
$listaEstados = [
    'pending' => ['Pago pendiente', 'warning'],
    'cancelled' => ['Compra cancelada', 'danger'],
    'paid' => ['Pagada', 'success'],
];
$estadoVenta = $entrada ? ($listaEstados[$entrada['sale_status']] ?? ['Estado desconocido', 'secondary']) : null;
$puedeIngresar = $entrada
    && $entrada['sale_status'] === 'paid'
    && $entrada['used_at'] === null;
ob_start();
?>

<main class="container-xl py-5 text-light">
    <div class="mx-auto" style="max-width: 640px;">
        <header class="mb-4">
            <p class="small text-uppercase text-violet-neon mb-2">Control de acceso</p>
            <h1 class="h3 fw-bold text-white">Validar entrada</h1>
            <p class="text-secondary-custom mb-0">
                Escaneá el QR con la cámara del celular. Antes de permitir el ingreso, comprobá los datos y tocá “Marcar como ingresada”.
            </p>
        </header>

        <?php if ($entryId === ''): ?>
            <section class="card bg-card-dark text-light border border-secondary-subtle rounded-4">
                <div class="card-body p-4">
                    <p class="text-secondary-custom">
                        Si no podés escanear el QR, pegá acá el identificador de la entrada.
                    </p>
                    <form method="GET" action="<?= e(url('/src/controllers/tickets/validate.php')) ?>">
                        <label class="form-label" for="entry-id">Identificador de entrada</label>
                        <input class="form-control mb-3" id="entry-id" name="id" type="text"
                               maxlength="36" required>
                        <button class="btn btn-violet-glow rounded-pill px-4" type="submit">Buscar entrada</button>
                    </form>
                </div>
            </section>
        <?php elseif (!$entrada): ?>
            <div class="alert alert-danger" role="alert">No encontramos esa entrada.</div>
            <a class="btn btn-outline-violet rounded-pill px-4"
               href="<?= e(url('/src/controllers/tickets/validate.php')) ?>">Volver</a>
        <?php else: ?>
            <section class="card bg-card-dark text-light border border-secondary-subtle rounded-4">
                <div class="card-body p-4">
                    <div class="d-flex flex-wrap justify-content-between align-items-start gap-3 mb-4">
                        <div>
                            <p class="small text-secondary-custom mb-1">Evento</p>
                            <h2 class="h4 text-white mb-0"><?= e($entrada['concert']) ?></h2>
                        </div>
                        <span class="badge text-bg-<?= e($estadoVenta[1]) ?>"><?= e($estadoVenta[0]) ?></span>
                    </div>

                    <dl class="row mb-4">
                        <dt class="col-sm-5 text-secondary-custom">A nombre de</dt>
                        <dd class="col-sm-7"><?= e(trim($entrada['first_name'] . ' ' . $entrada['last_name'])) ?></dd>
                        <dt class="col-sm-5 text-secondary-custom">Entrada</dt>
                        <dd class="col-sm-7">
                            <?= e($entrada['type']) ?> · N.º <?= (int) $entrada['entry_number'] ?>
                            <?php if (!empty($entrada['seat_code'])): ?>
                                · <?= e(textoButaca($entrada['seat_code'])) ?>
                            <?php endif; ?>
                        </dd>
                        <dt class="col-sm-5 text-secondary-custom">Fecha y lugar</dt>
                        <dd class="col-sm-7">
                            <?= e(formatFecha($entrada['date'])) ?><br>
                            <?= e($entrada['venue']) ?> · <?= e($entrada['city']) ?>
                        </dd>
                        <dt class="col-sm-5 text-secondary-custom">Pedido</dt>
                        <dd class="col-sm-7">#<?= e(strtoupper(substr($entrada['sale_id'], 0, 8))) ?></dd>
                    </dl>

                    <?php if ($entrada['used_at'] !== null): ?>
                        <div class="alert alert-secondary mb-0" role="status">
                            Esta entrada ya se utilizó el <?= e($entrada['used_at']) ?>. No puede volver a ingresar.
                        </div>
                    <?php elseif ($entrada['sale_status'] !== 'paid'): ?>
                        <div class="alert alert-warning mb-0" role="status">
                            Esta entrada no es válida porque el pago no está confirmado o la compra fue cancelada.
                        </div>
                    <?php elseif ($puedeIngresar): ?>
                        <form method="POST" action="<?= e(url('/src/controllers/tickets/validate.php?id=' . rawurlencode($entrada['id']))) ?>">
                            <input type="hidden" name="csrf_token" value="<?= e(csrfToken()) ?>">
                            <input type="hidden" name="id" value="<?= e($entrada['id']) ?>">
                            <button class="btn btn-violet-glow rounded-pill px-4" type="submit">
                                Marcar como ingresada
                            </button>
                        </form>
                    <?php endif; ?>
                </div>
            </section>
        <?php endif; ?>
    </div>
</main>

<?php
$pageContent = ob_get_clean();
include __DIR__ . '/../_layouts/layout.php';
