<?php
require_once __DIR__ . '/../../config/bootstrap.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Pagina de login de tu proyecto (la misma que usa checkout.php)
$loginUrl = '/src/views/auth/login.php';

// Mismo criterio que checkout.php para saber si hay alguien logueado
$userId = $_SESSION['user']['id'] ?? ($_SESSION['user_id'] ?? null);
$logueado = ($userId != null);

date_default_timezone_set('America/Argentina/Buenos_Aires');

// ============================================================
// 1. LEEMOS EL ID DEL RECITAL (viene en la URL: ?id=...)
// ============================================================

$id = "";
if (isset($_GET['id'])) {
    $id = trim($_GET['id']);
}

$concert = null;
$entradas = [];


// ============================================================
// 2. FUNCIONES
// ============================================================

// Dice si la entrada es VIP
function esVip($tipo)
{
    if (stripos($tipo, 'vip') !== false) {
        return true;
    }
    return false;
}

// Dice si la entrada tiene butaca numerada (VIP o Platea)
function esNumerada($tipo)
{
    if (stripos($tipo, 'vip') !== false) {
        return true;
    }
    if (stripos($tipo, 'platea') !== false) {
        return true;
    }
    return false;
}

// Cuenta cuantos dias faltan para el show
function diasRestantes($fecha)
{
    $hoy = strtotime(date('Y-m-d'));
    $dia = strtotime($fecha);
    $dias = ($dia - $hoy) / 86400;
    return round($dias);
}


// ============================================================
// 3. TRAEMOS EL RECITAL DE LA BASE DE DATOS
// ============================================================

try {

    if ($id != "") {

        $stmt = $pdo->prepare(
            "SELECT
                c.id, c.name, c.description, c.image, c.genre,
                c.date, c.time, c.status,
                a.name        AS artist,
                a.description AS artist_description,
                v.name        AS venue,
                v.address, v.city, v.province, v.capacity
             FROM concerts c
             JOIN artists a ON c.artist_id = a.id
             JOIN venues  v ON c.venue_id  = v.id
             WHERE c.id = :id"
        );
        $stmt->execute([":id" => $id]);
        $concert = $stmt->fetch(PDO::FETCH_ASSOC);

        // Las entradas de este recital (de la mas barata a la mas cara)
        if ($concert) {
            $stmtTickets = $pdo->prepare(
                "SELECT id, type, price, stock, available
                   FROM tickets
                  WHERE concert_id = :id
                  ORDER BY price ASC"
            );
            $stmtTickets->execute([":id" => $id]);
            $entradas = $stmtTickets->fetchAll(PDO::FETCH_ASSOC);
        }
    }

} catch (PDOException $e) {
    $concert = null;
    $entradas = [];
}


// ============================================================
// 4. BUTACAS QUE YA SE VENDIERON
//    (si todavia no corriste la migracion, la tabla no existe y seguimos igual)
// ============================================================

