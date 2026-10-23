<?php
require_once __DIR__ . '/../config/bootstrap.php';

// Mail de soporte: se cambia acá y se actualiza en toda la página
$emailSoporte = 'soporte@allticket.com';


// ============================================================
// 1. LEEMOS LO QUE ESCRIBIÓ O ELIGIÓ LA PERSONA (viene en la URL)
//    ?q=duki          -> lo de la lupa
//    ?categoria=rock  -> el menú Categorías y los chips
//    ?ciudad=Rosario  -> el selector de ciudad
//    ?finde=1         -> chip "Este fin de semana"
//    ?ver_todos=1     -> botón "Ver todos los recitales"
// ============================================================

// Lee un parámetro de la URL y siempre devuelve texto
// (si alguien manda ?q[]=algo no se rompe la página)
function parametroTexto(string $nombre): string
{
    $valor = $_GET[$nombre] ?? '';
    return is_string($valor) ? trim($valor) : '';
}

$busqueda = parametroTexto('q');
$categoria = parametroTexto('categoria');
$ciudad = parametroTexto('ciudad');
$verTodos = ($_GET['ver_todos'] ?? '') === '1';
$finde = ($_GET['finde'] ?? '') === '1';

// Resultado del formulario de newsletter (lo manda el controlador)
$estadoNewsletter = parametroTexto('newsletter');
if (!in_array($estadoNewsletter, ['ok', 'invalid', 'error', 'rate_limited'], true)) {
    $estadoNewsletter = '';
}


// ============================================================
// 2. LISTAS DE DATOS FIJOS
// ============================================================

// Categorías: a la izquierda lo que va en la URL, a la derecha lo que ve la persona
$categorias = [
    "rock" => "Rock",
    "reggaeton" => "Reggaetón / Trap",
    "pop" => "Pop",
    "electronica" => "Electrónica"
];

// Si escribieron una categoría que no existe, la borramos
if ($categoria !== '' && !array_key_exists($categoria, $categorias)) {
    $categoria = '';
}

// Garantías (sección de confianza)
$garantias = [
    [
        "icono" => "bi-patch-check",
        "titulo" => "Entradas oficiales",
        "texto" => "Sin reventa ni sorpresas: comprás tu entrada oficial."
    ],
    [
        "icono" => "bi-shield-lock",
        "titulo" => "Compra segura",
        "texto" => "Tus pagos se procesan con medios de pago homologados."
    ],
    [
        "icono" => "bi-qr-code",
        "titulo" => "Entrada en tu celular",
        "texto" => "Recibís tu ticket con código QR, sin imprimir nada."
    ],
    [
        "icono" => "bi-headset",
        "titulo" => "Soporte",
        "texto" => "Escribinos a " . $emailSoporte . " y te ayudamos."
    ]
];

// Medios de pago que se muestran debajo de las garantías.
// OJO: dejá solo los que AllTicket realmente acepta.
$mediosPago = [
    ["icono" => "bi-wallet2", "nombre" => "Mercado Pago", "marca" => "MP", "clave" => "mercado-pago"],
    ["icono" => "bi-credit-card-2-front", "nombre" => "Visa", "marca" => "VISA", "clave" => "visa"],
    ["icono" => "bi-credit-card-2-front", "nombre" => "Mastercard", "marca" => "mastercard", "clave" => "mastercard"],
    ["icono" => "bi-credit-card-2-front", "nombre" => "American Express", "marca" => "AMEX", "clave" => "american-express"],
    ["icono" => "bi-bank", "nombre" => "Transferencia bancaria", "marca" => "TRANSFERENCIA", "clave" => "transferencia"],
    ["icono" => "bi-cash-coin", "nombre" => "Rapipago", "marca" => "RAPIPAGO", "clave" => "rapipago"],
    ["icono" => "bi-cash-coin", "nombre" => "Pago Fácil", "marca" => "PAGO FÁCIL", "clave" => "pago-facil"]
];

// Preguntas frecuentes
// OJO: revisá que las respuestas coincidan con las reglas reales
// de AllTicket (sobre todo pagos y cancelaciones).
$preguntas = [
    [
        "pregunta" => "¿Cómo recibo mi entrada?",
        "respuesta" => "Cuando se confirma el pago, te enviamos tu ticket digital con código QR directo a tu celular o e-mail."
    ],
    [
        "pregunta" => "¿Tengo que imprimir la entrada?",
        "respuesta" => "No. Alcanza con mostrar el código QR desde tu celular en la puerta del evento."
    ],
    [
        "pregunta" => "¿Las entradas son oficiales?",
        "respuesta" => "Sí. En AllTicket no hay reventa: las entradas que comprás son oficiales."
    ],
    [
        "pregunta" => "¿Qué medios de pago puedo usar?",
        "respuesta" => "Podés pagar con los medios de pago homologados que aparecen disponibles al momento de la compra."
    ],
    [
        "pregunta" => "¿Qué pasa si se cancela o se reprograma el evento?",
        "respuesta" => "Te avisamos por e-mail y te explicamos cómo seguir: devolución de tu compra o validez de la entrada para la nueva fecha."
    ],
    [
        "pregunta" => "¿Cómo me comunico con soporte?",
        "respuesta" => "Escribinos a " . $emailSoporte . " y te respondemos lo antes posible."
    ]
];

// ¿Hay algún filtro puesto? (true = sí, false = no)
$hayFiltros = $busqueda !== '' || $categoria !== '' || $ciudad !== '' || $finde;


// ============================================================
// 3. FUNCIONES
// ============================================================

// Devuelve el pedacito de texto que buscamos en la columna "genre"
function textoGenero($categoria)
{
    if ($categoria == "reggaeton") {
        return "reggaet";
    }

    if ($categoria == "electronica") {
        return "electr";
    }

    // rock y pop se buscan con su mismo nombre
    return $categoria;
}

// Texto "Faltan X días" para la tarjeta
function textoDias($fecha)
{
    $hoy = new DateTime('today', new DateTimeZone('America/Argentina/Buenos_Aires'));
    $show = new DateTime($fecha, new DateTimeZone('America/Argentina/Buenos_Aires'));
    $dias = (int) $hoy->diff($show)->format('%r%a');

    if ($dias <= 0) {
        return "¡Es hoy!";
    }
    if ($dias == 1) {
        return "¡Es mañana!";
    }
    return "Faltan " . $dias . " días";
}

