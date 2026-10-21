<?php
require_once __DIR__ . '/../config/bootstrap.php';


// ============================================================
// 1. LEEMOS LO QUE ESCRIBIÓ O ELIGIÓ LA PERSONA (viene en la URL)
//    ?q=duki          -> lo de la lupa
//    ?categoria=rock  -> el menú Categorías y los chips
//    ?ciudad=Rosario  -> el selector de ciudad
// ============================================================

$busqueda = "";
if (isset($_GET['q'])) {
    $busqueda = trim($_GET['q']);
}

$categoria = "";
if (isset($_GET['categoria'])) {
    $categoria = $_GET['categoria'];
}

$ciudad = "";
if (isset($_GET['ciudad'])) {
    $ciudad = trim($_GET['ciudad']);
}


// ============================================================
// 2. LISTA DE CATEGORÍAS
//    A la izquierda el nombre que va en la URL,
//    a la derecha el nombre que ve la persona.
// ============================================================

$categorias = [
    "rock" => "Rock",
    "reggaeton" => "Reggaetón / Trap",
    "pop" => "Pop",
    "electronica" => "Electrónica"
];

// Si escribieron una categoría que no existe, la borramos
if ($categoria != "" && !array_key_exists($categoria, $categorias)) {
    $categoria = "";
}

// ============================================================
// LISTA DE GARANTÍAS (la sección de confianza)
// Cada una tiene un ícono, un título y un texto.
// ============================================================

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
        "texto" => "Escribinos a soporte@allticket.com y te ayudamos."
    ]
];


// ============================================================
// LISTA DE PREGUNTAS FRECUENTES
// Para agregar una pregunta nueva, copiá un bloque y cambiá el texto.
// OJO: revisá que las respuestas coincidan con las reglas reales
// de AllTicket (sobre todo pagos y cancelaciones).
// ============================================================

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
        "respuesta" => "Escribinos a soporte@allticket.com y te respondemos lo antes posible."
    ]
];


// ¿Hay algún filtro puesto? (true = sí, false = no)
$hayFiltros = false;
if ($busqueda != "" || $categoria != "" || $ciudad != "") {
    $hayFiltros = true;
}


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

// Arma el link de los chips, guardando lo que ya estaba elegido
function armarLink($busqueda, $categoria, $ciudad)
{
    $link = "/src/views/index.php?";

    if ($busqueda != "") {
        $link = $link . "q=" . urlencode($busqueda) . "&";
    }

    if ($categoria != "") {
        $link = $link . "categoria=" . urlencode($categoria) . "&";
    }

    if ($ciudad != "") {
        $link = $link . "ciudad=" . urlencode($ciudad) . "&";
    }

    return $link . "#eventos";
}


// ============================================================
// 4. TRAEMOS LOS RECITALES DE LA BASE DE DATOS
// ============================================================

$concerts = [];
$ciudades = [];

try {

    // Consulta base: eventos activos que todavía no pasaron
    $sql = "SELECT
                c.id,
                c.name,
                c.image,
                c.genre,
                c.date,
                c.time,
                v.name AS venue,
                v.city
            FROM concerts c
            JOIN venues v ON c.venue_id = v.id
            WHERE c.status = 'active'
              AND c.date >= CURDATE()";

    // Lista de datos que reemplazan a los :nombres de la consulta
    $datos = [];

    // Si buscó algo con la lupa
    if ($busqueda != "") {
        $sql = $sql . " AND (c.name LIKE :q1 OR v.name LIKE :q2 OR c.genre LIKE :q3)";
        $datos[":q1"] = "%" . $busqueda . "%";
        $datos[":q2"] = "%" . $busqueda . "%";
        $datos[":q3"] = "%" . $busqueda . "%";
    }

    // Si eligió una categoría
    if ($categoria != "") {
        $sql = $sql . " AND c.genre LIKE :genero";
        $datos[":genero"] = "%" . textoGenero($categoria) . "%";
    }

    // Si eligió una ciudad
    if ($ciudad != "") {
        $sql = $sql . " AND v.city = :ciudad";
        $datos[":ciudad"] = $ciudad;
    }

    // Final de la consulta
    $sql = $sql . " ORDER BY c.date ASC
                    LIMIT 12";

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
    $concerts = [];
    $ciudades = [];
}

