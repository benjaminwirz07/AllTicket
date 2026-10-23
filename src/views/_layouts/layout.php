<?php

require_once __DIR__ . '/../../config/bootstrap.php';

$usuarioMostrar = '';
if (isAuthenticated()) {
    try {
        $stmtUsuario = $pdo->prepare('SELECT usuario FROM users WHERE id = :id LIMIT 1');
        $stmtUsuario->execute(['id' => currentUserId()]);
        $usuarioMostrar = (string) ($stmtUsuario->fetchColumn() ?: '');
    } catch (PDOException $e) {
        error_log('Error al consultar el usuario en el layout: ' . $e->getMessage());
    }
}
?>

<!DOCTYPE html>
<html lang="es" data-bs-theme="dark">

<head>

    <!-- =========================================================
         CONFIGURACIÓN BÁSICA
    ========================================================== -->
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>AllTicket</title>


    <!-- =========================================================
         FUENTES
    ========================================================== -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>

    <link
        href="https://fonts.googleapis.com/css2?family=Poppins:wght@500;600;700&family=Roboto:wght@400;500;700&display=swap"
        rel="stylesheet">


    <!-- =========================================================
         BOOTSTRAP
    ========================================================== -->
    <link rel="stylesheet" href="<?= e(url('/assets/css/bootstrap.min.css')) ?>">

    <!-- Bootstrap Icons: usados en navbar y footer -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">

    <!-- Bootstrap JS (versión "bundle", incluye Popper para el menú de Categorías).
         Se carga desde internet para no depender de un archivo local.
         Si preferís tenerlo local, bajá el mismo archivo a /assets/js/ y cambiá este src. -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>


    <!-- =========================================================
         ESTILOS ALLTICKET
    ========================================================== -->
    <link rel="stylesheet" href="<?= e(url('/assets/css/global.css')) ?>">
    <link rel="stylesheet" href="<?= e(url('/assets/css/admin.css')) ?>">
    <link rel="stylesheet" href="<?= e(url('/assets/css/chatbot.css')) ?>">
    <?php
    // Estilos específicos de la página actual (ej: index.css),
    // definidos por la vista antes de incluir este layout.
    if (isset($pageStyles)) {
        echo $pageStyles;
    }
    ?>

</head>


