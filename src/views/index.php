<?php
require_once __DIR__ . '/../config/bootstrap.php';


// ============================================================
// RECITALES DESTACADOS
// Traemos los próximos eventos con su lugar y el precio más
// bajo disponible de cada uno.
// ============================================================

$concerts = [];

try {
    $sql = "SELECT
                c.id,
                c.name,
                c.image,
                c.genre,
                c.date,
                c.time,
                v.name AS venue,
                v.city,
                MIN(t.price) AS price
            FROM concerts c
            JOIN venues v ON c.venue_id = v.id
            LEFT JOIN tickets t ON t.concert_id = c.id AND t.available > 0
            WHERE c.status = 'active'
              AND c.date >= CURDATE()
            GROUP BY c.id, c.name, c.image, c.genre, c.date, c.time, v.name, v.city
            ORDER BY c.date ASC
            LIMIT 5";

    $stmt = $pdo->prepare($sql);
    $stmt->execute();
    $concerts = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    // Si la base falla, el index se muestra igual, sólo sin recitales
    $concerts = [];
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
                    <div class="hero-stat-value">+50K</div>
                    <div class="hero-stat-label">
                        entradas vendidas
                    </div>
                </div>

                <div>
                    <div class="hero-stat-value">300+</div>
                    <div class="hero-stat-label">
                        eventos realizados
                    </div>
                </div>

            </div>

        </div>

    </section>



    <!-- =====================================================
         EVENTOS DESTACADOS
    ====================================================== -->

    <section id="eventos" class="py-5">

        <div class="container-xl">


            <!-- ENCABEZADO -->

            <div class="d-flex justify-content-between align-items-center mb-4">

                <div>

                    <h2 class="h3 fw-bold text-white mb-1">
                        Próximos Recitales
                    </h2>

                    <p class="text-secondary-custom mb-0">
                        Comprá tus entradas oficiales para los mejores shows
                    </p>

                </div>

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

                                        <span class="fs-5 fw-bold text-white">
                                            <?= e(formatPrecio($concert['price'])) ?>
                                        </span>

                                        <a href="/src/views/concerts/show.php?id=<?= urlencode($concert['id']) ?>"
                                            class="btn btn-sm btn-outline-violet rounded-pill px-3">
                                            Comprar
                                        </a>

                                    </div>

                                </div>

                            </article>

                        </div>

                    <?php endforeach; ?>

                </div>

            <?php else: ?>

                <p class="text-secondary-custom mb-0">No hay recitales cargados por el momento.</p>

            <?php endif; ?>

        </div>

    </section>
    <!-- FIX: este </section> faltaba. Antes #como-funciona quedaba
         anidado adentro de #eventos (y heredaba su py-5). -->



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

</main>

<?php
$pageContent = ob_get_clean();
include('./_layouts/layout.php');