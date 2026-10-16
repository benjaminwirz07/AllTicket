<?php
require_once __DIR__ . '/../../config/bootstrap.php';

$loginUrl = '/src/views/auth/login.php';
$userId = $_SESSION['user']['id'] ?? ($_SESSION['user_id'] ?? null);

$compradorNombre = trim(
    ($_SESSION['user']['first_name'] ?? '') . ' ' . ($_SESSION['user']['last_name'] ?? '')
);
$compradorUsuario = $_SESSION['user']['usuario'] ?? '';

$modoDemo = true;
$transferAlias = 'ALLTICKET.PAGOS';
$transferTitular = 'AllTicket S.A.';

$metodos = [
    'mercadopago'   => ['nombre' => 'Mercado Pago', 'icono' => 'bi-wallet2', 'desc' => 'Pagá con tu dinero en cuenta, tarjetas guardadas o cuotas.', 'instant' => true],
    'tarjeta'       => ['nombre' => 'Tarjeta de crédito o débito', 'icono' => 'bi-credit-card-2-front', 'desc' => 'Visa, Mastercard y American Express.', 'instant' => true],
    'transferencia' => ['nombre' => 'Transferencia bancaria', 'icono' => 'bi-bank', 'desc' => 'Transferí desde tu home banking. Se acredita al confirmar el pago.', 'instant' => false],
    'efectivo'      => ['nombre' => 'Efectivo (Rapipago / Pago Fácil)', 'icono' => 'bi-cash-coin', 'desc' => 'Pagá en sucursal con tu número de pedido.', 'instant' => false],
];

$maxEntradas = 6;

if (!function_exists('esNumerada')) {
    function esNumerada($tipo) {
        return stripos($tipo, 'vip') !== false || stripos($tipo, 'platea') !== false;
    }
}
if (!function_exists('uuidv4')) {
    function uuidv4() {
        $d = random_bytes(16);
        $d[6] = chr((ord($d[6]) & 0x0f) | 0x40);
        $d[8] = chr((ord($d[8]) & 0x3f) | 0x80);
        return vsprintf('%s%s-%s-%s-%s-%s%s%s', str_split(bin2hex($d), 4));
    }
}
if (!function_exists('textoButaca')) {
    function textoButaca($codigo) {
        $p = explode('-', $codigo);
        return 'Fila ' . $p[0] . ' · Butaca ' . $p[1];
    }
}
// CAMBIO D: vence reservas held (24 h) y ventas pendientes; devuelve el stock
if (!function_exists('vencerReservas')) {
    function vencerReservas(PDO $pdo) {
        $propia = !$pdo->inTransaction();
        if ($propia) { $pdo->beginTransaction(); }
        try {
            $ids = $pdo->query(
                "SELECT s.id FROM sales s
                  WHERE s.status = 'pending'
                    AND (s.sale_date <= NOW() - INTERVAL 24 HOUR
                         OR EXISTS (SELECT 1 FROM seat_reservations r
                                     WHERE r.sale_id = s.id AND r.status = 'held' AND r.expires_at <= NOW()))"
            )->fetchAll(PDO::FETCH_COLUMN);

            $cancelar = $pdo->prepare("UPDATE sales SET status = 'cancelled' WHERE id = ? AND status = 'pending'");
            $devolver = $pdo->prepare(
                "UPDATE tickets t JOIN sale_details d ON d.ticket_id = t.id
                    SET t.available = LEAST(t.stock, t.available + d.quantity)
                  WHERE d.sale_id = ?"
            );
            $liberar = $pdo->prepare("UPDATE seat_reservations SET status = 'expired' WHERE sale_id = ? AND status = 'held'");

            foreach ($ids as $id) {
                $cancelar->execute([$id]);
                if ($cancelar->rowCount() === 1) {
                    $devolver->execute([$id]);
                    $liberar->execute([$id]);
                }
            }

            $pdo->exec("UPDATE seat_reservations SET status = 'expired' WHERE status = 'held' AND expires_at <= NOW()");

            if ($propia) { $pdo->commit(); }
        } catch (PDOException $e) {
            if ($propia && $pdo->inTransaction()) { $pdo->rollBack(); }
            throw $e;
        }
    }
}