// Devuelve [desde, hasta] del fin de semana (viernes a domingo).
// Si hoy ya es viernes, sábado o domingo, arranca desde hoy.
function rangoFinDeSemana(): array
{
    $zona = new DateTimeZone('America/Argentina/Buenos_Aires');
    $hoy = new DateTime('today', $zona);
    $dia = (int) $hoy->format('N'); // 1 = lunes ... 7 = domingo

    $desde = clone $hoy;
    if ($dia < 5) {
        $desde->modify('+' . (5 - $dia) . ' days');
    }

    $hasta = clone $hoy;
    $hasta->modify('+' . (7 - $dia) . ' days');

    return [$desde->format('Y-m-d'), $hasta->format('Y-m-d')];
}

// Arma el link de los chips, guardando lo que ya estaba elegido
function armarLink($busqueda, $categoria, $ciudad, bool $finde = false, bool $verTodos = false)
{
    $link = url('/src/views/index.php?');

    if ($busqueda != "") {
        $link .= "q=" . urlencode($busqueda) . "&";
    }

    if ($categoria != "") {
        $link .= "categoria=" . urlencode($categoria) . "&";
    }

    if ($ciudad != "") {
        $link .= "ciudad=" . urlencode($ciudad) . "&";
    }

    if ($finde) {
        $link .= "finde=1&";
    }

    if ($verTodos) {
        $link .= "ver_todos=1&";
    }

    return $link . "#eventos";
}


// ============================================================
// 4. TRAEMOS LOS DATOS DE LA BASE DE DATOS
// ============================================================

// ---------- 4.1 Recitales de la cartelera (con filtros) ----------

$concerts = [];
$ciudades = [];
$errorCartelera = false;

try {

    // Consulta base: eventos activos que todavía no pasaron
    $sql = "SELECT
            c.id, c.name, c.image, c.genre, c.date, c.time,
            a.name AS artist,
            v.name AS venue,
            v.city,
            ticket_totals.min_price AS min_price,
            COALESCE(ticket_totals.total_available, 0) AS total_available,
            COALESCE(ticket_totals.total_stock, 0) AS total_stock
        FROM concerts c
        JOIN artists a ON c.artist_id = a.id
        JOIN venues v ON c.venue_id = v.id
        LEFT JOIN (
            SELECT
                t.concert_id,
                MIN(CASE WHEN t.available > 0 THEN t.price END) AS min_price,
                SUM(t.available) AS total_available,
                SUM(t.stock) AS total_stock
            FROM tickets t
            GROUP BY t.concert_id
        ) ticket_totals ON ticket_totals.concert_id = c.id
        WHERE c.status = 'active'
          AND c.date >= CURDATE()";

    // Datos que reemplazan a los :nombres de la consulta
    $datos = [];

    // Si buscó algo con la lupa
    if ($busqueda !== '') {
        $sql .= " AND (c.name LIKE :q1 OR v.name LIKE :q2 OR c.genre LIKE :q3 OR a.name LIKE :q4)";
        $datos[":q1"] = "%" . $busqueda . "%";
        $datos[":q2"] = "%" . $busqueda . "%";
        $datos[":q3"] = "%" . $busqueda . "%";
        $datos[":q4"] = "%" . $busqueda . "%";
    }

    // Si eligió una categoría
    if ($categoria !== '') {
        $sql .= " AND c.genre LIKE :genero";
        $datos[":genero"] = "%" . textoGenero($categoria) . "%";
    }

    // Si eligió una ciudad
    if ($ciudad !== '') {
        $sql .= " AND v.city = :ciudad";
        $datos[":ciudad"] = $ciudad;
    }

    // Si eligió "Este fin de semana"
    if ($finde) {
        [$desdeFinde, $hastaFinde] = rangoFinDeSemana();
        $sql .= " AND c.date BETWEEN :desde AND :hasta";
        $datos[":desde"] = $desdeFinde;
        $datos[":hasta"] = $hastaFinde;
    }

    // Final de la consulta
    $sql .= " ORDER BY c.date ASC";
    if (!$verTodos) {
        $sql .= " LIMIT 13";
    }

    $stmt = $pdo->prepare($sql);
    $stmt->execute($datos);
    $concerts = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Lista de ciudades para el selector
    $stmtCiudades = $pdo->query(
        "SELECT DISTINCT v.city
           FROM venues v
           JOIN concerts c ON c.venue_id = v.id
          WHERE c.status = 'active'
            AND c.date >= CURDATE()
          ORDER BY v.city ASC"
    );
    $ciudades = $stmtCiudades->fetchAll(PDO::FETCH_COLUMN);

} catch (PDOException $e) {
    // Si la base falla, el index se muestra igual, sólo sin recitales
    error_log('Error al consultar la cartelera: ' . $e->getMessage());
    $concerts = [];
    $ciudades = [];
    $errorCartelera = true;
}

$concertsListado = $verTodos ? $concerts : array_slice($concerts, 0, 12);
$hayMasEventos = !$verTodos && count($concerts) > 12;


// ---------- 4.2 Carrusel de próximos shows ----------

$showsCarousel = [];
try {
    $stmtShows = $pdo->query(
        "SELECT c.id, c.name, c.date, a.name AS artist,
                CASE a.image
                    WHEN 'assets/img/tini.webp' THEN 'assets/img/carousel/tini.jpg'
                    WHEN 'assets/img/clarent.webp' THEN 'assets/img/carousel/clarent.jpg'
                    WHEN 'assets/img/cazzu.jpg' THEN 'assets/img/carousel/cazzu.jpg'
                    WHEN 'assets/img/badgyal.jpg' THEN 'assets/img/carousel/badgyal.jpg'
                    WHEN 'assets/img/anuel.jpg' THEN 'assets/img/carousel/anuel.jpg'
                    ELSE COALESCE(NULLIF(a.image, ''), c.image)
                END AS image
           FROM concerts c
           JOIN artists a ON a.id = c.artist_id
          WHERE c.status = 'active' AND c.date >= CURDATE()
          ORDER BY c.date ASC
          LIMIT 10"
    );
    $showsCarousel = $stmtShows->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    error_log('Error al consultar los próximos shows del carrusel: ' . $e->getMessage());
}


// ---------- 4.3 Artistas destacados (los que más shows próximos tienen) ----------

$artistas = [];
try {
    $stmtArtistas = $pdo->query(
        "SELECT a.id, a.name, a.image, COUNT(c.id) AS shows
           FROM artists a
           JOIN concerts c ON c.artist_id = a.id
          WHERE c.status = 'active' AND c.date >= CURDATE()
          GROUP BY a.id, a.name, a.image
          ORDER BY shows DESC, MIN(c.date) ASC
          LIMIT 8"
    );
    $artistas = $stmtArtistas->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    error_log('Error al consultar los artistas destacados: ' . $e->getMessage());
}