if ($concert) {

    $butacasVendidas = [];

    try {
        $stmtSeats = $pdo->prepare("SELECT ticket_id, seat_code FROM seat_reservations WHERE concert_id = :id");
        $stmtSeats->execute([":id" => $id]);
        $butacasVendidas = $stmtSeats->fetchAll(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        $butacasVendidas = [];
    }

    // A cada entrada le guardamos su lista de butacas ocupadas: "1-2,1-3,"
    foreach ($entradas as $numero => $entrada) {

        $lista = "";

        foreach ($butacasVendidas as $butaca) {
            if ($butaca['ticket_id'] == $entrada['id']) {
                $lista = $lista . $butaca['seat_code'] . ",";
            }
        }

        $entradas[$numero]['ocupadas'] = $lista;
    }
}


// ============================================================
// 5. DATOS PARA LA PAGINA
// ============================================================

$dias = 0;
$sePuedeComprar = false;
$fechaCompleta = "";

// Si no esta logueado, guardamos esta pagina para volver despues del login
if (!$logueado) {
    $_SESSION['volver_a'] = $_SERVER['REQUEST_URI'] . '#entradas';
}
$marquesina = [];

if ($concert) {

    $dias = diasRestantes($concert['date']);

    if ($concert['status'] == 'active' && $dias >= 0) {
        $sePuedeComprar = true;
    }

    // Fecha y hora juntas, para la cuenta regresiva
    $fechaCompleta = $concert['date'] . "T" . substr($concert['time'], 0, 5) . ":00-03:00";

    // Textos de la marquesina
    $marquesina = [
        $concert['artist'],
        formatFecha($concert['date'], $concert['time']),
        $concert['venue'],
        "vivi un show único",
        "entreas oficiales, sin reventa",
        "¡no te lo pierdas!",
    ];

    // Link para agregar el show a Google Calendar (dura 3 horas)
    $inicio = strtotime($concert['date'] . ' ' . $concert['time']);
    $fin = $inicio + 3 * 3600;
    $linkCalendario = 'https://calendar.google.com/calendar/render?action=TEMPLATE'
        . '&text=' . urlencode($concert['name'])
        . '&dates=' . date('Ymd\THis', $inicio) . '/' . date('Ymd\THis', $fin)
        . '&ctz=America/Argentina/Buenos_Aires'
        . '&location=' . urlencode($concert['venue'] . ', ' . $concert['address'] . ', ' . $concert['city']);

    // Link para compartir por WhatsApp
    $https = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off');
    $urlPagina = ($https ? 'https' : 'http') . '://' . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI'];
    $linkWhatsapp = 'https://wa.me/?text=' . urlencode(
        $concert['name'] . ' - ' . formatFecha($concert['date'], $concert['time']) . ' en ' . $concert['venue'] . ' ' . $urlPagina
    );
}

ob_start();
?>

<link rel="stylesheet" href="/assets/css/index.css">
<link rel="stylesheet" href="/assets/css/concert.css">
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/leaflet/1.9.4/leaflet.min.css">

<main class="bg-main-dark text-light min-vh-100 position-relative z-1 ct-page">

    <?php if (!$concert) { ?>

        <!-- RECITAL NO ENCONTRADO -->
        <section class="py-5">
            <div class="container-xl text-center py-5">
                <i class="bi bi-emoji-frown fs-1 text-violet-neon"></i>
                <h1 class="h3 text-white fw-bold mt-3">No encontramos ese recital</h1>
                <p class="text-secondary-custom">Puede que ya no esté disponible.</p>
                <a href="/src/views/index.php#eventos" class="btn btn-outline-violet rounded-pill px-4">
                    Ver todos los recitales
                </a>
            </div>
        </section>

    <?php } else { ?>

        <!-- =================================================
             HERO: ocupa toda la pantalla
        ================================================== -->
        <section class="ct-hero" id="inicio">

            <img class="ct-hero__bg" src="/<?= e($concert['image']) ?>" alt="" aria-hidden="true">
            <div class="ct-hero__shade" aria-hidden="true"></div>

            <div class="container-xl ct-hero__inner">

                <div class="ct-hero__text">

                <a href="/src/views/index.php#eventos" class="ct-hero__back">
                    <i class="bi bi-arrow-left me-1"></i>Volver a recitales
                </a>

                <?php if (!empty($concert['genre'])) { ?>
                    <span class="badge bg-violet-neon rounded-pill mb-3"><?= e($concert['genre']) ?></span>
                <?php } ?>

                <h1 class="ct-hero__title"><?= e($concert['name']) ?></h1>
                <p class="ct-hero__artist"><?= e($concert['artist']) ?></p>

                <ul class="ct-hero__facts list-unstyled">
                    <li><i class="bi bi-calendar-event"></i><?= e(formatFecha($concert['date'], $concert['time'])) ?></li>
                    <li><i class="bi bi-geo-alt"></i><?= e($concert['venue']) ?>, <?= e($concert['city']) ?></li>
                    <li><i class="bi bi-people"></i><?= e(number_format((int)$concert['capacity'], 0, ',', '.')) ?> personas</li>
                </ul>

                <!-- CUENTA REGRESIVA -->
                <?php if ($dias >= 0) { ?>
                    <p class="ct-hero__eyebrow">
                        <?php if ($dias == 0) { ?>
                            ¡El show es hoy!
                        <?php } elseif ($dias == 1) { ?>
                            ¡El show es mañana!
                        <?php } else { ?>
                            Faltan <strong><?= $dias ?> días</strong> para el show
                        <?php } ?>
                    </p>

                    <div class="ct-countdown" id="countdown" aria-live="off">
                        <div class="ct-countdown__box"><span class="ct-countdown__num" data-cd="d"><?= $dias ?></span><span class="ct-countdown__lbl">días</span></div>
                        <div class="ct-countdown__box"><span class="ct-countdown__num" data-cd="h">--</span><span class="ct-countdown__lbl">horas</span></div>
                        <div class="ct-countdown__box"><span class="ct-countdown__num" data-cd="m">--</span><span class="ct-countdown__lbl">min</span></div>
                        <div class="ct-countdown__box"><span class="ct-countdown__num" data-cd="s">--</span><span class="ct-countdown__lbl">seg</span></div>
                    </div>
                <?php } else { ?>
                    <div class="alert alert-warning d-inline-block">Este recital ya no está disponible para la compra.</div>
                <?php } ?>

                <?php if ($concert['status'] != 'active' && $dias >= 0) { ?>
                    <div class="alert alert-warning d-inline-block mt-3">Este recital no está a la venta.</div>
                <?php } ?>

                <div class="d-flex flex-wrap gap-3 mt-4">
                    <a href="#entradas" class="btn btn-violet-glow btn-lg px-5 rounded-pill">
                        Elegir entradas <i class="bi bi-arrow-down ms-2"></i>
                    </a>
                    <a href="#como-llegar" class="btn btn-outline-light btn-lg px-4 rounded-pill">
                        <i class="bi bi-geo-alt me-1"></i>Cómo llegar
                    </a>
                </div>

                <?php if ($dias >= 0) { ?>
                    <div class="ct-hero__links">
                        <a href="<?= e($linkCalendario) ?>" target="_blank" rel="noopener">
                            <i class="bi bi-calendar-plus"></i>Agregar al calendario
                        </a>
                        <a href="<?= e($linkWhatsapp) ?>" target="_blank" rel="noopener">
                            <i class="bi bi-whatsapp"></i>Compartir
                        </a>
                    </div>
                <?php } ?>

                </div>

                <img class="ct-hero__photo" src="/<?= e($concert['image']) ?>" alt="<?= e($concert['artist']) ?>">

            </div>
        </section>


        <!-- MARQUESINA -->
        <div class="ct-marquee" aria-hidden="true">
            <div class="ct-marquee__track">
                <?php for ($vuelta = 0; $vuelta < 2; $vuelta++) { ?>
                    <?php foreach ($marquesina as $texto) { ?>
                        <span class="ct-marquee__item"><?= e($texto) ?></span>
                    <?php } ?>
                <?php } ?>
            </div>
        </div>


        <!-- MENÚ DE SECCIONES -->
        <nav class="ct-subnav" aria-label="Secciones del recital">
            <div class="container-xl ct-subnav__inner">
                <a href="#info">Info</a>
                <a href="#entradas">Entradas</a>
                <a href="#como-llegar">Cómo llegar</a>
                <a href="#faq">Preguntas</a>
            </div>
        </nav>


        <!-- =================================================
             QUÉ VA A PASAR EN EL SHOW
        ================================================== -->
        <section id="info" class="py-5">
            <div class="container-xl">
                <div class="row g-5">

                    <div class="col-12 col-lg-7">
                        <h2 class="h4 fw-bold text-white mb-3">Sobre el show</h2>

                        <?php if (!empty($concert['description'])) { ?>
                            <p class="text-secondary-custom" style="white-space: pre-line;"><?= e($concert['description']) ?></p>
                        <?php } else { ?>
                            <p class="text-secondary-custom">Pronto vamos a sumar más detalles de este show.</p>
                        <?php } ?>

                        <?php if (!empty($concert['artist_description'])) { ?>
                            <h2 class="h4 fw-bold text-white mt-5 mb-3">Sobre <?= e($concert['artist']) ?></h2>
                            <p class="text-secondary-custom" style="white-space: pre-line;"><?= e($concert['artist_description']) ?></p>
                        <?php } ?>
                    </div>

                    <div class="col-12 col-lg-5">
                        <div class="step-card p-4 rounded-4 border border-secondary-subtle">
                            <h2 class="h5 fw-bold text-white mb-3">Información útil</h2>
                            <ul class="list-unstyled small text-secondary-custom mb-0">
                                <li class="mb-2"><i class="bi bi-qr-code me-2 text-violet-neon"></i>Tu entrada llega con código QR a tu celular.</li>
                                <li class="mb-2"><i class="bi bi-patch-check me-2 text-violet-neon"></i>Entradas oficiales, sin reventa.</li>
                                <li class="mb-2"><i class="bi bi-clock me-2 text-violet-neon"></i>Inicio del show: <?= e(substr($concert['time'], 0, 5)) ?> hs.</li>
                                <li><i class="bi bi-headset me-2 text-violet-neon"></i>Soporte: soporte@allticket.com</li>
                            </ul>
                        </div>
                    </div>

                </div>
            </div>
        </section>


        <!-- =================================================
             ELEGIR UBICACIÓN
        ================================================== -->
        <section id="entradas" class="py-5 border-top border-dark-subtle">
            <div class="container-xl">

                <h2 class="h4 fw-bold text-white mb-1">Elegí tu ubicación</h2>
                <p class="text-secondary-custom mb-4">Seleccioná el tipo de entrada. En las numeradas elegís tu butaca.</p>

                <?php if (empty($entradas)) { ?>

                    <p class="text-secondary-custom">Todavía no hay entradas cargadas para este recital.</p>

                <?php } else { ?>

                    <form action="/src/views/concerts/checkout.php" method="POST" id="form-compra" novalidate>

                        <input type="hidden" name="concert_id" value="<?= e($concert['id']) ?>">
                        <input type="hidden" name="quantity" id="f-qty" value="1">
                        <input type="hidden" name="seats" id="f-seats" value="">

                        <div class="row g-4 align-items-start">

                            <!-- COLUMNA IZQUIERDA: entradas + butacas o cantidad -->
                            <div class="col-12 col-lg-8">

                                <!-- Tipos de entrada (lista vertical) -->
                                <div class="d-grid gap-3 mb-4" role="radiogroup" aria-label="Tipo de entrada">

                                    <?php foreach ($entradas as $i => $entrada) { ?>

                                        <?php
                                        $agotada   = ((int)$entrada['available'] <= 0);
                                        $vip       = esVip($entrada['type']);
                                        $numerada  = esNumerada($entrada['type']);
                                        $idInput   = "entrada-" . $i;

                                        // Porcentaje de entradas que quedan (para la barrita)
                                        $stockTotal = (int)$entrada['stock'];
                                        $porcentaje = 0;
                                        if ($stockTotal > 0) {
                                            $porcentaje = round(((int)$entrada['available'] / $stockTotal) * 100);
                                        }
                                        ?>

                                        <div>

                                            <input type="radio" class="btn-check" name="ticket_id"
                                                id="<?= $idInput ?>" value="<?= e($entrada['id']) ?>"
                                                data-tipo="<?= e($entrada['type']) ?>"
                                                data-precio="<?= e($entrada['price']) ?>"
                                                data-disponibles="<?= (int)$entrada['available'] ?>"
                                                data-stock="<?= (int)$entrada['stock'] ?>"
                                                data-numerada="<?php if ($numerada) { echo "si"; } else { echo "no"; } ?>"
                                                data-ocupadas="<?= e($entrada['ocupadas']) ?>"
                                                <?php if ($agotada || !$sePuedeComprar) { echo "disabled"; } ?>>

                                            <label for="<?= $idInput ?>" class="ct-ticket">

                                                <div class="d-flex justify-content-between align-items-center gap-3">

                                                    <div>
                                                        <h3 class="h5 fw-bold text-white mb-1">
                                                            <?php if ($vip) { ?><i class="bi bi-star-fill text-warning me-1"></i><?php } ?>
                                                            <?= e($entrada['type']) ?>
                                                            <?php if ($agotada) { ?>
                                                                <span class="badge text-bg-secondary ms-1">Agotada</span>
                                                            <?php } elseif ((int)$entrada['available'] <= 20) { ?>
                                                                <span class="badge text-bg-danger ms-1">¡Últimas!</span>
                                                            <?php } ?>
                                                        </h3>

                                                        <p class="small text-secondary-custom mb-0">
                                                            <?= $agotada ? "Sin disponibilidad" : (int)$entrada['available'] . " disponibles" ?>
                                                        </p>

                                                        <div class="ct-meter<?php if ($porcentaje <= 20) { echo " is-low"; } ?>" aria-hidden="true">
                                                            <span style="width: <?= (int)$porcentaje ?>%"></span>
                                                        </div>

                                                        <span class="ct-ticket__tag">
                                                            <?php if ($numerada) { ?>
                                                                <i class="bi bi-grid-3x3-gap"></i> Butaca numerada · elegís tu asiento
                                                            <?php } else { ?>
                                                                <i class="bi bi-people"></i> Ingreso libre · sin butaca asignada
                                                            <?php } ?>
                                                        </span>
                                                    </div>

                                                    <p class="fs-4 fw-bold text-white mb-0 text-nowrap"><?= e(formatPrecio($entrada['price'])) ?></p>

                                                </div>

                                            </label>
                                        </div>

                                    <?php } ?>

                                </div>


                                <!-- Panel de BUTACAS (solo si la entrada es numerada) -->
                                <div id="panel-seats" class="ct-seatbox" hidden>

                                    <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3">
                                        <h3 class="h6 text-white fw-bold mb-0">
                                            <i class="bi bi-grid-3x3-gap me-1 text-violet-neon"></i>
                                            Elegí tus butacas <span class="text-secondary-custom fw-normal">(hasta 6)</span>
                                        </h3>

                                        <div class="ct-legend">
                                            <span><i style="background:rgba(255,255,255,.16)"></i>Libre</span>
                                            <span><i style="background:linear-gradient(135deg,#a855f7,#22d3ee)"></i>Tu selección</span>
                                            <span><i style="background:#2a2a38"></i>Ocupada</span>
                                        </div>
                                    </div>

                                    <div class="ct-stage">ESCENARIO</div>
                                    <div class="ct-seatscroll" id="seat-grid" role="group" aria-label="Mapa de butacas"></div>

                                    <div class="mt-3" id="seat-chips" aria-live="polite"></div>
                                </div>


                                <!-- Panel de CAMPO (entradas sin butaca) -->
                                <div id="panel-field" class="ct-field" hidden>
                                    <div class="d-flex flex-wrap align-items-center justify-content-between gap-3">
                                        <div>
                                            <h3 class="h6 text-white fw-bold mb-1">
                                                <i class="bi bi-people me-1 text-violet-neon"></i>Ingreso libre
                                            </h3>
                                            <p class="small text-secondary-custom mb-0">
                                                Esta ubicación no tiene butaca asignada: ubicate donde prefieras dentro del sector.
                                            </p>
                                        </div>

                                        <div>
                                            <label class="small text-secondary-custom d-block mb-1">Cantidad de entradas</label>
                                            <div class="ct-stepper">
                                                <button type="button" id="qty-minus" aria-label="Menos">−</button>
                                                <output id="qty-out">1</output>
                                                <button type="button" id="qty-plus" aria-label="Más">+</button>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                            </div>


                            <!-- COLUMNA DERECHA: resumen + botón -->
                            <div class="col-12 col-lg-4">
                                <div class="ct-summary">

                                    <h3 class="h6 text-white fw-bold mb-3">
                                        <i class="bi bi-receipt me-1 text-violet-neon"></i>Tu compra
                                    </h3>

                                    <div class="small text-secondary-custom" id="sum-text">Elegí un tipo de entrada para continuar.</div>

                                    <div class="d-flex justify-content-between align-items-center border-top border-secondary-subtle pt-3 mt-3">
                                        <span class="text-secondary-custom">Total</span>
                                        <span class="ct-summary__total text-white" id="sum-total">$ 0</span>
                                    </div>

                                    <?php if (!$logueado) { ?>
                                        <p class="small text-warning mt-3 mb-0">
                                            <i class="bi bi-person-lock me-1"></i>Podés armar tu selección, pero para pagar necesitás
                                            <a href="<?= e($loginUrl) ?>" class="text-warning fw-semibold">iniciar sesión</a>.
                                        </p>
                                    <?php } ?>

                                    <button type="submit" id="btn-buy" class="btn btn-violet-glow btn-lg w-100 rounded-pill mt-3" disabled>
                                        Continuar con el pago <i class="bi bi-arrow-right ms-2"></i>
                                    </button>

                                    <p class="text-danger small mt-2 mb-0" id="form-error" role="alert" hidden></p>

                                </div>
                            </div>

                        </div>

                    </form>

                <?php } ?>

            </div>
        </section>


        <!-- =================================================
             CÓMO LLEGAR (OpenStreetMap + Overpass)
        ================================================== -->
        <section id="como-llegar" class="py-5 border-top border-dark-subtle">
            <div class="container-xl">

                <h2 class="h4 fw-bold text-white mb-1">Cómo llegar</h2>
                <p class="text-secondary-custom mb-4">
                    <?= e($concert['venue']) ?> — <?= e($concert['address']) ?>, <?= e($concert['city']) ?>
                </p>

                <div class="row g-4">

                    <div class="col-12 col-lg-8">
                        <div id="venue-map" role="img" aria-label="Mapa de <?= e($concert['venue']) ?>"></div>
                        <p class="small text-secondary-custom mt-2 mb-0" id="map-status">Cargando mapa…</p>
                    </div>

                    <div class="col-12 col-lg-4">
                        <div class="step-card p-4 rounded-4 border border-secondary-subtle ct-near">
                            <h2 class="h6 fw-bold text-white mb-0">Cerca del lugar</h2>
                            <div id="near-list"><p class="small text-secondary-custom mt-2 mb-0">Buscando transporte, estacionamiento y bares…</p></div>

                            <a href="#" id="map-link" target="_blank" rel="noopener"
                                class="btn btn-sm btn-outline-violet rounded-pill mt-3" hidden>
                                <i class="bi bi-sign-turn-right me-1"></i>Cómo llegar en OpenStreetMap
                            </a>
                        </div>
                    </div>

                </div>
            </div>
        </section>


        <!-- =================================================
             PREGUNTAS FRECUENTES
        ================================================== -->
        <section id="faq" class="py-5 border-top border-dark-subtle">
            <div class="container-xl">

                <h2 class="h4 fw-bold text-white mb-4">Preguntas frecuentes</h2>

                <div class="ct-faq">

                    <details>
                        <summary>¿Cómo recibo mi entrada?</summary>
                        <p>Te llega con código QR a tu celular. Mostralo en el ingreso y listo.</p>
                    </details>

                    <details>
                        <summary>¿Cuántas entradas puedo comprar?</summary>
                        <p>Hasta 6 entradas por compra.</p>
                    </details>

                    <details>
                        <summary>¿Qué medios de pago puedo usar?</summary>
                        <p>Mercado Pago, tarjeta de crédito o débito, transferencia bancaria y efectivo en Rapipago o Pago Fácil.</p>
                    </details>

                    <details>
                        <summary>¿Las entradas son oficiales?</summary>
                        <p>Sí. Vendemos entradas oficiales, sin reventa.</p>
                    </details>

                    <details>
                        <summary>¿A qué hora empieza el show?</summary>
                        <p>El show empieza a las <?= e(substr($concert['time'], 0, 5)) ?> hs en <?= e($concert['venue']) ?>.</p>
                    </details>

                    <details>
                        <summary>¿Tuve un problema con mi compra, qué hago?</summary>
                        <p>Escribinos a soporte@allticket.com con tu número de pedido y te ayudamos.</p>
                    </details>

                </div>

            </div>
        </section>

    <?php } ?>

</main>

<?php if ($concert) { ?>

    <!-- Datos que lee el JavaScript -->
    <div id="datos-show" hidden
        data-fecha="<?= e($fechaCompleta) ?>"
        data-puede-comprar="<?php if ($sePuedeComprar && $logueado) { echo "si"; } else { echo "no"; } ?>"
        data-lugar="<?= e($concert['venue']) ?>"
        data-direccion="<?= e($concert['address']) ?>"
        data-ciudad="<?= e($concert['city']) ?>"></div>

    <script src="https://cdnjs.cloudflare.com/ajax/libs/leaflet/1.9.4/leaflet.min.js"></script>
    <script src="/assets/js/concert.js"></script>

<?php } ?>

<?php
$pageContent = ob_get_clean();
include(__DIR__ . '/../_layouts/layout.php');