if (empty($_SESSION['csrf'])) {
    $_SESSION['csrf'] = bin2hex(random_bytes(16));
}
$csrf = $_SESSION['csrf'];

$vista = 'resumen';
$errores = [];
$datos = null;
$butacas = [];
$cantidad = 0;
$total = 0;
$venta = null;
$metodo = $_SERVER['REQUEST_METHOD'];

if ($metodo === 'GET' && isset($_GET['ok'])) {
    if (!$userId) {
        $vista = 'login';
    } else {
        $stmt = $pdo->prepare(
            "SELECT s.id, s.total, s.status, s.payment_method, s.sale_date,
                    d.quantity, d.price, t.type, t.id AS ticket_id,
                    c.id AS concert_id, c.name, c.image, c.date, c.time,
                    v.name AS venue, v.city
               FROM sales s
               JOIN sale_details d ON d.sale_id = s.id
               JOIN tickets t ON t.id = d.ticket_id
               JOIN concerts c ON c.id = t.concert_id
               JOIN venues v ON v.id = c.venue_id
              WHERE s.id = :id AND s.user_id = :u LIMIT 1"
        );
        $stmt->execute([':id' => $_GET['ok'], ':u' => $userId]);
        $venta = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$venta) {
            $errores[] = 'No encontramos esa compra.';
            $vista = 'error';
        } else {
            $vista = 'listo';
            $stmt = $pdo->prepare("SELECT seat_code FROM seat_reservations WHERE sale_id = :id ORDER BY created_at, seat_code");
            $stmt->execute([':id' => $venta['id']]);
            $butacas = $stmt->fetchAll(PDO::FETCH_COLUMN);
        }
    }
} elseif ($metodo === 'POST') {
    $confirma = isset($_POST['confirmar']);
    $concertId = trim($_POST['concert_id'] ?? '');
    $ticketId = trim($_POST['ticket_id'] ?? '');
    $cantidad = (int) ($_POST['quantity'] ?? 0);
    $metodoSel = $_POST['payment_method'] ?? '';

    $butacas = [];
    foreach (explode(',', $_POST['seats'] ?? '') as $b) {
        $b = trim($b);
        if (preg_match('/^\d{1,3}-\d{1,3}$/', $b)) {
            $butacas[$b] = $b;
        }
    }
    $butacas = array_values($butacas);

    if (!$userId) {
        $vista = 'login';
    } else {
        // CAMBIO D: liberar reservas vencidas antes de consultar disponibilidad
        try {
            vencerReservas($pdo);
        } catch (PDOException $e) {
            error_log('Error al vencer reservas: ' . $e->getMessage());
        }

        $stmt = $pdo->prepare(
            "SELECT t.id AS ticket_id, t.type, t.price, t.available, t.stock,
                    c.id AS concert_id, c.name, c.image, c.date, c.time, c.status,
                    v.name AS venue, v.city
               FROM tickets t
               JOIN concerts c ON c.id = t.concert_id
               JOIN venues v ON v.id = c.venue_id
              WHERE t.id = :t AND c.id = :c"
        );
        $stmt->execute([':t' => $ticketId, ':c' => $concertId]);
        $datos = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$datos) {
            $errores[] = 'No encontramos esa entrada.';
        } else {
            $numerada = esNumerada($datos['type']);
            if ($numerada) {
                $cantidad = count($butacas);
            } else {
                $butacas = [];
            }

            if ($datos['status'] !== 'active' || $datos['date'] < date('Y-m-d')) {
                $errores[] = 'Este recital ya no está disponible para la compra.';
            }
            if ($cantidad < 1) {
                $errores[] = $numerada ? 'Elegí al menos una butaca.' : 'Elegí al menos una entrada.';
            }
            if ($cantidad > $maxEntradas) {
                $errores[] = 'Podés comprar hasta ' . $maxEntradas . ' entradas por vez.';
            }
            if ($cantidad > (int) $datos['available']) {
                $errores[] = 'No quedan tantas entradas disponibles.';
            }

            if ($numerada && $butacas && !$errores) {
                $marcas = implode(',', array_fill(0, count($butacas), '?'));
                // CAMBIO E: solo cuentan las butacas held o confirmed
                $q = $pdo->prepare("SELECT seat_code FROM seat_reservations WHERE concert_id = ? AND status IN ('held', 'confirmed') AND seat_code IN ($marcas)");
                $q->execute(array_merge([$concertId], $butacas));
                $tomadas = $q->fetchAll(PDO::FETCH_COLUMN);
                if ($tomadas) {
                    $errores[] = 'Estas butacas ya no están disponibles: ' . implode(', ', array_map('textoButaca', $tomadas)) . '.';
                }
            }
            $total = $cantidad * (float) $datos['price'];
        }

        if ($errores) {
            $vista = 'error';
        } elseif ($confirma) {
            if (!hash_equals($csrf, $_POST['csrf'] ?? '')) {
                $errores[] = 'La sesión expiró. Volvé a intentarlo.';
                $vista = 'error';
            } elseif (!isset($metodos[$metodoSel])) {
                $errores[] = 'Elegí un medio de pago.';
                $vista = 'resumen';
            } else {
                try {
                    $pdo->beginTransaction();
                    vencerReservas($pdo); // CAMBIO D: también dentro de la transacción
                    $lock = $pdo->prepare("SELECT available FROM tickets WHERE id = :id FOR UPDATE");
                    $lock->execute([':id' => $ticketId]);
                    $disp = (int) $lock->fetchColumn();
                    if ($disp < $cantidad) {
                        throw new RuntimeException('No quedan tantas entradas disponibles.');
                    }

                    $pagadoYa = $metodos[$metodoSel]['instant'] && $modoDemo;
                    $estado = $pagadoYa ? 'paid' : 'pending';
                    $ventaId = uuidv4();

                    $pdo->prepare("INSERT INTO sales (id, user_id, total, status, payment_method) VALUES (?, ?, ?, ?, ?)")
                        ->execute([$ventaId, $userId, $total, $estado, $metodoSel]);
                    $pdo->prepare("INSERT INTO sale_details (id, sale_id, ticket_id, quantity, price) VALUES (?, ?, ?, ?, ?)")
                        ->execute([uuidv4(), $ventaId, $ticketId, $cantidad, $datos['price']]);

                    // CAMBIO F: pagada = confirmed; pendiente = held con vencimiento a 24 h
                    $estadoReserva = $pagadoYa ? 'confirmed' : 'held';
                    $expiraSql = $pagadoYa ? 'NULL' : 'NOW() + INTERVAL 24 HOUR';
                    $insSeat = $pdo->prepare("INSERT INTO seat_reservations (id, concert_id, ticket_id, sale_id, seat_code, status, expires_at) VALUES (?, ?, ?, ?, ?, ?, $expiraSql)");
                    foreach ($butacas as $codigo) {
                        $insSeat->execute([uuidv4(), $concertId, $ticketId, $ventaId, $codigo, $estadoReserva]);
                    }

                    // CAMBIO C: el descuento verifica el stock por sí mismo
                    $upd = $pdo->prepare("UPDATE tickets SET available = available - ? WHERE id = ? AND available >= ?");
                    $upd->execute([$cantidad, $ticketId, $cantidad]);
                    if ($upd->rowCount() !== 1) {
                        throw new RuntimeException('No quedan tantas entradas disponibles.');
                    }

                    $pdo->commit();
                    unset($_SESSION['csrf']); // CAMBIO A: token consumido, un reenvío ya no es válido
                    header('Location: /src/views/concerts/checkout.php?ok=' . urlencode($ventaId));
                    exit;
                } catch (PDOException $e) {
                    error_log('Error en checkout: ' . $e->getMessage()); // CAMBIO B: deja rastro en el log
                    if ($pdo->inTransaction()) { $pdo->rollBack(); }
                    $errores[] = ($e->getCode() == '23000')
                        ? 'Alguna de las butacas se ocupó recién. Volvé y elegí otras.'
                        : 'No pudimos registrar la compra. Probá de nuevo.';
                    $vista = 'error';
                } catch (RuntimeException $e) {
                    if ($pdo->inTransaction()) { $pdo->rollBack(); }
                    $errores[] = $e->getMessage();
                    $vista = 'error';
                }
            }
        }
    }
} else {
    header('Location: /src/views/index.php#eventos');
    exit;
}

