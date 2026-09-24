<?php
ob_start();
?>

<!-- =========================================================
     ESTILOS Y RECURSOS ESPECÍFICOS DEL INDEX
========================================================= -->

<link
    rel="stylesheet"
    href="/assets/css/index.css"
>

<link
    rel="stylesheet"
    href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css"
>


<!-- =========================================================
     FONDO AMBIENTAL
========================================================= -->

<div
    class="ambient-glow-container"
    aria-hidden="true"
>

    <div class="glow-orb orb-violet"></div>
    <div class="glow-orb orb-blue"></div>
    <div class="glow-orb orb-violet-secondary"></div>

</div>


<!-- =========================================================
     CONTENIDO PRINCIPAL
========================================================= -->

<main
    class="bg-main-dark text-light min-vh-100 position-relative z-1 overflow-hidden"
>


    <!-- =====================================================
         HERO FULL-BLEED
         (el header/navbar vive únicamente en _layouts/layout.php,
         se eliminó el <header class="site-header"> que estaba
         duplicado acá adentro)
    ====================================================== -->

    <section
        class="hero-fullbleed position-relative"
        id="hero"
    >

        <!-- IMAGEN DE FONDO -->

        <img
            class="hero-fullbleed-bg"
            src="<?php echo "/assets/img/hero.png"; ?>"
            alt=""
            aria-hidden="true"
        />

        <div class="hero-fullbleed-overlay-top" aria-hidden="true"></div>
        <div class="hero-fullbleed-overlay-bottom" aria-hidden="true"></div>


        <!-- CONTENIDO DEL HERO -->

        <div class="hero-fullbleed-content hero-fullbleed-content--center container-xl">

            <span
                class="badge bg-violet-neon text-uppercase px-3 py-2 mb-4 rounded-pill fw-semibold border border-light-subtle"
            >
                <i class="bi bi-music-note-beamed me-1"></i>
                Entradas Oficiales
            </span>

            <h1
                class="hero-title-v2 text-white mb-4"
            >
                <span class="typing-line typing-line-1">Momentos inolvidables</span>
                <br>
                <span class="typing-line typing-line-2 text-gradient">a solo un click.</span>
            </h1>

            <p
                class="lead text-secondary-custom mb-4 fs-5"
            >
                Sin reventa, sin filas, sin sorpresas.
                Comprá tus entradas oficiales para los shows
                que no te querés perder.
            </p>

            <div
                class="d-flex flex-wrap justify-content-center gap-3 mb-2"
            >

                <a
                    href="#eventos"
                    class="btn btn-violet-glow btn-lg px-5 rounded-pill d-inline-flex align-items-center"
                >
                    Buscar eventos
                    <i class="bi bi-arrow-right ms-2"></i>
                </a>

            </div>

            <div
                class="mb-5"
            >
                <a
                    href="#como-funciona"
                    class="hero-secondary-link"
                >
                    Cómo funciona
                </a>
            </div>

            <div
                class="d-flex flex-wrap justify-content-center gap-4 gap-lg-5"
            >

                <div>
                    <div class="hero-stat-value text-gradient">
                        +50K
                    </div>
                    <div class="hero-stat-label">
                        entradas vendidas
                    </div>
                </div>

                <div>
                    <div class="hero-stat-value text-gradient">
                        300+
                    </div>
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

    <section
        id="eventos"
        class="py-5"
    >

        <div class="container-xl">


            <!-- ENCABEZADO -->

            <div
                class="d-flex justify-content-between align-items-center mb-4"
            >

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

            <div class="row g-4">


                <!-- EVENTO 1 -->

                <div class="col-12 col-sm-6 col-lg-3">

                    <article
                        class="card event-card h-100 bg-card-dark text-light border-0 rounded-4 overflow-hidden shadow"
                    >

                        <div class="position-relative overflow-hidden">

                            <img
                                src="https://images.unsplash.com/photo-1514525253161-7a46d19cd819?auto=format&fit=crop&w=600&q=80"
                                class="card-img-top event-img"
                                alt="Recital de Rock"
                            >

                            <div class="img-gradient-overlay"></div>

                            <span
                                class="badge bg-violet-neon position-absolute top-0 end-0 m-3 rounded-pill shadow-sm"
                            >
                                Rock
                            </span>

                        </div>


                        <div
                            class="card-body d-flex flex-column p-4 position-relative z-1"
                        >

                            <h3
                                class="h5 card-title fw-bold text-white mb-2"
                            >
                                Festival de Rock 2026
                            </h3>

                            <p class="text-secondary-custom small mb-2">

                                <i class="bi bi-geo-alt me-1 text-primary-neon"></i>

                                Estadio River Plate, CABA

                            </p>

                            <p class="text-secondary-custom small mb-3">

                                <i class="bi bi-calendar-event me-1 text-primary-neon"></i>

                                15 de Noviembre, 21:00 hs

                            </p>

                            <div
                                class="mt-auto d-flex justify-content-between align-items-center pt-3 border-top border-secondary-subtle"
                            >

                                <span class="fs-5 fw-bold text-white">
                                    $ 25.000
                                </span>

                                <a
                                    href="#"
                                    class="btn btn-sm btn-outline-violet rounded-pill px-3"
                                >
                                    Comprar
                                </a>

                            </div>

                        </div>

                    </article>

                </div>


                <!-- EVENTO 2 -->

                <div class="col-12 col-sm-6 col-lg-3">

                    <article
                        class="card event-card h-100 bg-card-dark text-light border-0 rounded-4 overflow-hidden shadow"
                    >

                        <div class="position-relative overflow-hidden">

                            <img
                                src="https://images.unsplash.com/photo-1501386761578-eac5c94b800a?auto=format&fit=crop&w=600&q=80"
                                class="card-img-top event-img"
                                alt="Noche Urbana Trap"
                            >

                            <div class="img-gradient-overlay"></div>

                            <span
                                class="badge bg-blue-neon position-absolute top-0 end-0 m-3 rounded-pill shadow-sm"
                            >
                                Urban / Trap
                            </span>

                        </div>


                        <div
                            class="card-body d-flex flex-column p-4 position-relative z-1"
                        >

                            <h3 class="h5 card-title fw-bold text-white mb-2">
                                Noche Urbana Live
                            </h3>

                            <p class="text-secondary-custom small mb-2">

                                <i class="bi bi-geo-alt me-1 text-primary-neon"></i>

                                Movistar Arena, CABA

                            </p>

                            <p class="text-secondary-custom small mb-3">

                                <i class="bi bi-calendar-event me-1 text-primary-neon"></i>

                                20 de Diciembre, 20:00 hs

                            </p>

                            <div
                                class="mt-auto d-flex justify-content-between align-items-center pt-3 border-top border-secondary-subtle"
                            >

                                <span class="fs-5 fw-bold text-white">
                                    $ 30.000
                                </span>

                                <a
                                    href="#"
                                    class="btn btn-sm btn-outline-violet rounded-pill px-3"
                                >
                                    Comprar
                                </a>

                            </div>

                        </div>

                    </article>

                </div>


                <!-- EVENTO 3 -->

                <div class="col-12 col-sm-6 col-lg-3">

                    <article
                        class="card event-card h-100 bg-card-dark text-light border-0 rounded-4 overflow-hidden shadow"
                    >

                        <div class="position-relative overflow-hidden">

                            <img
                                src="https://images.unsplash.com/photo-1492684223066-81342ee5ff30?auto=format&fit=crop&w=600&q=80"
                                class="card-img-top event-img"
                                alt="Electro Night"
                            >

                            <div class="img-gradient-overlay"></div>

                            <span
                                class="badge bg-violet-neon position-absolute top-0 end-0 m-3 rounded-pill shadow-sm"
                            >
                                Electrónica
                            </span>

                        </div>


                        <div
                            class="card-body d-flex flex-column p-4 position-relative z-1"
                        >

                            <h3 class="h5 card-title fw-bold text-white mb-2">
                                Electro Sunset Session
                            </h3>

                            <p class="text-secondary-custom small mb-2">

                                <i class="bi bi-geo-alt me-1 text-primary-neon"></i>

                                Mandarine Park, CABA

                            </p>

                            <p class="text-secondary-custom small mb-3">

                                <i class="bi bi-calendar-event me-1 text-primary-neon"></i>

                                05 de Enero, 18:00 hs

                            </p>

                            <div
                                class="mt-auto d-flex justify-content-between align-items-center pt-3 border-top border-secondary-subtle"
                            >

                                <span class="fs-5 fw-bold text-white">
                                    $ 20.000
                                </span>

                                <a
                                    href="#"
                                    class="btn btn-sm btn-outline-violet rounded-pill px-3"
                                >
                                    Comprar
                                </a>

                            </div>

                        </div>

                    </article>

                </div>


                <!-- EVENTO 4 -->

                <div class="col-12 col-sm-6 col-lg-3">

                    <article
                        class="card event-card h-100 bg-card-dark text-light border-0 rounded-4 overflow-hidden shadow"
                    >

                        <div class="position-relative overflow-hidden">

                            <img
                                src="https://images.unsplash.com/photo-1459749411175-04bf5292ceea?auto=format&fit=crop&w=600&q=80"
                                class="card-img-top event-img"
                                alt="Pop Tour"
                            >

                            <div class="img-gradient-overlay"></div>

                            <span
                                class="badge bg-blue-neon position-absolute top-0 end-0 m-3 rounded-pill shadow-sm"
                            >
                                Pop
                            </span>

                        </div>


                        <div
                            class="card-body d-flex flex-column p-4 position-relative z-1"
                        >

                            <h3 class="h5 card-title fw-bold text-white mb-2">
                                Pop World Tour
                            </h3>

                            <p class="text-secondary-custom small mb-2">

                                <i class="bi bi-geo-alt me-1 text-primary-neon"></i>

                                Estadio Vélez Sarsfield, CABA

                            </p>

                            <p class="text-secondary-custom small mb-3">

                                <i class="bi bi-calendar-event me-1 text-primary-neon"></i>

                                12 de Febrero, 21:30 hs

                            </p>

                            <div
                                class="mt-auto d-flex justify-content-between align-items-center pt-3 border-top border-secondary-subtle"
                            >

                                <span class="fs-5 fw-bold text-white">
                                    $ 28.000
                                </span>

                                <a
                                    href="#"
                                    class="btn btn-sm btn-outline-violet rounded-pill px-3"
                                >
                                    Comprar
                                </a>

                            </div>

                        </div>

                    </article>

                </div>

            </div>

        </div>

    </section>



    <!-- =====================================================
         CÓMO FUNCIONA
    ====================================================== -->

    <section
        id="como-funciona"
        class="py-5 border-top border-dark-subtle position-relative"
    >

        <div class="container-xl">


            <!-- ENCABEZADO -->

            <div
                class="text-center max-w-2xl mx-auto mb-5"
            >

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
                        class="step-card p-4 rounded-4 text-center h-100 border border-secondary-subtle position-relative"
                    >

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
                        class="step-card p-4 rounded-4 text-center h-100 border border-secondary-subtle position-relative"
                    >

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
                        class="step-card p-4 rounded-4 text-center h-100 border border-secondary-subtle position-relative"
                    >

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
                        class="step-card p-4 rounded-4 text-center h-100 border border-secondary-subtle position-relative"
                    >

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