// ---------- 4.4 Lugares populares ----------

$lugares = [];
try {
    $stmtLugares = $pdo->query(
        "SELECT v.id, v.name, v.city, COUNT(c.id) AS shows
           FROM venues v
           JOIN concerts c ON c.venue_id = v.id
          WHERE c.status = 'active' AND c.date >= CURDATE()
          GROUP BY v.id, v.name, v.city
          ORDER BY shows DESC, v.name ASC
          LIMIT 4"
    );
    $lugares = $stmtLugares->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    error_log('Error al consultar los lugares populares: ' . $e->getMessage());
}


// ---------- 4.5 Noticias ----------

$noticias = [];
try {
    $stmtNoticias = $pdo->query(
        'SELECT p.id, p.title, p.content, p.created_at
           FROM posts p
          ORDER BY p.created_at DESC
          LIMIT 3'
    );
    $noticias = $stmtNoticias->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    error_log('Error al consultar las noticias del inicio: ' . $e->getMessage());
}


// ---------- 4.6 Estadísticas del hero (datos reales) ----------
// Solo se cuentan como vendidas las entradas de ventas pagadas.
// Los recitales cancelados no se consideran realizados.

$entradasVendidas = 0;
$eventosRealizados = 0;
try {
    $entradasVendidas = (int) $pdo->query(
        "SELECT COALESCE(SUM(d.quantity), 0)
           FROM sale_details d
           JOIN sales s ON s.id = d.sale_id
          WHERE s.status = 'paid'"
    )->fetchColumn();

    $eventosRealizados = (int) $pdo->query(
        "SELECT COUNT(*) FROM concerts
          WHERE date < CURDATE() AND status <> 'cancelled'"
    )->fetchColumn();
} catch (PDOException $e) {
    error_log('Error al calcular las estadísticas del inicio: ' . $e->getMessage());
}
$pageStyles = '<link rel="stylesheet" href="' . e(url('/assets/css/index.css')) . '">';
ob_start();
?>
<main class="home-page bg-main-dark text-light min-vh-100 position-relative z-1 overflow-hidden"><!-- =====================================================
         HERO FULL-BLEED
    ====================================================== -->

    <section class="hero-fullbleed position-relative" id="hero">

        <!-- IMAGEN DE FONDO -->

        <img class="hero-fullbleed-bg" src="<?= e(url('/assets/img/hero.png')) ?>" alt="" aria-hidden="true" />

        <div class="hero-fullbleed-overlay-top" aria-hidden="true"></div>
        <div class="hero-fullbleed-overlay-bottom" aria-hidden="true"></div>


        <!-- CONTENIDO DEL HERO -->

        <div class="hero-fullbleed-content hero-fullbleed-content--center container-xl">

            <h1 class="hero-title-v2 mb-4">
                <span class="hero-line-1">Momentos inolvidables</span>
                <br>
                <span class="hero-line-2"><em>a solo un click.</em></span>
            </h1>

            <p class="lead hero-description mb-4 fs-5">
                Sin reventa, sin filas, sin sorpresas.
                Comprá tus entradas oficiales para los shows
                que no te querés perder.
            </p>

            <div class="d-flex flex-wrap justify-content-center gap-3 mb-2">

                <a href="#eventos"
                    class="btn btn-violet-glow btn-home-gradient btn-lg px-5 rounded-pill d-inline-flex align-items-center">
                    Buscar eventos
                    <i class="bi bi-arrow-right ms-2"></i>
                </a>

            </div>

            <div class="mb-5">
                <a href="#como-funciona" class="hero-secondary-link">
                    Cómo funciona
                </a>
            </div>

            <!-- Estadísticas reales: si todavía no hay datos, no se muestran -->
            <?php if ($entradasVendidas > 0 || $eventosRealizados > 0): ?>
                <div class="d-flex flex-wrap justify-content-center gap-4 gap-lg-5">

                    <?php if ($entradasVendidas > 0): ?>
                        <div>
                            <div class="hero-stat-value" data-contador="<?= $entradasVendidas ?>" data-sufijo="+">
                                <?= $entradasVendidas ?>+</div>
                            <div class="hero-stat-label">
                                entradas vendidas
                            </div>
                        </div>
                    <?php endif; ?>

                    <?php if ($eventosRealizados > 0): ?>
                        <div>
                            <div class="hero-stat-value" data-contador="<?= $eventosRealizados ?>" data-sufijo="+">
                                <?= $eventosRealizados ?>+</div>
                            <div class="hero-stat-label">
                                eventos realizados
                            </div>
                        </div>
                    <?php endif; ?>

                </div>
            <?php endif; ?>

        </div>

    </section>


    <!-- =====================================================
         PRÓXIMOS SHOWS (carrusel)
    ====================================================== -->

    <?php if (!empty($showsCarousel)): ?>
        <section id="proximos-shows" class="upcoming-shows-section py-5" aria-labelledby="upcoming-shows-title">
            <div class="container-xl">
                <div class="upcoming-shows-heading d-flex justify-content-between align-items-end gap-3 mb-4">
                    <div>
                        <span class="upcoming-shows-eyebrow">NO TE LO PIERDAS</span>
                        <h2 id="upcoming-shows-title" class="h3 fw-bold text-white mb-0">Próximos shows</h2>
                    </div>
                    <?php if (count($showsCarousel) > 1): ?>
                        <div class="upcoming-shows-controls">
                            <button class="btn upcoming-shows-control" type="button" data-show-step="-1"
                                aria-label="Show anterior">
                                <i class="bi bi-arrow-left" aria-hidden="true"></i>
                            </button>
                            <button class="btn upcoming-shows-control" type="button" data-show-step="1"
                                aria-label="Show siguiente">
                                <i class="bi bi-arrow-right" aria-hidden="true"></i>
                            </button>
                        </div>
                    <?php endif; ?>
                </div>

                <div id="upcoming-shows-carousel" class="upcoming-shows-carousel" role="region"
                    aria-roledescription="carrusel" aria-label="Próximos recitales" tabindex="0">
                    <div class="upcoming-shows-panels" role="tablist" aria-label="Elegí un próximo show">
                        <?php foreach ($showsCarousel as $index => $show): ?>
                            <button class="upcoming-show-panel <?= $index === 0 ? 'is-active' : '' ?>"
                                type="button" role="tab" aria-selected="<?= $index === 0 ? 'true' : 'false' ?>"
                                tabindex="<?= $index === 0 ? '0' : '-1' ?>"
                                aria-label="<?= e($show['artist']) ?>: <?= e($show['name']) ?>"
                                aria-controls="upcoming-show-detail"
                                data-show-title="<?= e($show['name']) ?>"
                                data-show-artist="<?= e($show['artist']) ?>"
                                data-show-date="<?= e(formatFecha($show['date'])) ?>"
                                data-show-href="<?= e(url('/src/views/concerts/show.php?id=' . urlencode($show['id']))) ?>">
                                <?php if (!empty($show['image'])): ?>
                                    <img src="<?= e(url('/' . ltrim($show['image'], '/'))) ?>" class="upcoming-show-image"
                                        alt="" aria-hidden="true" <?= $index === 0 ? 'fetchpriority="high"' : 'loading="lazy"' ?>>
                                <?php else: ?>
                                    <span class="upcoming-show-image upcoming-show-image--empty" aria-hidden="true">
                                        <i class="bi bi-music-note-beamed"></i>
                                    </span>
                                <?php endif; ?>
                                <span class="upcoming-show-shade" aria-hidden="true"></span>
                                <span class="upcoming-show-panel-label" aria-hidden="true">
                                    <?= e($show['artist']) ?>
                                </span>
                                <span class="upcoming-show-panel-index" aria-hidden="true">
                                    <?= str_pad((string) ($index + 1), 2, '0', STR_PAD_LEFT) ?>
                                </span>
                            </button>
                        <?php endforeach; ?>
                    </div>
                </div>

                <?php $firstShow = $showsCarousel[0]; ?>
                <div id="upcoming-show-detail" class="upcoming-show-details" role="tabpanel" aria-live="polite">
                    <div class="upcoming-show-details-copy">
                        <p class="upcoming-show-details-date mb-1">
                            <i class="bi bi-calendar-event me-2" aria-hidden="true"></i>
                            <span data-show-detail-date><?= e(formatFecha($firstShow['date'])) ?></span>
                        </p>
                        <h3 class="h4 fw-bold text-white mb-1" data-show-detail-artist><?= e($firstShow['artist']) ?></h3>
                        <p class="text-secondary-custom mb-0" data-show-detail-title><?= e($firstShow['name']) ?></p>
                    </div>
                    <a class="btn btn-violet-glow rounded-pill px-4 upcoming-show-details-link"
                        href="<?= e(url('/src/views/concerts/show.php?id=' . urlencode($firstShow['id']))) ?>"
                        data-show-detail-link>
                        Ver show <i class="bi bi-arrow-right ms-2" aria-hidden="true"></i>
                    </a>
                </div>
            </div>
        </section>
        <?php if (count($showsCarousel) > 1): ?>
            <script>
                (() => {
                    const section = document.getElementById('proximos-shows');
                    const panels = Array.from(section.querySelectorAll('.upcoming-show-panel'));
                    const detailArtist = section.querySelector('[data-show-detail-artist]');
                    const detailTitle = section.querySelector('[data-show-detail-title]');
                    const detailDate = section.querySelector('[data-show-detail-date]');
                    const detailLink = section.querySelector('[data-show-detail-link]');
                    const reduceMotion = window.matchMedia('(prefers-reduced-motion: reduce)');
                    let activeIndex = 0;

                    const activate = (index) => {
                        activeIndex = (index + panels.length) % panels.length;
                        panels.forEach((panel, panelIndex) => {
                            const active = panelIndex === activeIndex;
                            panel.classList.toggle('is-active', active);
                            panel.setAttribute('aria-selected', active ? 'true' : 'false');
                            panel.tabIndex = active ? 0 : -1;
                        });

                        const activePanel = panels[activeIndex];
                        detailArtist.textContent = activePanel.dataset.showArtist;
                        detailTitle.textContent = activePanel.dataset.showTitle;
                        detailDate.textContent = activePanel.dataset.showDate;
                        detailLink.href = activePanel.dataset.showHref;
                    };

                    panels.forEach((panel, index) => {
                        panel.addEventListener('click', () => activate(index));
                    });

                    section.querySelectorAll('[data-show-step]').forEach((button) => {
                        button.addEventListener('click', () => {
                            activate(activeIndex + Number(button.dataset.showStep));
                        });
                    });

                    section.addEventListener('keydown', (event) => {
                        if (event.key !== 'ArrowLeft' && event.key !== 'ArrowRight') return;
                        event.preventDefault();
                        activate(activeIndex + (event.key === 'ArrowRight' ? 1 : -1));
                        panels[activeIndex].focus();
                    });

                    if (!reduceMotion.matches) {
                        window.setInterval(() => {
                            if (!section.matches(':hover') && !section.contains(document.activeElement) && !document.hidden) {
                                activate(activeIndex + 1);
                            }
                        }, 6000);
                    }
                })();
            </script>
        <?php endif; ?>
    <?php endif; ?>