ob_start();
?>
<link rel="stylesheet" href="/assets/css/index.css">
<link rel="stylesheet" href="/assets/css/concert.css">
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
            <a href="<?= !empty($datos['concert_id']) ? '/src/views/concerts/show.php?id=' . urlencode($datos['concert_id']) . '#entradas' : '/src/views/index.php#eventos' ?>"
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
            </div>
            <?php if (!$pagada) { ?>
                <div class="ct-panel">
                    <?php if ($venta['payment_method'] === 'transferencia') { ?>
                        <p class="mb-1 fw-semibold text-white">Transferí <?= e(formatPrecio($venta['total'])) ?> a:</p>
                        <p class="small text-secondary-custom mb-0">Alias: <strong class="text-white"><?= e($transferAlias) ?></strong><br>Titular: <?= e($transferTitular) ?><br>Indicá el número de pedido en el concepto.</p>
                    <?php } elseif ($venta['payment_method'] === 'efectivo') { ?>
                        <p class="mb-0 small text-secondary-custom">Pagá en Rapipago o Pago Fácil indicando tu número de pedido.</p>
                    <?php } else { ?>
                        <p class="mb-0 small text-secondary-custom">Estamos esperando la confirmación de tu pago.</p>
                    <?php } ?>
                </div>
            <?php } ?>
            <div class="text-center mt-4">
                <a href="/src/views/index.php#eventos" class="btn btn-outline-violet rounded-pill px-4">Seguir viendo recitales</a>
            </div>
        </div>
    <?php } else { ?>
        <h1 class="h3 fw-bold text-white mb-1">Finalizar compra</h1>
        <p class="text-secondary-custom mb-4">Revisá tu pedido y elegí cómo querés pagar.</p>
        <form action="/src/views/concerts/checkout.php" method="POST" id="form-pago" novalidate>
            <input type="hidden" name="confirmar" value="1">
            <input type="hidden" name="csrf" value="<?= e($csrf) ?>">
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
                    <div class="ct-panel" data-panel="mercadopago" hidden>
                        <p class="small text-secondary-custom mb-0">Al confirmar, vas a continuar el pago en Mercado Pago y volvés a AllTicket cuando termines.</p>
                    </div>
                    <div class="ct-panel" data-panel="tarjeta" hidden>
                        <div class="ct-card-preview" aria-hidden="true">
                            <div class="small text-uppercase" id="cc-brand">Tarjeta</div>
                            <div class="ct-card-preview__num" id="cc-prev-num">•••• •••• •••• ••••</div>
                            <div class="d-flex justify-content-between small">
                                <span id="cc-prev-name">NOMBRE APELLIDO</span>
                                <span id="cc-prev-exp">MM/AA</span>
                            </div>
                        </div>
                        <div class="row g-3">
                            <div class="col-12">
                                <label for="cc-num" class="form-label small text-secondary-custom">Número de tarjeta</label>
                                <input id="cc-num" type="text" class="form-control custom-input" inputmode="numeric" autocomplete="cc-number" maxlength="23" placeholder="0000 0000 0000 0000">
                            </div>
                            <div class="col-12">
                                <label for="cc-name" class="form-label small text-secondary-custom">Nombre como figura en la tarjeta</label>
                                <input id="cc-name" type="text" class="form-control custom-input" autocomplete="cc-name" maxlength="40">
                            </div>
                            <div class="col-6">
                                <label for="cc-exp" class="form-label small text-secondary-custom">Vencimiento</label>
                                <input id="cc-exp" type="text" class="form-control custom-input" inputmode="numeric" autocomplete="cc-exp" maxlength="5" placeholder="MM/AA">
                            </div>
                            <div class="col-6">
                                <label for="cc-cvv" class="form-label small text-secondary-custom">Código de seguridad</label>
                                <input id="cc-cvv" type="text" class="form-control custom-input" inputmode="numeric" autocomplete="cc-csc" maxlength="4" placeholder="CVV">
                            </div>
                            <div class="col-6">
                                <label for="cc-dni" class="form-label small text-secondary-custom">DNI del titular</label>
                                <input id="cc-dni" type="text" class="form-control custom-input" inputmode="numeric" maxlength="9">
                            </div>
                            <div class="col-6">
                                <label for="cc-cuotas" class="form-label small text-secondary-custom">Cuotas</label>
                                <select id="cc-cuotas" class="form-select custom-input">
                                    <option>1 cuota</option><option>3 cuotas</option><option>6 cuotas</option><option>12 cuotas</option>
                                </select>
                            </div>
                        </div>
                        <p class="small text-secondary-custom mt-3 mb-0"><i class="bi bi-shield-lock me-1"></i>Por seguridad, los datos de tu tarjeta no se guardan en nuestra base.</p>
                    </div>
                    <div class="ct-panel" data-panel="transferencia" hidden>
                        <p class="small text-secondary-custom mb-2">Al confirmar te mostramos los datos para transferir:</p>
                        <p class="small mb-0">Alias: <strong class="text-white"><?= e($transferAlias) ?></strong><br>Titular: <?= e($transferTitular) ?></p>
                    </div>
                    <div class="ct-panel" data-panel="efectivo" hidden>
                        <p class="small text-secondary-custom mb-0">Al confirmar generamos tu número de pedido para que pagues en Rapipago o Pago Fácil.</p>
                    </div>
                    <p class="ct-field-error" id="pay-error" role="alert" hidden></p>
                    <div class="form-check mt-4">
                        <input class="form-check-input" type="checkbox" id="terminos">
                        <label class="form-check-label small text-secondary-custom" for="terminos">Acepto los términos y condiciones de compra.</label>
                    </div>
                </div>
                <div class="col-12 col-lg-5">
                    <div class="step-card p-4 rounded-4 border border-secondary-subtle position-sticky" style="top: 90px;">
                        <div class="d-flex gap-3 mb-3">
                            <img src="/<?= e($datos['image']) ?>" alt="" class="rounded-3" style="width: 88px; height: 88px; object-fit: cover;">
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
                        <button type="submit" class="btn btn-violet-glow btn-lg w-100 rounded-pill mt-3">Confirmar y pagar <i class="bi bi-lock ms-2"></i></button>
                        <?php if ($modoDemo) { ?>
                            <p class="small text-warning mt-3 mb-0"><i class="bi bi-info-circle me-1"></i>Modo demo: no se realiza ningún cobro real.</p>
                        <?php } ?>
                    </div>
                </div>
            </div>
        </form>
        <script src="/assets/js/checkout.js"></script>
    <?php } ?>
    </div>
</main>
<?php
$pageContent = ob_get_clean();
include __DIR__ . '/../_layouts/layout.php';