ob_start();
?>

<!-- =========================================================
     ESTILOS Y RECURSOS ESPECÍFICOS DEL INDEX
========================================================= -->

<link rel="stylesheet" href="/assets/css/index.css">

<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">


<!-- =========================================================
     CONTENIDO PRINCIPAL
     (el fondo ambiental y el header/navbar viven únicamente en
     _layouts/layout.php)
========================================================= -->

<main class="bg-main-dark text-light min-vh-100 position-relative z-1 overflow-hidden">


    <!-- =====================================================
         HERO FULL-BLEED
    ====================================================== -->

    <section class="hero-fullbleed position-relative" id="hero">

        <!-- IMAGEN DE FONDO -->

        <img class="hero-fullbleed-bg" src="/assets/img/hero.png" alt="" aria-hidden="true" />

        <div class="hero-fullbleed-overlay-top" aria-hidden="true"></div>
        <div class="hero-fullbleed-overlay-bottom" aria-hidden="true"></div>


        <!-- CONTENIDO DEL HERO -->

        <div class="hero-fullbleed-content hero-fullbleed-content--center container-xl">

            <h1 class="hero-title-v2 mb-4">
                <span class="hero-line-1">Momentos inolvidables</span>
                <br>
                <span class="hero-line-2">a solo un click.</span>
            </h1>

            <p class="lead hero-description mb-4 fs-5">
                Sin reventa, sin filas, sin sorpresas.
                Comprá tus entradas oficiales para los shows
                que no te querés perder.
            </p>

            <div class="d-flex flex-wrap justify-content-center gap-3 mb-2">

                <a href="#eventos"
                    class="btn btn-violet-glow btn-lg px-5 rounded-pill d-inline-flex align-items-center">
                    Buscar eventos
                    <i class="bi bi-arrow-right ms-2"></i>
                </a>

            </div>

            <div class="mb-5">
                <a href="#como-funciona" class="hero-secondary-link">
                    Cómo funciona
                </a>
            </div>

            <div class="d-flex flex-wrap justify-content-center gap-4 gap-lg-5">

                <div>
                    <div class="hero-stat-value" data-contador="50" data-prefijo="+" data-sufijo="K">+50K</div>
                    <div class="hero-stat-label">
                        entradas vendidas
                    </div>
                </div>

                <div>
                    <div class="hero-stat-value" data-contador="300" data-sufijo="+">300+</div>
                    <div class="hero-stat-label">
                        eventos realizados
                    </div>
                </div>

            </div>

        </div>

    </section>



    <!-- =====================================================
         EVENTOS
    ====================================================== -->

    <section id="eventos" class="py-5">

        <div class="container-xl">


            <!-- ENCABEZADO -->

            <div class="mb-4">

                <?php if ($hayFiltros) { ?>

                    <h2 class="h3 fw-bold text-white mb-1">
                        <?php if ($busqueda != "") { ?>
                            Resultados para “<?= e($busqueda) ?>”
                        <?php } else { ?>
                            Recitales
                        <?php } ?>
                    </h2>

                    <p class="text-secondary-custom mb-0">
                        <?= count($concerts) ?> evento(s) encontrado(s)
                        <a href="/src/views/index.php#eventos" class="ms-2">
                            <i class="bi bi-x-circle me-1"></i>Limpiar filtros
                        </a>
                    </p>

                <?php } else { ?>

                    <h2 class="h3 fw-bold text-white mb-1">
                        Próximos Recitales
                    </h2>

                    <p class="text-secondary-custom mb-0">
                        Comprá tus entradas oficiales para los mejores shows
                    </p>

                <?php } ?>

            </div>


            <!-- FILTROS: chips de categoría + selector de ciudad -->

            <div
                class="filter-bar d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-3 mb-4">

                <!-- Chips de categoría -->
                <nav class="filter-chips" aria-label="Filtrar por categoría">

                    <!-- Chip "Todos": saca la categoría -->
                    <a href="<?= e(armarLink($busqueda, "", $ciudad)) ?>"
                        class="filter-chip <?php if ($categoria == "") {
                            echo "active";
                        } ?>">
                        Todos
                    </a>

                    <!-- Un chip por cada categoría de la lista -->
                    <?php foreach ($categorias as $slug => $nombre) { ?>

                        <a href="<?= e(armarLink($busqueda, $slug, $ciudad)) ?>"
                            class="filter-chip <?php if ($categoria == $slug) {
                                echo "active";
                            } ?>">
                            <?= e($nombre) ?>
                        </a>

                    <?php } ?>

                </nav>


                <!-- Selector de ciudad (se envía solo al cambiar) -->
                <?php if (count($ciudades) > 0) { ?>

                    <form action="/src/views/index.php#eventos" method="GET" class="filter-city-form">

                        <!-- Guardamos lo que ya estaba elegido -->
                        <?php if ($busqueda != "") { ?>
                            <input type="hidden" name="q" value="<?= e($busqueda) ?>">
                        <?php } ?>

                        <?php if ($categoria != "") { ?>
                            <input type="hidden" name="categoria" value="<?= e($categoria) ?>">
                        <?php } ?>

                        <label for="filtro-ciudad" class="visually-hidden">Ciudad</label>

                        <div class="input-group">

                            <span class="input-group-text custom-input-text">
                                <i class="bi bi-geo-alt"></i>
                            </span>

                            <select id="filtro-ciudad" name="ciudad" class="form-select custom-input"
                                onchange="this.form.submit()">

                                <option value="">Todas las ciudades</option>

                                <?php foreach ($ciudades as $unaCiudad) { ?>
                                    <option value="<?= e($unaCiudad) ?>" <?php if ($ciudad == $unaCiudad) {
                                          echo "selected";
                                      } ?>>
                                        <?= e($unaCiudad) ?>
                                    </option>
                                <?php } ?>

                            </select>

                        </div>

                    </form>

                <?php } ?>

            </div>


            <!-- CARDS -->

            <?php if (!empty($concerts)): ?>

                <div class="row g-4">

                    <?php foreach ($concerts as $concert): ?>

                        <div class="col-12 col-sm-6 col-lg-3">

                            <article
                                class="card event-card h-100 bg-card-dark text-light border-0 rounded-4 overflow-hidden shadow">

                                <div class="position-relative overflow-hidden">

                                    <img src="/<?= e($concert['image']) ?>" class="card-img-top event-img"
                                        alt="<?= e($concert['name']) ?>" loading="lazy">

                                    <div class="img-gradient-overlay"></div>

                                    <?php if (!empty($concert['genre'])): ?>
                                        <span class="badge bg-violet-neon position-absolute top-0 end-0 m-3 rounded-pill shadow-sm">
                                            <?= e($concert['genre']) ?>
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

                                    <p class="text-secondary-custom small mb-3">
                                        <i class="bi bi-calendar-event me-1 text-primary-neon"></i>
                                        <?= e(formatFecha($concert['date'], $concert['time'])) ?>
                                    </p>

                                    <div
                                        class="mt-auto d-flex justify-content-between align-items-center pt-3 border-top border-secondary-subtle">

                                        <span class="small fw-semibold text-white">
                                            <i class="bi bi-hourglass-split me-1 text-violet-neon"></i>
                                            <?= e(textoDias($concert['date'])) ?>
                                        </span>

                                        <!-- stretched-link: toda la tarjeta es clickeable -->
                                        <a href="/src/views/concerts/show.php?id=<?= urlencode($concert['id']) ?>"
                                            class="btn btn-sm btn-outline-violet rounded-pill px-3 stretched-link">
                                            Ver entradas
                                        </a>

                                    </div>

                                </div>

                            </article>

                        </div>

                    <?php endforeach; ?>

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

                    <a href="/src/views/index.php#eventos" class="btn btn-outline-violet rounded-pill px-4">
                        Ver todos los recitales
                    </a>

                </div>

            <?php else: ?>

                <p class="text-secondary-custom mb-0">No hay recitales cargados por el momento.</p>

            <?php endif; ?>

        </div>

    </section>



    <!-- =====================================================
         CÓMO FUNCIONA
    ====================================================== -->

    <section id="como-funciona" class="py-5 border-top border-dark-subtle position-relative">

        <div class="container-xl">


            <!-- ENCABEZADO -->

            <div class="text-center max-w-2xl mx-auto mb-5">

                <h2 class="h3 fw-bold text-white mb-2">
                    ¿Cómo funciona AllTicket?
                </h2>

                <p class="text-secondary-custom">
                    Conseguí tus entradas en simples pasos de manera segura y directa
                </p>

            </div>


            <!-- PASOS -->

            <div class="row g-4">


                <!-- PASO 01 -->

                <div class="col-12 col-md-6 col-lg-3">

                    <div
                        class="step-card p-4 rounded-4 text-center h-100 border border-secondary-subtle position-relative">

                        <div class="step-number text-gradient fw-bold display-6 mb-2">
                            01
                        </div>

                        <div class="step-icon mb-3 fs-2 text-violet-neon">
                            <i class="bi bi-search-heart"></i>
                        </div>

                        <h4 class="h5 text-white fw-semibold mb-2">
                            Buscá tu evento
                        </h4>

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

                        <div class="step-number text-gradient fw-bold display-6 mb-2">
                            02
                        </div>

                        <div class="step-icon mb-3 fs-2 text-blue-neon">
                            <i class="bi bi-ticket-perforated"></i>
                        </div>

                        <h4 class="h5 text-white fw-semibold mb-2">
                            Elegí tus entradas
                        </h4>

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

                        <div class="step-number text-gradient fw-bold display-6 mb-2">
                            03
                        </div>

                        <div class="step-icon mb-3 fs-2 text-violet-neon">
                            <i class="bi bi-credit-card"></i>
                        </div>

                        <h4 class="h5 text-white fw-semibold mb-2">
                            Comprá seguro
                        </h4>

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

                        <div class="step-number text-gradient fw-bold display-6 mb-2">
                            04
                        </div>

                        <div class="step-icon mb-3 fs-2 text-blue-neon">
                            <i class="bi bi-qr-code-scan"></i>
                        </div>

                        <h4 class="h5 text-white fw-semibold mb-2">
                            Disfrutá el show
                        </h4>

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
         GARANTÍAS
    ====================================================== -->

    <section id="garantias" class="py-5 border-top border-dark-subtle">

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

                <?php foreach ($garantias as $garantia) { ?>

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

                <?php } ?>

            </div>

        </div>

    </section>



    <!-- =====================================================
         PREGUNTAS FRECUENTES (acordeón de Bootstrap)
    ====================================================== -->

    <section id="preguntas-frecuentes" class="py-5 border-top border-dark-subtle">

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

                <?php foreach ($preguntas as $numero => $item) { ?>

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

                <?php } ?>

            </div>

        </div>

    </section>

</main>
<?php if ($hayFiltros) { ?>
    <script>
        window.addEventListener("load", function () {
            var seccion = document.getElementById("eventos");
            if (seccion) {
                seccion.scrollIntoView({ behavior: "smooth", block: "start" });
            }
        });
    </script>
<?php } ?>
<!-- Números que cuentan al aparecer (+50K, 300+) -->
<script src="/assets/js/contador.js"></script>

<?php
$pageContent = ob_get_clean();
include('./_layouts/layout.php');