<!-- =====================================================
         EVENTOS
    ====================================================== -->

    <section id="eventos" class="py-5">

        <div class="container-xl">


            <!-- ENCABEZADO -->

            <div class="mb-4">

                <?php if ($hayFiltros): ?>

                    <h2 class="h3 fw-bold text-white mb-1">
                        <?php if ($busqueda !== ''): ?>
                            Resultados para “<?= e($busqueda) ?>”
                        <?php elseif ($finde): ?>
                            Este fin de semana
                        <?php else: ?>
                            Recitales
                        <?php endif; ?>
                    </h2>

                    <p class="text-secondary-custom mb-0">
                        <?php if ($verTodos): ?>
                            <?= count($concerts) ?> evento(s) encontrado(s)
                        <?php elseif ($hayMasEventos): ?>
                            Mostrando 12 recitales; hay más resultados
                        <?php else: ?>
                            <?= count($concerts) ?> evento(s) encontrado(s)
                        <?php endif; ?>
                        <a href="<?= e(url('/src/views/index.php#eventos')) ?>" class="ms-2">
                            <i class="bi bi-x-circle me-1"></i>Limpiar filtros
                        </a>
                    </p>

                <?php else: ?>

                    <h2 class="h3 fw-bold text-white mb-1 event-title-marquee" aria-label="Próximos Recitales">
                        <span class="event-title-marquee__track" aria-hidden="true">
                            <?php for ($repeticion = 0; $repeticion < 2; $repeticion++): ?>
                                <span class="event-title-marquee__group">
                                    <span class="event-title-marquee__item">PRÓXIMOS RECITALES</span>
                                    <span class="event-title-marquee__item event-title-marquee__item--secondary">
                                        <span class="event-title-marquee__separator" aria-hidden="true"></span>
                                        ENCONTRÁ TU PRÓXIMO SHOW
                                    </span>
                                </span>
                            <?php endfor; ?>
                        </span>
                    </h2>

                    <p class="text-secondary-custom mb-0">
                        Comprá tus entradas oficiales para los mejores shows
                    </p>

                <?php endif; ?>

            </div>


            <!-- FILTROS: chips de categoría + fin de semana + selector de ciudad -->

            <div
                class="filter-bar d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-3 mb-4">

                <!-- Chips -->
                <nav class="filter-chips" aria-label="Filtrar por categoría">

                    <!-- Chip "Todos": saca la categoría -->
                    <a href="<?= e(armarLink($busqueda, '', $ciudad, $finde, $verTodos)) ?>"
                        class="filter-chip <?= $categoria === '' ? 'active' : '' ?>">
                        Todos
                    </a>

                    <!-- Un chip por cada categoría de la lista -->
                    <?php foreach ($categorias as $slug => $nombre): ?>
                        <a href="<?= e(armarLink($busqueda, $slug, $ciudad, $finde, $verTodos)) ?>"
                            class="filter-chip <?= $categoria === $slug ? 'active' : '' ?>">
                            <?= e($nombre) ?>
                        </a>
                    <?php endforeach; ?>

                    <!-- Chip "Este fin de semana": se prende y se apaga -->
                    <a href="<?= e(armarLink($busqueda, $categoria, $ciudad, !$finde, $verTodos)) ?>"
                        class="filter-chip <?= $finde ? 'active' : '' ?>">
                        <i class="bi bi-calendar-week me-1" aria-hidden="true"></i>Este fin de semana
                    </a>

                </nav>


                <!-- Selector de ciudad (se envía solo al cambiar) -->
                <?php if (count($ciudades) > 0): ?>

                    <form action="<?= e(url('/src/views/index.php#eventos')) ?>" method="GET" class="filter-city-form">

                        <!-- Guardamos lo que ya estaba elegido -->
                        <?php if ($busqueda !== ''): ?>
                            <input type="hidden" name="q" value="<?= e($busqueda) ?>">
                        <?php endif; ?>

                        <?php if ($categoria !== ''): ?>
                            <input type="hidden" name="categoria" value="<?= e($categoria) ?>">
                        <?php endif; ?>

                        <?php if ($finde): ?>
                            <input type="hidden" name="finde" value="1">
                        <?php endif; ?>

                        <?php if ($verTodos): ?>
                            <input type="hidden" name="ver_todos" value="1">
                        <?php endif; ?>

                        <label for="filtro-ciudad" class="visually-hidden">Ciudad</label>

                        <div class="input-group">

                            <span class="input-group-text custom-input-text">
                                <i class="bi bi-geo-alt"></i>
                            </span>

                            <select id="filtro-ciudad" name="ciudad" class="form-select custom-input"
                                onchange="this.form.submit()">

                                <option value="">Todas las ciudades</option>

                                <?php foreach ($ciudades as $unaCiudad): ?>
                                    <option value="<?= e($unaCiudad) ?>" <?= $ciudad === $unaCiudad ? 'selected' : '' ?>>
                                        <?= e($unaCiudad) ?>
                                    </option>
                                <?php endforeach; ?>

                            </select>

                        </div>

                    </form>

                <?php endif; ?>

            </div>


            <!-- CARDS -->

            <?php if (!empty($concertsListado)): ?>
                <?php
                $cantidadEventos = count($concertsListado);
                $columnasEventos = $cantidadEventos === 3
                    ? 'col-lg-4'
                    : ($cantidadEventos <= 2 ? 'col-lg-6' : 'col-lg-3');
                ?>

                <div class="row g-4 <?= $cantidadEventos <= 2 ? 'justify-content-center' : '' ?>">

                    <?php foreach ($concertsListado as $concert): ?>

                        <?php
                        // Disponibilidad de entradas de este recital
                        $stock = (int) $concert['total_stock'];
                        $disponibles = (int) $concert['total_available'];
                        $estadoEntradas = estadoStock($disponibles, $stock);
                        $agotado = $estadoEntradas === 'agotado';
                        $ultimas = $estadoEntradas === 'pocas';
                        ?>

                        <div class="col-12 col-sm-6 <?= $columnasEventos ?>">

                            <article
                                class="card event-card h-100 bg-card-dark text-light border-0 rounded-4 overflow-hidden shadow">

                                <div class="position-relative overflow-hidden">

                                    <?php if (!empty($concert['image'])): ?>
                                        <img src="/<?= e($concert['image']) ?>" class="card-img-top event-img"
                                            alt="<?= e($concert['name']) ?>" loading="lazy">
                                        <div class="img-gradient-overlay"></div>
                                    <?php else: ?>
                                        <div class="event-img event-img--empty" role="img"
                                            aria-label="Imagen no disponible para <?= e($concert['name']) ?>">
                                            <i class="bi bi-music-note-beamed" aria-hidden="true"></i>
                                        </div>
                                    <?php endif; ?>

                                    <?php if (!empty($concert['genre'])): ?>
                                        <span class="badge bg-violet-neon position-absolute top-0 end-0 m-3 rounded-pill shadow-sm">
                                            <?= e($concert['genre']) ?>
                                        </span>
                                    <?php endif; ?>

                                    <?php if ($agotado): ?>
                                        <span class="badge text-bg-secondary position-absolute top-0 start-0 m-3 rounded-pill shadow-sm">
                                            Agotado
                                        </span>
                                    <?php elseif ($ultimas): ?>
                                        <span class="badge text-bg-warning position-absolute top-0 start-0 m-3 rounded-pill shadow-sm">
                                            ¡Últimas entradas!
                                        </span>
                                    <?php endif; ?>

                                </div>

                                <div class="card-body d-flex flex-column p-4 position-relative z-1">

                                    <h3 class="h5 card-title fw-bold text-white mb-2">
                                        <?= e($concert['name']) ?>
                                    </h3>

                                    <p class="text-secondary-custom small mb-2">
                                        <i class="bi bi-geo-alt me-1 text-primary-neon"></i>
                                        <?= e($concert['venue']) ?>, <?= e($concert['city']) ?>
                                    </p>

                                    <p class="text-secondary-custom small mb-2">
                                        <i class="bi bi-calendar-event me-1 text-primary-neon"></i>
                                        <?= e(formatFecha($concert['date'], $concert['time'])) ?>
                                    </p>

                                    <p class="small fw-semibold text-white mb-3">
                                        <i class="bi bi-ticket-perforated me-1 text-primary-neon"></i>
                                        <?php if ($concert['min_price'] !== null): ?>
                                            Desde $<?= number_format((float) $concert['min_price'], 0, ',', '.') ?>
                                        <?php else: ?>
                                            Sin entradas disponibles
                                        <?php endif; ?>
                                    </p>

                                    <div
                                        class="mt-auto d-flex justify-content-between align-items-center pt-3 border-top border-secondary-subtle">

                                        <span class="small fw-semibold text-white">
                                            <i class="bi bi-hourglass-split me-1 text-violet-neon"></i>
                                            <?= e(textoDias($concert['date'])) ?>
                                        </span>

                                        <a href="<?= e(url('/src/views/concerts/show.php?id=' . urlencode($concert['id']))) ?>"
                                            class="btn btn-sm btn-outline-violet rounded-pill px-3 stretched-link">
                                            <?= $agotado || $concert['min_price'] === null ? 'Ver detalle' : 'Ver entradas' ?>
                                        </a>

                                    </div>

                                </div>

                            </article>

                        </div>

                    <?php endforeach; ?>

                </div>

            <?php elseif ($errorCartelera): ?>

                <div class="alert alert-warning" role="alert">
                    No pudimos cargar los recitales en este momento. Probá de nuevo más tarde.
                </div>

            <?php elseif ($hayFiltros): ?>

                <div class="text-center py-5">

                    <i class="bi bi-search fs-1 text-violet-neon"></i>

                    <p class="text-white fw-semibold mt-3 mb-1">
                        No encontramos eventos con esos filtros
                    </p>

                    <p class="text-secondary-custom mb-3">
                        Probá con otra búsqueda, otra categoría u otra ciudad.
                    </p>

                    <a href="<?= e(url('/src/views/index.php#eventos')) ?>" class="btn btn-outline-violet rounded-pill px-4">
                        Ver todos los recitales
                    </a>

                </div>

            <?php else: ?>

                <p class="text-secondary-custom mb-0">No hay recitales cargados por el momento.</p>

            <?php endif; ?>

            <?php if ($hayMasEventos): ?>
                <div class="text-center mt-5">
                    <a href="<?= e(armarLink($busqueda, $categoria, $ciudad, $finde, true)) ?>"
                        class="btn btn-outline-violet rounded-pill px-4">
                        Ver todos los recitales <i class="bi bi-arrow-down ms-1"></i>
                    </a>
                </div>
            <?php elseif ($verTodos && count($concerts) > 12): ?>
                <div class="text-center mt-5">
                    <a href="<?= e(armarLink($busqueda, $categoria, $ciudad, $finde)) ?>"
                        class="btn btn-outline-violet rounded-pill px-4">
                        Mostrar menos <i class="bi bi-arrow-up ms-1"></i>
                    </a>
                </div>
            <?php endif; ?>

        </div>

    </section>

