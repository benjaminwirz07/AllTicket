<?php

require_once __DIR__ . '/../../config/bootstrap.php';

// Si no está logueado, redirigir al login
//if (!isset($_SESSION['user'])) {
//    header('Location: /src/views/auth/login.php');
//    exit;
//}
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
    <link rel="stylesheet" href="/assets/css/bootstrap.min.css">

    <!-- Bootstrap Icons: usados en navbar y footer -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">

    <!-- Bootstrap JS (versión "bundle", incluye Popper para el menú de Categorías).
         Se carga desde internet para no depender de un archivo local.
         Si preferís tenerlo local, bajá el mismo archivo a /assets/js/ y cambiá este src. -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>


    <!-- =========================================================
         ESTILOS ALLTICKET
    ========================================================== -->
    <link rel="stylesheet" href="/assets/css/global.css">
    <link rel="stylesheet" href="/assets/css/admin.css">
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
                <a class="navbar-brand d-flex align-items-center py-1" href="/src/views/index.php">

                    <img src="/assets/img/logo-Allticket1.png" alt="AllTicket Logo" class="brand-logo">

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
                            <a class="nav-link active" aria-current="page" href="/src/views/index.php">
                                Inicio
                            </a>
                        </li>


                        <li class="nav-item">
                            <a class="nav-link" href="#eventos">
                                Eventos & Recitales
                            </a>
                        </li>


                        <!-- NUEVO: Categorías -->
                        <li class="nav-item dropdown">

                            <a class="nav-link dropdown-toggle" href="#" role="button" data-bs-toggle="dropdown"
                                aria-expanded="false">
                                Categorías
                            </a>

                            <ul class="dropdown-menu dropdown-menu-dark">
                                <li><a class="dropdown-item" href="/src/views/index.php?categoria=rock#eventos">Rock</a>
                                </li>
                                <li><a class="dropdown-item"
                                        href="/src/views/index.php?categoria=reggaeton#eventos">Reggaetón / Trap</a>
                                </li>
                                <li><a class="dropdown-item" href="/src/views/index.php?categoria=pop#eventos">Pop</a>
                                </li>
                                <li><a class="dropdown-item"
                                        href="/src/views/index.php?categoria=electronica#eventos">Electrónica</a></li>
                            </ul>

                        </li>


                        <li class="nav-item">
                            <a class="nav-link" href="#como-funciona">
                                Cómo funciona
                            </a>
                        </li>


                        <!-- Ayuda: lleva a las preguntas frecuentes del inicio -->
                        <li class="nav-item">
                            <a class="nav-link" href="/src/views/index.php#preguntas-frecuentes">
                                Ayuda
                            </a>
                        </li>

                        <?php if (isAdmin()): ?>
                            <li class="nav-item">
                                <a class="nav-link" href="/src/controllers/admin/index.php">
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
                        <form class="nav-search" id="navSearch" action="/src/views/index.php" method="GET" role="search"
                            autocomplete="off">

                            <input type="search" name="q" id="navSearchInput" class="nav-search-input"
                                placeholder="Buscar artista o evento..." aria-label="Buscar artista o evento">

                            <button type="button" class="nav-search-toggle" id="navSearchToggle"
                                aria-label="Buscar eventos">
                                <i class="bi bi-search"></i>
                            </button>

                            <div class="nav-search-suggest" id="navSearchSuggest" hidden></div>

                        </form>


                        <?php if (isset($_SESSION['user']['first_name'])): ?>

                            <!-- Nombre del usuario -->
                            <span class="text-light small d-none d-md-inline">

                                <i class="bi bi-person-circle me-1 text-primary-neon"></i>

                                Hola,
                                <?= htmlspecialchars(
                                    $_SESSION['user']['first_name'] ?? 'Usuario'
                                ) ?>

                            </span>


                            <!-- Cerrar sesión -->
                            <a href="/src/controllers/auth/logout.php"
                                class="btn btn-outline-danger btn-sm rounded-pill px-3">

                                <i class="bi bi-box-arrow-right me-1"></i>

                                Cerrar sesión

                            </a>


                        <?php else: ?>

                            <!-- Iniciar sesión -->
                            <a href="/src/views/auth/login.php" class="btn btn-violet-glow btn-sm rounded-pill px-4">

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

                    <a class="d-inline-block text-decoration-none mb-3" href="/src/views/index.php">

                        <img src="/assets/img/logo-Allticket1.png" alt="AllTicket Logo" class="brand-logo">

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
                            <a href="/src/views/index.php"
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
                            <a href="#como-funciona" class="text-secondary-custom text-decoration-none nav-footer-link">
                                Cómo funciona
                            </a>
                        </li>

                        <li>
                            <a href="/src/views/index.php#preguntas-frecuentes"
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


    <?php
    // Nombres de los eventos próximos para las sugerencias de la lupa.
    // Salen de la base de datos, así siempre están actualizados.
    $nombresEventos = [];

    if (isset($pdo)) {
        try {
            $stmtNombres = $pdo->query(
                "SELECT DISTINCT name
                   FROM concerts
                  WHERE status = 'active'
                    AND date >= CURDATE()
                  ORDER BY name ASC"
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
    <script src="/assets/js/nav-search.js"></script>

</body>

</html>