<body>


    <!-- Salto directo al contenido para navegación por teclado -->
    <a href="#main-content" class="skip-link">Saltar al contenido principal</a>


    <!-- =========================================================
         FONDO AMBIENTAL GLOBAL
    ========================================================== -->
    <div class="ambient-background" aria-hidden="true">

        <div class="grid-overlay"></div>

        <div class="glow-orb orb-violet"></div>
        <div class="glow-orb orb-blue"></div>
        <div class="glow-orb orb-cyan"></div>
        <div class="glow-orb orb-violet-secondary"></div>

    </div>


    <!-- =========================================================
         HEADER / NAVBAR
    ========================================================== -->
    <header class="sticky-top">

        <nav class="navbar navbar-expand-lg navbar-dark bg-allticket border-bottom border-dark-subtle shadow-sm">

            <div class="container-fluid container-xl">


                <!-- LOGO -->
                <a class="navbar-brand d-flex align-items-center py-1" href="<?= e(url('/src/views/index.php')) ?>">

                    <img src="<?= e(url('/assets/img/logo-Allticket1.png')) ?>" alt="AllTicket Logo" class="brand-logo">

                </a>


                <!-- BOTÓN MENÚ RESPONSIVE -->
                <button class="navbar-toggler border-0 shadow-none" type="button" data-bs-toggle="collapse"
                    data-bs-target="#navbarAllTicket" aria-controls="navbarAllTicket" aria-expanded="false"
                    aria-label="Abrir menú">

                    <span class="navbar-toggler-icon"></span>

                </button>


                <!-- CONTENIDO NAVBAR -->
                <div class="collapse navbar-collapse" id="navbarAllTicket">


                    <!-- LINKS PRINCIPALES -->
                    <ul class="navbar-nav me-auto mb-2 mb-lg-0 ms-lg-4">

                        <li class="nav-item">
                            <a class="nav-link active" aria-current="page" href="<?= e(url('/src/views/index.php')) ?>">
                                Inicio
                            </a>
                        </li>

                        <?php if (isAuthenticated()): ?>
                            <li class="nav-item">
                                <a class="nav-link" href="<?= e(url('/src/controllers/tickets/index.php')) ?>">
                                    Mis entradas
                                </a>
                            </li>
                        <?php endif; ?>


                        <li class="nav-item">
                            <a class="nav-link" href="#eventos">
                                Eventos & Recitales
                            </a>
                        </li>

                        <li class="nav-item">
                            <a class="nav-link" href="<?= e(url('/src/views/index.php#noticias')) ?>">
                                Noticias
                            </a>
                        </li>


                        <!-- NUEVO: Categorías -->
                        <li class="nav-item dropdown">

                            <a class="nav-link dropdown-toggle" href="#" role="button" data-bs-toggle="dropdown"
                                aria-expanded="false">
                                Categorías
                            </a>

                            <ul class="dropdown-menu dropdown-menu-dark">
                                <li><a class="dropdown-item" href="<?= e(url('/src/views/index.php?categoria=rock#eventos')) ?>">Rock</a>
                                </li>
                                <li><a class="dropdown-item"
                                        href="<?= e(url('/src/views/index.php?categoria=reggaeton#eventos')) ?>">Reggaetón / Trap</a>
                                </li>
                                <li><a class="dropdown-item" href="<?= e(url('/src/views/index.php?categoria=pop#eventos')) ?>">Pop</a>
                                </li>
                                <li><a class="dropdown-item"
                                        href="<?= e(url('/src/views/index.php?categoria=electronica#eventos')) ?>">Electrónica</a></li>
                            </ul>

                        </li>


                        <li class="nav-item">
                            <a class="nav-link" href="#como-funciona">
                                Cómo funciona
                            </a>
                        </li>


                        <!-- Ayuda: lleva a las preguntas frecuentes del inicio -->
                        <li class="nav-item">
                            <a class="nav-link" href="<?= e(url('/src/views/index.php#preguntas-frecuentes')) ?>">
                                Ayuda
                            </a>
                        </li>

                        <?php if (isAdmin()): ?>
                            <li class="nav-item">
                                <a class="nav-link" href="<?= e(url('/src/controllers/admin/index.php')) ?>">
                                    <i class="bi bi-gear me-1"></i>Panel admin
                                </a>
                            </li>
                        <?php endif; ?>

                    </ul>


                    <!-- BÚSQUEDA + USUARIO -->
                    <div class="nav-actions d-flex align-items-center gap-3">

                        <!-- BÚSQUEDA
                             La lupa ahora BUSCA: se expande el campo, se escribe,
                             y con Enter o la lupa va a los resultados. -->
                        <form class="nav-search" id="navSearch" action="<?= e(url('/src/views/index.php')) ?>" method="GET" role="search"
                            autocomplete="off">

                            <input type="search" name="q" id="navSearchInput" class="nav-search-input"
                                placeholder="Buscar artista o evento..." aria-label="Buscar artista o evento">

                            <button type="button" class="nav-search-toggle" id="navSearchToggle"
                                aria-label="Buscar eventos">
                                <i class="bi bi-search"></i>
                            </button>

                            <div class="nav-search-suggest" id="navSearchSuggest" hidden></div>

                        </form>


                        <?php if (isAuthenticated()): ?>

                            <!-- Nombre del usuario -->
                            <span class="text-light small d-none d-md-inline text-nowrap">

                                <i class="bi bi-person-circle me-1 text-primary-neon"></i>

                                Hola, <?= e($usuarioMostrar !== '' ? $usuarioMostrar : 'Mi cuenta') ?>

                            </span>


                            <!-- Cerrar sesión -->
                            <form action="<?= e(url('/src/controllers/auth/logout.php')) ?>" method="POST" class="d-inline flex-shrink-0">
                                <input type="hidden" name="csrf_token" value="<?= e(csrfToken()) ?>">
                                <button type="submit" class="btn btn-outline-danger btn-sm rounded-pill px-3 text-nowrap">
                                    <i class="bi bi-box-arrow-right me-1" aria-hidden="true"></i>Cerrar sesión
                                </button>
                            </form>


                        <?php else: ?>

                            <!-- Iniciar sesión -->
                            <a href="<?= e(url('/src/views/auth/login.php')) ?>" class="btn btn-violet-glow btn-sm rounded-pill px-4">

                                Iniciar Sesión

                            </a>

                        <?php endif; ?>


                    </div>

                </div>

            </div>

        </nav>

    </header>



    <!-- =========================================================
         CONTENIDO PRINCIPAL
         Cada vista arma su propio contenido en $pageContent
         (con ob_start / ob_get_clean) antes de incluir este layout.
    ========================================================== -->
    <div id="main-content" class="site-main">

        <?php
        $globalFlash = consumeFlash();
        if ($globalFlash !== null):
            $flashType = in_array($globalFlash['type'], ['success', 'danger', 'warning', 'info'], true)
                ? $globalFlash['type']
                : 'info';
        ?>
            <div class="container-xl pt-3">
                <div class="alert alert-<?= e($flashType) ?>" role="status"><?= e($globalFlash['msg']) ?></div>
            </div>
        <?php
        endif;
        if (isset($pageContent)) {
            echo $pageContent;
        }
        ?>

    </div>



    <!-- =========================================================
         FOOTER
    ========================================================== -->
    <footer class="bg-dark-footer text-light-50 py-5 border-top border-dark-subtle position-relative z-1">

        <div class="container-xl">


            <div class="row g-4 mb-4">


                <!-- MARCA -->
                <div class="col-12 col-md-4">

                    <a class="d-inline-block text-decoration-none mb-3" href="<?= e(url('/src/views/index.php')) ?>">

                        <img src="<?= e(url('/assets/img/logo-Allticket1.png')) ?>" alt="AllTicket Logo" class="brand-logo">

                    </a>


                    <p class="small text-secondary-custom mb-0">

                        Plataforma líder en venta de entradas digitales
                        para conciertos, recitales y eventos musicales
                        en vivo.

                    </p>

                </div>



                <!-- SECCIONES -->
                <div class="col-6 col-md-2 offset-md-2">

                    <h5 class="text-white small fw-bold text-uppercase mb-3">
                        Secciones
                    </h5>


                    <ul class="list-unstyled small d-flex flex-column gap-2 mb-0">

                        <li>
                            <a href="<?= e(url('/src/views/index.php')) ?>"
                                class="text-secondary-custom text-decoration-none nav-footer-link">
                                Inicio
                            </a>
                        </li>

                        <li>
                            <a href="#eventos" class="text-secondary-custom text-decoration-none nav-footer-link">
                                Eventos
                            </a>
                        </li>

                        <li>
                            <a href="<?= e(url('/src/views/index.php#noticias')) ?>"
                                class="text-secondary-custom text-decoration-none nav-footer-link">
                                Noticias
                            </a>
                        </li>

                        <li>
                            <a href="#como-funciona" class="text-secondary-custom text-decoration-none nav-footer-link">
                                Cómo funciona
                            </a>
                        </li>

                        <li>
                            <a href="<?= e(url('/src/views/index.php#preguntas-frecuentes')) ?>"
                                class="text-secondary-custom text-decoration-none nav-footer-link">
                                Ayuda
                            </a>
                        </li>

                    </ul>

                </div>



                <!-- SOPORTE -->
                <div class="col-6 col-md-4">

                    <h5 class="text-white small fw-bold text-uppercase mb-3">
                        Soporte & Contacto
                    </h5>


                    <ul class="list-unstyled small d-flex flex-column gap-2 mb-0 text-secondary-custom">

                        <li>
                            <i class="bi bi-envelope me-2 text-violet-neon"></i>
                            soporte@allticket.com
                        </li>

                        <li>
                            <i class="bi bi-shield-check me-2 text-blue-neon"></i>
                            Compra 100% segura
                        </li>

                    </ul>

                </div>

            </div>



            <!-- COPYRIGHT -->
            <div class="border-top border-secondary-subtle pt-4 text-center small text-secondary-custom">

                <p class="mb-0">

                    &copy;
                    <?= date('Y') ?>
                    AllTicket. Todos los derechos reservados.

                </p>

            </div>


        </div>

    </footer>

    <button id="chatbotLauncher" class="chatbot-launcher" type="button"
        aria-label="Abrir asistente virtual" aria-controls="chatbotPanel" aria-expanded="false">
        <img src="<?= e(url('/assets/img/chatbot/chat.png')) ?>" alt="">
    </button>

    <section id="chatbotPanel" class="chatbot-panel" aria-label="Asistente virtual" hidden>
        <header class="chatbot-header">
            <div class="chatbot-header__identity">
                <img src="<?= e(url('/assets/img/chatbot/chat.png')) ?>" alt="">
                <span>Asistente AllTicket</span>
            </div>
            <button id="chatbotClose" class="chatbot-close" type="button" aria-label="Cerrar asistente">&times;</button>
        </header>
        <p class="chatbot-privacy">Guardamos el historial de esta sesión. No envíes datos de pago ni información sensible.</p>
        <div id="chatbotLog" class="chatbot-log" role="log" aria-live="polite" aria-relevant="additions"></div>
        <p id="chatbotError" class="chatbot-error" role="alert" hidden></p>
        <form id="chatbotForm" class="chatbot-form">
            <input type="hidden" id="chatbotCsrf" value="<?= e(csrfToken()) ?>">
            <label class="visually-hidden" for="chatbotInput">Escribí tu mensaje</label>
            <input id="chatbotInput" class="form-control custom-input" type="text" maxlength="1000"
                placeholder="Preguntá por eventos o entradas..." autocomplete="off" disabled>
            <button id="chatbotSend" class="btn btn-violet-glow" type="submit" disabled>Enviar</button>
        </form>
    </section>

    <?php
    // Nombres de eventos y artistas con recitales próximos para las sugerencias.
    $nombresEventos = [];

    if (isset($pdo)) {
        try {
            $stmtNombres = $pdo->query(
                "SELECT nombre
                   FROM (
                       SELECT c.name AS nombre
                         FROM concerts c
                        WHERE c.status = 'active'
                          AND c.date >= CURDATE()
                       UNION
                       SELECT a.name AS nombre
                         FROM artists a
                         JOIN concerts c ON c.artist_id = a.id
                        WHERE c.status = 'active'
                          AND c.date >= CURDATE()
                   ) AS sugerencias
                  ORDER BY nombre ASC"
            );
            $nombresEventos = $stmtNombres->fetchAll(PDO::FETCH_COLUMN);
        } catch (PDOException $e) {
            $nombresEventos = [];
        }
    }
    ?>
    <script>
        // Lista que usa nav-search.js
        var eventosAllticket = <?= json_encode($nombresEventos, JSON_UNESCAPED_UNICODE | JSON_HEX_TAG) ?>;
    </script>

    <script>
        // Subrayado del menú: al tocar un link, el subrayado pasa a ese link.
        var enlacesMenu = document.querySelectorAll(
            ".navbar-nav > .nav-item > a.nav-link:not(.dropdown-toggle)"
        );

        // Saca la marca a todos los links y se la pone al que tocaron
        function marcarActivo(enlace) {
            for (var i = 0; i < enlacesMenu.length; i++) {
                enlacesMenu[i].classList.remove("active");
                enlacesMenu[i].removeAttribute("aria-current");
            }

            enlace.classList.add("active");
            enlace.setAttribute("aria-current", "page");
        }

        // Cada link avisa cuando lo tocan
        for (var i = 0; i < enlacesMenu.length; i++) {
            enlacesMenu[i].addEventListener("click", function () {
                marcarActivo(this);
            });
        }
    </script>

    <!-- Buscador del header (lupa + sugerencias) -->
    <script src="<?= e(url('/assets/js/nav-search.js')) ?>"></script>
    <script src="<?= e(url('/assets/js/chatbot.js')) ?>"></script>

</body>

</html>