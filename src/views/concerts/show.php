<?php
require_once __DIR__ . '/../../config/bootstrap.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$loginUrl = '/src/views/auth/login.php';
$userId = $_SESSION['user']['id'] ?? ($_SESSION['user_id'] ?? null);
$logueado = ($userId != null);

date_default_timezone_set('America/Argentina/Buenos_Aires');

// ============================================================
// 1. ID DEL RECITAL (?id=...)
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

function esVip($tipo)
{
    return (stripos($tipo, 'vip') !== false);
}

function esNumerada($tipo)
{
    return (stripos($tipo, 'vip') !== false || stripos($tipo, 'platea') !== false);
}

function diasRestantes($fecha)
{
    $hoy = strtotime(date('Y-m-d'));
    $dia = strtotime($fecha);
    return round(($dia - $hoy) / 86400);
}

// CAMBIO G: vence reservas held (24 h) y ventas pendientes; devuelve el stock
// (misma función que usa checkout.php)
if (!function_exists('vencerReservas')) {
    function vencerReservas(PDO $pdo)
    {
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

            foreach ($ids as $idVenta) {
                $cancelar->execute([$idVenta]);
                if ($cancelar->rowCount() === 1) {
                    $devolver->execute([$idVenta]);
                    $liberar->execute([$idVenta]);
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


// ============================================================
// 3. RECITAL Y ENTRADAS
// ============================================================

// CAMBIO G: liberar reservas vencidas antes de mostrar la disponibilidad
try {
    vencerReservas($pdo);
} catch (PDOException $e) {
    error_log('Error al vencer reservas: ' . $e->getMessage());
}

try {

    if ($id != "") {

        $stmt = $pdo->prepare(
            "SELECT
                c.id, c.name, c.description, c.image, c.genre,
                c.date, c.time, c.status, c.artist_id,
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
// 4. BUTACAS VENDIDAS
// ============================================================

if ($concert) {

    $butacasVendidas = [];

    try {
        // CAMBIO H: solo cuentan las butacas held o confirmed
        $stmtSeats = $pdo->prepare("SELECT ticket_id, seat_code FROM seat_reservations WHERE concert_id = :id AND status IN ('held', 'confirmed')");
        $stmtSeats->execute([":id" => $id]);
        $butacasVendidas = $stmtSeats->fetchAll(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        $butacasVendidas = [];
    }

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
$diasCompletos = 0;
$sePuedeComprar = false;
$fechaCompleta = "";
$precioDesde = null;
$marquesina = [];

// Datos nuevos del hero
$diaNum = "";
$mesCorto = "";
$diaSemana = "";
$horaShow = "";
$estadoVenta = 'ok';
$resumen = "";
$imagenArtista = null;
$otrosShows = [];
$iniciales = "";

// Si no esta logueado, guardamos esta pagina para volver despues del login
if (!$logueado) {
    $_SESSION['volver_a'] = $_SERVER['REQUEST_URI'] . '#entradas';
}

if ($concert) {

    $dias = diasRestantes($concert['date']);

    if ($concert['status'] == 'active' && $dias >= 0) {
        $sePuedeComprar = true;
    }

    // Fecha en partes (para la card de fecha del hero)
    $meses  = ['ene', 'feb', 'mar', 'abr', 'may', 'jun', 'jul', 'ago', 'sep', 'oct', 'nov', 'dic'];
    $semana = ['domingo', 'lunes', 'martes', 'miércoles', 'jueves', 'viernes', 'sábado'];

    $tsShow    = strtotime($concert['date']);
    $diaNum    = date('j', $tsShow);
    $mesCorto  = $meses[(int)date('n', $tsShow) - 1];
    $diaSemana = $semana[(int)date('w', $tsShow)];
    $horaShow  = substr($concert['time'], 0, 5);

    // Precio mas bajo entre las entradas que todavia tienen lugar
    foreach ($entradas as $entrada) {
        if ((int)$entrada['available'] > 0) {
            $precioDesde = $entrada['price'];
            break; // ya vienen ordenadas de menor a mayor
        }
    }

    // Estado de la venta (chip sobre la foto)
    $totalDisp = 0;
    $totalStock = 0;
    foreach ($entradas as $en) {
        $totalDisp  += (int)$en['available'];
        $totalStock += (int)$en['stock'];
    }
    if (!empty($entradas)) {
        if ($totalDisp <= 0) {
            $estadoVenta = 'agotado';
        } elseif ($totalStock > 0 && ($totalDisp / $totalStock) <= 0.15) {
            $estadoVenta = 'pocas';
        }
    }

    // Texto corto del show para el hero
    if (!empty($concert['description'])) {
        $limpio  = trim(preg_replace('/\s+/', ' ', $concert['description']));
        // Recorta a 190 caracteres sin usar mbstring (preg con /u respeta los acentos)
        if (preg_match('/^.{1,190}/su', $limpio, $corte)) {
            $resumen = $corte[0];
            if (strlen($limpio) > strlen($resumen)) {
                $resumen = rtrim($resumen) . '…';
            }
        }
    }

    // Foto propia del artista (si la tabla artists tiene columna image).
    // Va en try/catch: si la columna no existe, simplemente no se usa.
    try {
        $stmtArt = $pdo->prepare("SELECT image FROM artists WHERE id = :a");
        $stmtArt->execute([":a" => $concert['artist_id']]);
        $filaArt = $stmtArt->fetch(PDO::FETCH_ASSOC);
        if ($filaArt && !empty($filaArt['image'])) {
            $imagenArtista = $filaArt['image'];
        }
    } catch (PDOException $e) {
        $imagenArtista = null;
    }

    // Otros shows del mismo artista (cada uno con su propia foto)
    try {
        $stmtOtros = $pdo->prepare(
            "SELECT id, name, image, date, time
               FROM concerts
              WHERE artist_id = :a AND id <> :id AND status = 'active' AND date >= CURDATE()
              ORDER BY date ASC
              LIMIT 3"
        );
        $stmtOtros->execute([":a" => $concert['artist_id'], ":id" => $concert['id']]);
        $otrosShows = $stmtOtros->fetchAll(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        $otrosShows = [];
    }

    // Iniciales para el monograma (cuando no hay foto del artista)
    foreach (array_slice(preg_split('/\s+/', trim($concert['artist'])), 0, 2) as $palabra) {
        if (preg_match('/^./u', $palabra, $letra)) {
            $iniciales .= $letra[0];
        }
    }
    $iniciales = strtoupper($iniciales);

    $fechaCompleta = $concert['date'] . "T" . substr($concert['time'], 0, 5) . ":00-03:00";

    $marquesina = [
        $concert['artist'],
        formatFecha($concert['date'], $concert['time']),
        $concert['venue'],
        "viví un show único",
        "entradas oficiales, sin reventa",
        "¡no te lo pierdas!",
    ];

    $inicio = strtotime($concert['date'] . ' ' . $concert['time']);
    $fin = $inicio + 3 * 3600;
    $diasCompletos = max(0, (int)floor(($inicio - time()) / 86400));
    $linkCalendario = 'https://calendar.google.com/calendar/render?action=TEMPLATE'
        . '&text=' . urlencode($concert['name'])
        . '&dates=' . date('Ymd\THis', $inicio) . '/' . date('Ymd\THis', $fin)
        . '&ctz=America/Argentina/Buenos_Aires'
        . '&location=' . urlencode($concert['venue'] . ', ' . $concert['address'] . ', ' . $concert['city']);

    $https = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off');
    $urlPagina = ($https ? 'https' : 'http') . '://' . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI'];
    $linkWhatsapp = 'https://wa.me/?text=' . urlencode(
        $concert['name'] . ' - ' . formatFecha($concert['date'], $concert['time']) . ' en ' . $concert['venue'] . ' ' . $urlPagina
    );
}

ob_start();
?>

<link rel="stylesheet" href="/assets/css/index.css">
<link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Archivo:wdth,wght@62..125,800..900&display=swap">
<link rel="stylesheet" href="/assets/css/concert.css">
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/leaflet/1.9.4/leaflet.min.css">

<main class="bg-main-dark text-light min-vh-100 position-relative z-1 ct-page">

    <?php if (!$concert) { ?>

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
             HERO: qué, cuándo, dónde y cuánto — y un solo botón principal
        ================================================== -->
        <section class="ct-hero" id="inicio">

            <img class="ct-hero__bg" src="/<?= e($concert['image']) ?>" alt="<?= e($concert['artist']) ?>">
            <div class="ct-hero__shade" aria-hidden="true"></div>

            <div class="container-xl ct-hero__inner">
                <div class="ct-hero__text">

                    <a href="/src/views/index.php#eventos" class="ct-hero__back">
                        <i class="bi bi-arrow-left me-1"></i>Volver a recitales
                    </a>

                    <h1 class="ct-hero__title"><?= e($concert['artist']) ?></h1>
                    <?php if (strcasecmp(trim($concert['name']), trim($concert['artist'])) !== 0) { ?>
                        <p class="ct-hero__sub"><?= e($concert['name']) ?></p>
                    <?php } ?>

                    <div class="ct-when">
                        <div class="ct-when__date">
                            <span class="ct-when__day"><?= e($diaNum) ?></span>
                            <span class="ct-when__mon"><?= e($mesCorto) ?></span>
                        </div>
                        <div class="ct-when__info">
                            <strong><?= e($diaSemana) ?>, <?= e($horaShow) ?> hs</strong>
                            <span><?= e($concert['venue']) ?> · <?= e($concert['city']) ?></span>
                        </div>
                    </div>

                    <?php if ($dias >= 0) { ?>
                        <?php if ($dias == 0) { ?>
                            <p class="ct-hero__eyebrow">¡El show es hoy!</p>
                        <?php } elseif ($dias == 1) { ?>
                            <p class="ct-hero__eyebrow">¡El show es mañana!</p>
                        <?php } ?>

                        <div class="ct-countdown" id="countdown" aria-live="off">
                            <div class="ct-countdown__box"><span class="ct-countdown__num" data-cd="d"><?= $diasCompletos ?></span><span class="ct-countdown__lbl">días</span></div>
                            <div class="ct-countdown__box"><span class="ct-countdown__num" data-cd="h">--</span><span class="ct-countdown__lbl">horas</span></div>
                            <div class="ct-countdown__box"><span class="ct-countdown__num" data-cd="m">--</span><span class="ct-countdown__lbl">min</span></div>
                            <div class="ct-countdown__box"><span class="ct-countdown__num" data-cd="s">--</span><span class="ct-countdown__lbl">seg</span></div>
                        </div>
                    <?php } else { ?>
                        <div class="alert alert-warning d-inline-block">Este recital ya terminó.</div>
                    <?php } ?>

                    <?php if ($concert['status'] != 'active' && $dias >= 0) { ?>
                        <div class="alert alert-warning d-inline-block mb-3">Este recital no está a la venta por ahora.</div>
                    <?php } ?>

                    <?php if ($sePuedeComprar && !empty($entradas)) { ?>
                        <div class="ct-hero__cta">
                            <a href="#entradas" class="btn btn-violet-glow btn-lg px-5 rounded-pill">
                                Elegir entradas <i class="bi bi-arrow-down ms-2"></i>
                            </a>
                           <!--<span class="ct-hero__from">
                                <?php if ($precioDesde !== null) { ?>
                                    Desde <strong><?= e(formatPrecio($precioDesde)) ?></strong>
                                <?php } else { ?>
                                    Entradas agotadas
                                <?php } ?>
                            </span>
                            -->
                            <?php if ($estadoVenta == 'pocas') { ?>
                                <span class="ct-hero__state"><span class="ct-dot is-low"></span>Quedan pocas</span>
                            <?php } ?>
                        </div>
                    <?php } ?>

                    <?php if ($dias >= 0) { ?>
                        <div class="ct-hero__links">
                            <a href="<?= e($linkCalendario) ?>" target="_blank" rel="noopener">
                                <i class="bi bi-calendar-plus"></i>Agregar al calendario
                            </a>
                            <a href="<?= e($linkWhatsapp) ?>" target="_blank" rel="noopener">
                                <i class="bi bi-whatsapp"></i>Compartir
                            </a>
                            <a href="#como-llegar">
                                <i class="bi bi-geo-alt"></i>Cómo llegar
                            </a>
                        </div>
                    <?php } ?>

                </div>
            </div>
        </section>


        <!-- MENÚ DE SECCIONES (mismo orden que la página) -->
        <nav class="ct-subnav" aria-label="Secciones del recital">
            <div class="container-xl ct-subnav__inner">
                <a href="#artista">Artista</a>
                <a href="#entradas">Entradas</a>
                <a href="#info">Info</a>
                <a href="#como-llegar">Cómo llegar</a>
                <a href="#faq">Preguntas</a>
            </div>
        </nav>


        <!-- =================================================
             SOBRE EL ARTISTA
        ================================================== -->
        <section id="artista" class="py-5 ct-artist">
            <div class="container-xl">

                <h2 class="ct-artist__name"><?= e($concert['artist']) ?></h2>
                <?php if (!empty($concert['genre'])) { ?>
                    <p class="ct-artist__genre"><?= e($concert['genre']) ?></p>
                <?php } ?>

                <div class="row g-4 g-lg-5">

                    <div class="col-12 <?= $imagenArtista ? 'col-lg-7' : 'col-lg-8' ?>">

                        <?php if (!empty($concert['artist_description'])) { ?>
                            <p class="ct-artist__bio" style="white-space: pre-line;"><?= e($concert['artist_description']) ?></p>
                        <?php } else { ?>
                            <p class="ct-artist__bio">Pronto vamos a sumar más información sobre <?= e($concert['artist']) ?>.</p>
                        <?php } ?>

                        <?php if (!empty($otrosShows)) { ?>
                            <h3 class="ct-h">Otras fechas</h3>
                            <ul class="ct-dates list-unstyled">
                                <?php foreach ($otrosShows as $otro) { ?>
                                    <?php
                                    $tsOtro = strtotime($otro['date']);
                                    $textoFecha = date('j', $tsOtro) . ' de ' . $meses[(int)date('n', $tsOtro) - 1];
                                    ?>
                                    <li>
                                        <a href="/src/views/concerts/show.php?id=<?= e($otro['id']) ?>">
                                            <img src="/<?= e($otro['image']) ?>" alt="" loading="lazy">
                                            <span class="ct-dates__txt">
                                                <strong><?= e($otro['name']) ?></strong>
                                                <small><?= e($textoFecha) ?></small>
                                            </span>
                                            <i class="bi bi-arrow-right"></i>
                                        </a>
                                    </li>
                                <?php } ?>
                            </ul>
                        <?php } ?>

                    </div>

                    <?php if ($imagenArtista) { ?>
                        <div class="col-12 col-lg-5">
                            <div class="ct-artist__photo">
                                <img src="/<?= e($imagenArtista) ?>" alt="<?= e($concert['artist']) ?>" loading="lazy">
                            </div>
                        </div>
                    <?php } ?>

                </div>
            </div>
        </section>


        <!-- =================================================
             COMPRA: va primero, es lo que la persona vino a hacer
        ================================================== -->
        <section id="entradas" class="py-5 ct-buy">
            <div class="container-xl">

                <?php if (empty($entradas)) { ?>

                    <h2 class="h4 fw-bold text-white mb-2">Entradas</h2>
                    <p class="text-secondary-custom">Todavía no hay entradas cargadas para este recital.</p>

                <?php } else { ?>

                    <form action="/src/views/concerts/checkout.php" method="POST" id="form-compra" novalidate>

                        <input type="hidden" name="concert_id" value="<?= e($concert['id']) ?>">
                        <input type="hidden" name="quantity" id="f-qty" value="1">
                        <input type="hidden" name="seats" id="f-seats" value="">

                        <div class="row g-4 align-items-start">

                            <div class="col-12 col-lg-8">

                                <!-- PASO 1 -->
                                <div class="ct-step">
                                    <div class="ct-step__head">
                                        <span class="ct-step__n">1</span>
                                        <div>
                                            <h2 class="ct-step__title">Elegí tu ubicación</h2>
                                            <p class="ct-step__hint">En las numeradas elegís tu butaca; en las demás, entrás y te ubicás donde quieras.</p>
                                        </div>
                                    </div>

                                    <div class="d-grid gap-3" role="radiogroup" aria-label="Tipo de entrada">

                                        <?php foreach ($entradas as $i => $entrada) { ?>

                                            <?php
                                            $agotada   = ((int)$entrada['available'] <= 0);
                                            $vip       = esVip($entrada['type']);
                                            $numerada  = esNumerada($entrada['type']);
                                            $idInput   = "entrada-" . $i;

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
                                                    data-numerada="<?= $numerada ? 'si' : 'no' ?>"
                                                    data-ocupadas="<?= e($entrada['ocupadas']) ?>"
                                                    <?php if ($agotada || !$sePuedeComprar) { echo "disabled"; } ?>>

                                                <label for="<?= $idInput ?>" class="ct-ticket">
                                                    <div class="ct-ticket__row">

                                                        <div class="ct-ticket__main">
                                                            <span class="ct-ticket__icon<?= $vip ? ' is-vip' : '' ?>" aria-hidden="true">
                                                                <?php if ($vip) { ?>
                                                                    <i class="bi bi-star-fill"></i>
                                                                <?php } elseif ($numerada) { ?>
                                                                    <i class="bi bi-grid-3x3-gap-fill"></i>
                                                                <?php } else { ?>
                                                                    <i class="bi bi-people-fill"></i>
                                                                <?php } ?>
                                                            </span>

                                                            <div class="ct-ticket__body">
                                                                <h3 class="ct-ticket__name">
                                                                    <?= e($entrada['type']) ?>
                                                                    <?php if ($agotada) { ?>
                                                                        <span class="badge text-bg-secondary ms-1">Agotada</span>
                                                                    <?php } elseif ((int)$entrada['available'] <= 20) { ?>
                                                                        <span class="badge text-bg-danger ms-1">¡Últimas!</span>
                                                                    <?php } ?>
                                                                </h3>

                                                                <p class="small text-secondary-custom mb-0">
                                                                    <?= $numerada ? "Elegís tu butaca" : "Ingreso libre, sin butaca asignada" ?>
                                                                    · <?= $agotada ? "sin disponibilidad" : (int)$entrada['available'] . " disponibles" ?>
                                                                </p>

                                                                <div class="ct-meter<?php if ($porcentaje <= 20) { echo " is-low"; } ?>" aria-hidden="true">
                                                                    <span style="width: <?= (int)$porcentaje ?>%"></span>
                                                                </div>
                                                            </div>
                                                        </div>

                                                        <div class="ct-ticket__stub">
                                                            <span class="ct-ticket__amount"><?= e(formatPrecio($entrada['price'])) ?></span>
                                                            <small>por entrada</small>
                                                        </div>

                                                    </div>
                                                </label>
                                            </div>

                                        <?php } ?>

                                    </div>
                                </div>


                                <!-- PASO 2: butacas o cantidad, según lo que se eligió -->
                                <div class="ct-step">
                                    <div class="ct-step__head">
                                        <span class="ct-step__n">2</span>
                                        <div>
                                            <h2 class="ct-step__title" id="step2-title">Butacas o cantidad</h2>
                                            <p class="ct-step__hint" id="step2-hint">Hasta 6 entradas por compra.</p>
                                        </div>
                                    </div>

                                    <!-- Antes de elegir ubicación -->
                                    <div id="panel-empty" class="ct-empty">
                                        <i class="bi bi-arrow-up-circle"></i>
                                        Primero elegí una ubicación y acá aparecen las butacas o la cantidad.
                                    </div>

                                    <!-- Entradas numeradas -->
                                    <div id="panel-seats" class="ct-seatbox" hidden>

                                        <div class="ct-autopick">
                                            <label for="auto-qty" class="small text-secondary-custom">¿No querés buscar? Elegimos por vos:</label>
                                            <div class="d-flex gap-2">
                                                <select id="auto-qty" class="form-select form-select-sm ct-autopick__sel" aria-label="Cantidad de butacas">
                                                    <option value="1">1 butaca</option>
                                                    <option value="2" selected>2 butacas juntas</option>
                                                    <option value="3">3 butacas juntas</option>
                                                    <option value="4">4 butacas juntas</option>
                                                    <option value="5">5 butacas juntas</option>
                                                    <option value="6">6 butacas juntas</option>
                                                </select>
                                                <button type="button" id="auto-pick" class="btn btn-sm btn-outline-violet rounded-pill px-3">
                                                    <i class="bi bi-magic me-1"></i>Mejores disponibles
                                                </button>
                                            </div>
                                        </div>

                                        <div class="ct-legend mb-3">
                                            <span><i style="background:rgba(255,255,255,.16)"></i>Libre</span>
                                            <span><i style="background:linear-gradient(135deg,#a855f7,#22d3ee)"></i>Tu selección</span>
                                            <span><i style="background:#2a2a38"></i>Ocupada</span>
                                        </div>

                                        <div class="ct-stage">ESCENARIO</div>
                                        <div class="ct-seatscroll" id="seat-grid" role="group" aria-label="Mapa de butacas"></div>

                                        <div class="mt-3" id="seat-chips" aria-live="polite"></div>
                                    </div>

                                    <!-- Entradas sin butaca -->
                                    <div id="panel-field" class="ct-field" hidden>
                                        <div class="d-flex flex-wrap align-items-center justify-content-between gap-3">
                                            <p class="small text-secondary-custom mb-0">
                                                Esta ubicación no tiene butaca asignada: ubicate donde prefieras dentro del sector.
                                            </p>
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

                            </div>


                            <!-- PASO 3: resumen (en el celular queda en la barra de abajo) -->
                            <div class="col-12 col-lg-4">
                                <div class="ct-summary">

                                    <div class="ct-step__head mb-3">
                                        <span class="ct-step__n">3</span>
                                        <h2 class="ct-step__title">Revisá y pagá</h2>
                                    </div>

                                    <div class="small text-secondary-custom" id="sum-text" aria-live="polite">Elegí un tipo de entrada para continuar.</div>

                                    <div class="d-flex justify-content-between align-items-center border-top border-secondary-subtle pt-3 mt-3">
                                        <span class="text-secondary-custom">Total</span>
                                        <span class="ct-summary__total text-white js-total">$ 0</span>
                                    </div>

                                    <button type="submit" class="btn btn-violet-glow btn-lg w-100 rounded-pill mt-3 d-none d-lg-block js-buy" disabled>
                                        <span class="js-buy-label">Continuar con el pago</span> <i class="bi bi-arrow-right ms-2"></i>
                                    </button>

                                    <?php if (!$logueado) { ?>
                                        <p class="small text-secondary-custom mt-3 mb-0">
                                            <i class="bi bi-info-circle me-1 text-violet-neon"></i>
                                            Armá tu selección tranquilo. Para pagar te pedimos iniciar sesión y la selección te espera.
                                        </p>
                                    <?php } ?>

                                    <p class="text-danger small mt-2 mb-0" id="form-error" role="alert" hidden></p>

                                </div>
                            </div>

                        </div>

                        <!-- Barra fija de compra (solo celular) -->
                        <div class="ct-buybar" id="buybar">
                            <div>
                                <span class="ct-buybar__lbl">Total</span>
                                <span class="ct-buybar__total js-total">$ 0</span>
                            </div>
                            <button type="submit" class="btn btn-violet-glow rounded-pill px-4 js-buy" disabled>
                                <span class="js-buy-label">Continuar con el pago</span>
                            </button>
                        </div>

                    </form>

                <?php } ?>

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


        <!-- =================================================
             INFO DEL SHOW
        ================================================== -->
        <section id="info" class="py-5">
            <div class="container-xl">
                <div class="row g-5">

                    <div class="col-12 col-lg-7">
                        <h2 class="ct-h">Sobre el show</h2>
                        <?php if (!empty($concert['description'])) { ?>
                            <p class="ct-prose" style="white-space: pre-line;"><?= e($concert['description']) ?></p>
                        <?php } else { ?>
                            <p class="ct-prose">Pronto vamos a sumar más detalles de este show.</p>
                        <?php } ?>
                    </div>

                    <div class="col-12 col-lg-5">
                        <h2 class="ct-h">Antes de ir</h2>
                        <dl class="ct-dl">
                            <div><dt>Inicio</dt><dd><?= e($horaShow) ?> hs en <?= e($concert['venue']) ?></dd></div>
                            <div><dt>Entrada</dt><dd>Código QR en tu celular, no hace falta imprimir.</dd></div>
                            <div><dt>Compra</dt><dd>Entradas oficiales, sin reventa.</dd></div>
                            <div><dt>Soporte</dt><dd>soporte@allticket.com</dd></div>
                        </dl>
                    </div>

                </div>
            </div>
        </section>


        <!-- =================================================
             CÓMO LLEGAR (el mapa se carga recién al acercarse)
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
                        <summary>Tuve un problema con mi compra, ¿qué hago?</summary>
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
        data-concierto="<?= e($concert['id']) ?>"
        data-fecha="<?= e($fechaCompleta) ?>"
        data-puede-comprar="<?= $sePuedeComprar ? 'si' : 'no' ?>"
        data-logueado="<?= $logueado ? 'si' : 'no' ?>"
        data-login-url="<?= e($loginUrl) ?>"
        data-lugar="<?= e($concert['venue']) ?>"
        data-direccion="<?= e($concert['address']) ?>"
        data-ciudad="<?= e($concert['city']) ?>"></div>

    <script src="https://cdnjs.cloudflare.com/ajax/libs/leaflet/1.9.4/leaflet.min.js"></script>
    <script src="/assets/js/concert.js"></script>

<?php } ?>

<?php
$pageContent = ob_get_clean();
include(__DIR__ . '/../_layouts/layout.php');