<!-- =====================================================
         ARTISTAS DESTACADOS
    ====================================================== -->

    <?php if (!empty($artistas)): ?>
        <section id="artistas" class="py-5 home-section-divider">
            <div class="container-xl">

                <div class="mb-4">
                    <h2 class="h3 fw-bold text-white mb-1">Artistas destacados</h2>
                    <p class="text-secondary-custom mb-0">Los que más shows tienen próximamente</p>
                </div>

                <div class="row g-4 justify-content-center">
                    <?php foreach ($artistas as $artista): ?>
                        <div class="col-6 col-md-3 text-center">
                            <a href="<?= e(armarLink($artista['name'], '', '')) ?>"
                                class="text-decoration-none d-block">

                                <div class="ratio ratio-1x1 rounded-circle overflow-hidden mx-auto mb-3 border border-secondary-subtle featured-artist-image">
                                    <?php if (!empty($artista['image'])): ?>
                                        <img src="/<?= e($artista['image']) ?>" alt="<?= e($artista['name']) ?>"
                                            class="w-100 h-100 object-fit-cover" loading="lazy">
                                    <?php else: ?>
                                        <span class="d-flex align-items-center justify-content-center fs-2 text-violet-neon">
                                            <i class="bi bi-music-note-beamed" aria-hidden="true"></i>
                                        </span>
                                    <?php endif; ?>
                                </div>

                                <h3 class="h6 fw-bold text-white mb-1"><?= e($artista['name']) ?></h3>
                                <p class="small text-secondary-custom mb-0">
                                    <?= (int) $artista['shows'] === 1 ? '1 show' : (int) $artista['shows'] . ' shows' ?>
                                </p>

                            </a>
                        </div>
                    <?php endforeach; ?>
                </div>

            </div>
        </section>
    <?php endif; ?>


    <!-- =====================================================
         LUGARES POPULARES
    ====================================================== -->

    <?php if (!empty($lugares)): ?>
        <section id="lugares" class="py-5 home-section-divider">
            <div class="container-xl">

                <div class="mb-4">
                    <h2 class="h3 fw-bold text-white mb-1">Lugares populares</h2>
                    <p class="text-secondary-custom mb-0">Donde se vienen los próximos shows</p>
                </div>

                <div class="row g-4">
                    <?php foreach ($lugares as $lugar): ?>
                        <div class="col-12 col-sm-6 col-lg-3">
                            <div
                                class="step-card p-4 rounded-4 text-center h-100 border border-secondary-subtle position-relative">

                                <div class="step-icon mb-3 fs-2 text-blue-neon">
                                    <i class="bi bi-geo-alt" aria-hidden="true"></i>
                                </div>

                                <h3 class="h5 text-white fw-semibold mb-1"><?= e($lugar['name']) ?></h3>
                                <p class="small text-secondary-custom mb-2"><?= e($lugar['city']) ?></p>
                                <p class="small text-white mb-3">
                                    <?= (int) $lugar['shows'] === 1 ? '1 show próximo' : (int) $lugar['shows'] . ' shows próximos' ?>
                                </p>

                                <a href="<?= e(armarLink($lugar['name'], '', '')) ?>"
                                    class="btn btn-sm btn-outline-violet rounded-pill px-3 stretched-link">
                                    Ver shows
                                </a>

                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>

            </div>
        </section>
    <?php endif; ?>


    <!-- =====================================================
         NOTICIAS
    ====================================================== -->

    <section id="noticias" class="py-5 home-section-divider">
        <div class="container-xl">
            <div class="d-flex flex-wrap justify-content-between align-items-end gap-3 mb-4">
                <div>
                    <h2 class="h3 fw-bold text-white mb-1">Noticias</h2>
                    <p class="text-secondary-custom mb-0">Novedades de AllTicket y del mundo de la música.</p>
                </div>
                <?php if (isAdmin()): ?>
                    <a href="<?= e(url('/src/controllers/posts/form.php')) ?>"
                        class="btn btn-outline-violet rounded-pill px-4">Publicar noticia</a>
                <?php endif; ?>
            </div>

            <?php if (!empty($noticias)): ?>
                <div class="row g-4">
                    <?php foreach ($noticias as $noticia): ?>
                        <?php $resumenNoticia = trim(strip_tags((string) $noticia['content'])); ?>
                        <div class="col-12 col-md-6 col-lg-4">
                            <article class="card h-100 bg-card-dark text-light border border-secondary-subtle rounded-4 shadow">
                                <div class="card-body d-flex flex-column p-4">
                                    <p class="small text-secondary-custom mb-2">
                                        <i class="bi bi-calendar-event me-1"></i>
                                        <?= e(date('d/m/Y', strtotime($noticia['created_at']))) ?>
                                    </p>
                                    <h3 class="h5 fw-bold text-white"><?= e($noticia['title']) ?></h3>
                                    <p class="text-secondary-custom mb-4">
                                        <?= e(mb_strimwidth($resumenNoticia, 0, 180, '…', 'UTF-8')) ?>
                                    </p>
                                    <a href="<?= e(url('/src/controllers/posts/show.php?id=' . urlencode($noticia['id']))) ?>"
                                        class="btn btn-sm btn-outline-violet rounded-pill px-3 mt-auto align-self-start">
                                        Leer noticia
                                    </a>
                                </div>
                            </article>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php else: ?>
                <p class="text-secondary-custom mb-0">Todavía no hay noticias publicadas.</p>
            <?php endif; ?>
        </div>
    </section>

<!-- =====================================================
         CÓMO FUNCIONA
    ====================================================== -->

    <section id="como-funciona" class="py-5 home-section-divider position-relative">

        <div class="container-xl">

            <div class="text-center max-w-2xl mx-auto mb-5">

                <h2 class="h3 fw-bold text-white mb-2">
                    ¿Cómo funciona AllTicket?
                </h2>

                <p class="text-secondary-custom">
                    Conseguí tus entradas en simples pasos de manera segura y directa
                </p>

            </div>

            <div class="row g-4">

                <!-- PASO 01 -->
                <div class="col-12 col-md-6 col-lg-3">
                    <div
                        class="step-card p-4 rounded-4 text-center h-100 border border-secondary-subtle position-relative">
                        <div class="step-number text-gradient fw-bold display-6 mb-2">01</div>
                        <div class="step-icon mb-3 fs-2 text-violet-neon">
                            <i class="bi bi-search-heart"></i>
                        </div>
                        <h3 class="h5 text-white fw-semibold mb-2">Buscá tu evento</h3>
                        <p class="small text-secondary-custom mb-0">
                            Explorá la cartelera de conciertos y recitales
                            filtrando por género o ciudad.
                        </p>
                    </div>
                </div>

                <!-- PASO 02 -->
                <div class="col-12 col-md-6 col-lg-3">
                    <div
                        class="step-card p-4 rounded-4 text-center h-100 border border-secondary-subtle position-relative">
                        <div class="step-number text-gradient fw-bold display-6 mb-2">02</div>
                        <div class="step-icon mb-3 fs-2 text-blue-neon">
                            <i class="bi bi-ticket-perforated"></i>
                        </div>
                        <h3 class="h5 text-white fw-semibold mb-2">Elegí tus entradas</h3>
                        <p class="small text-secondary-custom mb-0">
                            Seleccioná la ubicación y la cantidad de tickets
                            que querés adquirir.
                        </p>
                    </div>
                </div>

                <!-- PASO 03 -->
                <div class="col-12 col-md-6 col-lg-3">
                    <div
                        class="step-card p-4 rounded-4 text-center h-100 border border-secondary-subtle position-relative">
                        <div class="step-number text-gradient fw-bold display-6 mb-2">03</div>
                        <div class="step-icon mb-3 fs-2 text-violet-neon">
                            <i class="bi bi-credit-card"></i>
                        </div>
                        <h3 class="h5 text-white fw-semibold mb-2">Comprá seguro</h3>
                        <p class="small text-secondary-custom mb-0">
                            Pagá de forma rápida con múltiples medios de pago
                            homologados.
                        </p>
                    </div>
                </div>

                <!-- PASO 04 -->
                <div class="col-12 col-md-6 col-lg-3">
                    <div
                        class="step-card p-4 rounded-4 text-center h-100 border border-secondary-subtle position-relative">
                        <div class="step-number text-gradient fw-bold display-6 mb-2">04</div>
                        <div class="step-icon mb-3 fs-2 text-blue-neon">
                            <i class="bi bi-qr-code-scan"></i>
                        </div>
                        <h3 class="h5 text-white fw-semibold mb-2">Disfrutá el show</h3>
                        <p class="small text-secondary-custom mb-0">
                            Recibí tu ticket digital con código QR directo
                            en tu celular o e-mail.
                        </p>
                    </div>
                </div>

            </div>

        </div>

    </section>


    <!-- =====================================================
         GARANTÍAS + MEDIOS DE PAGO
    ====================================================== -->

    <section id="garantias" class="py-5 home-section-divider">

        <div class="container-xl">

            <div class="text-center max-w-2xl mx-auto mb-5">

                <h2 class="h3 fw-bold text-white mb-2">
                    Comprá con tranquilidad
                </h2>

                <p class="text-secondary-custom">
                    Todo lo que necesitás para ir al show sin preocupaciones
                </p>

            </div>

            <div class="row g-4">

                <?php foreach ($garantias as $garantia): ?>

                    <div class="col-12 col-sm-6 col-lg-3">

                        <div
                            class="step-card p-4 rounded-4 text-center h-100 border border-secondary-subtle position-relative">

                            <div class="step-icon mb-3 fs-2 text-violet-neon">
                                <i class="bi <?= e($garantia['icono']) ?>"></i>
                            </div>

                            <h3 class="h5 text-white fw-semibold mb-2">
                                <?= e($garantia['titulo']) ?>
                            </h3>

                            <p class="small text-secondary-custom mb-0">
                                <?= e($garantia['texto']) ?>
                            </p>

                        </div>

                    </div>

                <?php endforeach; ?>

            </div>

            <!-- Medios de pago -->
            <?php if (!empty($mediosPago)): ?>
                <div class="payment-methods mt-5" aria-labelledby="payment-methods-title">
                    <div class="text-center mb-4">
                        <h3 id="payment-methods-title" class="h5 fw-semibold text-white mb-1">Medios de pago</h3>
                        <p class="small text-secondary-custom mb-0">Elegí la opción que te resulte más cómoda</p>
                    </div>

                    <div class="payment-slider" role="region" aria-label="Medios de pago aceptados">
                        <div class="payment-slider__track">
                            <?php for ($copiaMedios = 0; $copiaMedios < 2; $copiaMedios++): ?>
                                <ul class="payment-slider__group list-unstyled mb-0"
                                    <?= $copiaMedios > 0 ? 'aria-hidden="true"' : '' ?>>
                                    <?php foreach ($mediosPago as $medio): ?>
                                        <li class="payment-slider__item">
                                            <span class="payment-method">
                                                <span class="payment-method__mark payment-method__mark--<?= e($medio['clave']) ?>">
                                                    <?php if ($medio['nombre'] === 'Mastercard'): ?>
                                                        <span class="mastercard-symbol" aria-hidden="true">
                                                            <span></span><span></span>
                                                        </span>
                                                    <?php else: ?>
                                                        <?= e($medio['marca']) ?>
                                                    <?php endif; ?>
                                                </span>
                                                <span class="payment-method__name">
                                                    <i class="bi <?= e($medio['icono']) ?>" aria-hidden="true"></i>
                                                    <?= e($medio['nombre']) ?>
                                                </span>
                                            </span>
                                        </li>
                                    <?php endforeach; ?>
                                </ul>
                            <?php endfor; ?>
                        </div>
                    </div>
                </div>
            <?php endif; ?>

        </div>

    </section>

    <!-- =====================================================
         NEWSLETTER
    ====================================================== -->

    <section id="newsletter" class="py-5 home-section-divider">
        <div class="container-xl">
            <div class="step-card p-4 p-lg-5 rounded-4 border border-secondary-subtle text-center mx-auto newsletter-card">

                <div class="step-icon mb-3 fs-2 text-violet-neon">
                    <i class="bi bi-bell" aria-hidden="true"></i>
                </div>

                <h2 class="h3 fw-bold text-white mb-2">No te pierdas ningún show</h2>
                <p class="text-secondary-custom mb-4">
                    Dejanos tu e-mail y te avisamos cuando salgan nuevas fechas y entradas.
                </p>

                <?php if ($estadoNewsletter === 'ok'): ?>
                    <div class="alert alert-success py-2" role="status">
                        ¡Listo! Ya estás en la lista.
                    </div>
                <?php elseif ($estadoNewsletter === 'invalid'): ?>
                    <div class="alert alert-warning py-2" role="alert">
                        Revisá el e-mail: no parece válido.
                    </div>
                <?php elseif ($estadoNewsletter === 'error'): ?>
                    <div class="alert alert-danger py-2" role="alert">
                        No pudimos guardar tu e-mail. Probá de nuevo más tarde.
                    </div>
                <?php elseif ($estadoNewsletter === 'rate_limited'): ?>
                    <div class="alert alert-warning py-2" role="alert">
                        Hiciste demasiados intentos. Esperá unos minutos y volvé a probar.
                    </div>
                <?php endif; ?>

                <form action="<?= e(url('/src/controllers/newsletter/subscribe.php')) ?>" method="POST"
                    class="row g-2 justify-content-center">

                    <input type="hidden" name="csrf_token" value="<?= e(csrfToken()) ?>">

                    <div class="col-12 col-md">
                        <label for="newsletter-email" class="visually-hidden">E-mail</label>
                        <input type="email" id="newsletter-email" name="email" class="form-control custom-input"
                            placeholder="tu@email.com" maxlength="254" autocomplete="email" required>
                    </div>

                    <div class="col-12 col-md-auto">
                        <button type="submit" class="btn btn-violet-glow btn-home-gradient rounded-pill px-4">Avisame</button>
                    </div>

                </form>

            </div>
        </div>
    </section>


    <!-- =====================================================
         PREGUNTAS FRECUENTES (acordeón de Bootstrap)
    ====================================================== -->

    <section id="preguntas-frecuentes" class="py-5 home-section-divider">

        <div class="container-xl">

            <div class="text-center max-w-2xl mx-auto mb-5">

                <h2 class="h3 fw-bold text-white mb-2">
                    Preguntas frecuentes
                </h2>

                <p class="text-secondary-custom">
                    Respondemos las dudas más comunes antes de comprar
                </p>

            </div>

            <div class="accordion faq-accordion mx-auto" id="faqAccordion">

                <?php foreach ($preguntas as $numero => $item): ?>

                    <?php $idRespuesta = "faq-" . $numero; ?>

                    <div class="accordion-item">

                        <h3 class="accordion-header">
                            <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse"
                                data-bs-target="#<?= $idRespuesta ?>" aria-expanded="false"
                                aria-controls="<?= $idRespuesta ?>">
                                <?= e($item['pregunta']) ?>
                            </button>
                        </h3>

                        <div id="<?= $idRespuesta ?>" class="accordion-collapse collapse" data-bs-parent="#faqAccordion">

                            <div class="accordion-body">
                                <?= e($item['respuesta']) ?>
                            </div>

                        </div>

                    </div>

                <?php endforeach; ?>

            </div>

        </div>

    </section>


    <!-- =====================================================
         BANNER PARA ORGANIZADORES
    ====================================================== -->

    <section id="organizadores" class="py-5 home-section-divider">
        <div class="container-xl">
            <div
                class="step-card p-4 p-lg-5 rounded-4 border border-secondary-subtle d-flex flex-column flex-lg-row justify-content-between align-items-lg-center gap-4">

                <div>
                    <h2 class="h3 fw-bold text-white mb-2">¿Organizás eventos?</h2>
                    <p class="text-secondary-custom mb-0">
                        Publicá tu show en AllTicket y vendé entradas oficiales con código QR.
                    </p>
                </div>

                <a href="mailto:<?= e($emailSoporte) ?>?subject=<?= rawurlencode('Quiero publicar mi evento en AllTicket') ?>"
                    class="btn btn-violet-glow rounded-pill px-4 align-self-lg-center">
                    Contactanos <i class="bi bi-arrow-right ms-2" aria-hidden="true"></i>
                </a>

            </div>
        </div>
    </section>
</main>
<?php if ($hayFiltros): ?>
    <script>
        window.addEventListener("load", function () {
            var seccion = document.getElementById("eventos");
            if (seccion) {
                seccion.scrollIntoView({ behavior: "smooth", block: "start" });
            }
        });
    </script>
<?php endif; ?>

<!-- Números que cuentan al aparecer -->
<script src="<?= e(url('/assets/js/contador.js')) ?>"></script>

<?php
$pageContent = ob_get_clean();
require __DIR__ . '/_layouts/layout.php';
