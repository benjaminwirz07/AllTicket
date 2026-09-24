<?php

require_once __DIR__ . '/../../config/bootstrap.php';

// Si no está logueado, redirigir al login
if (!isset($_SESSION['user'])) {
    header('Location: /src/views/auth/login.php');
    exit;
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
        href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600&family=Sora:wght@600;700&display=swap"
        rel="stylesheet"
    >


    <!-- =========================================================
         BOOTSTRAP
    ========================================================== -->
    <link
        rel="stylesheet"
        href="/assets/css/bootstrap.min.css"
    >

    <!-- Bootstrap Icons: usados en navbar y footer, se cargan acá
         a nivel global para que estén disponibles en toda página
         que use este layout -->
    <link
        rel="stylesheet"
        href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css"
    >

    <script
        src="/assets/js/bootstrap.min.js"
    ></script>


    <!-- =========================================================
         ESTILOS GLOBALES ALLTICKET
    ========================================================== -->
    <link
        rel="stylesheet"
        href="/assets/css/global.css"
    >

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
         Vive acá porque se comparte entre todas las páginas
         que usan este layout.
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

        <nav
            class="navbar navbar-expand-lg navbar-dark bg-allticket border-bottom border-dark-subtle shadow-sm"
        >

            <div class="container-fluid container-xl">


                <!-- =================================================
                     LOGO
                ================================================== -->
                <a
                    class="navbar-brand d-flex align-items-center py-1"
                    href="/src/views/index.php"
                >

                    <img
                        src="/assets/img/logo-Allticket1.png"
                        alt="AllTicket Logo"
                        class="brand-logo"
                    >

                </a>


                <!-- =================================================
                     BOTÓN MENÚ RESPONSIVE
                ================================================== -->
                <button
                    class="navbar-toggler border-0 shadow-none"
                    type="button"
                    data-bs-toggle="collapse"
                    data-bs-target="#navbarAllTicket"
                    aria-controls="navbarAllTicket"
                    aria-expanded="false"
                    aria-label="Abrir menú"
                >

                    <span class="navbar-toggler-icon"></span>

                </button>


                <!-- =================================================
                     CONTENIDO NAVBAR
                ================================================== -->
                <div
                    class="collapse navbar-collapse"
                    id="navbarAllTicket"
                >


                    <!-- =================================================
                         LINKS PRINCIPALES
                    ================================================== -->
                    <ul
                        class="navbar-nav me-auto mb-2 mb-lg-0 ms-lg-4"
                    >

                        <li class="nav-item">

                            <a
                                class="nav-link active"
                                aria-current="page"
                                href="/src/views/index.php"
                            >
                                Inicio
                            </a>

                        </li>


                        <li class="nav-item">

                            <a
                                class="nav-link"
                                href="#eventos"
                            >
                                Eventos & Recitales
                            </a>

                        </li>


                        <li class="nav-item">

                            <a
                                class="nav-link"
                                href="#como-funciona"
                            >
                                Cómo funciona
                            </a>

                        </li>

                    </ul>


                    <!-- =================================================
                         USUARIO
                    ================================================== -->
                    <div class="d-flex align-items-center gap-3">

                        <!-- BÚSQUEDA
                             Abre/cierra el panel de filtros que se
                             despliega debajo del navbar. -->
                        <button
                            type="button"
                            class="nav-search-toggle"
                            data-bs-toggle="collapse"
                            data-bs-target="#navSearchPanel"
                            aria-controls="navSearchPanel"
                            aria-expanded="false"
                            aria-label="Buscar eventos"
                        >
                            <i class="bi bi-search"></i>
                        </button>


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
                            <a
                                href="/src/controllers/auth/logout.php"
                                class="btn btn-outline-danger btn-sm rounded-pill px-3"
                            >

                                <i class="bi bi-box-arrow-right me-1"></i>

                                Cerrar sesión

                            </a>


                        <?php else: ?>

                            <!-- Iniciar sesión -->
                            <a
                                href="/src/views/auth/login.php"
                                class="btn btn-violet-glow btn-sm rounded-pill px-4"
                            >

                                Iniciar Sesión

                            </a>

                        <?php endif; ?>


                    </div>

                </div>

            </div>

        </nav>


        <!-- =================================================
             PANEL DE BÚSQUEDA (desplegable, oculto por defecto)
        ================================================== -->
        <div class="collapse nav-search-panel" id="navSearchPanel">

            <div class="container-fluid container-xl py-4">

                <form
                    class="search-form-card p-4 rounded-4 border border-secondary-subtle"
                    action="/src/views/index.php"
                    method="GET"
                >

                    <div class="row g-3 align-items-end">


                        <!-- ARTISTA / EVENTO -->

                        <div class="col-12 col-md-4">

                            <label
                                class="form-label text-light-50 small fw-semibold"
                            >
                                Buscar Artista o Evento
                            </label>

                            <div class="input-group">

                                <span class="input-group-text custom-input-text">

                                    <i class="bi bi-search"></i>

                                </span>

                                <input
                                    type="text"
                                    name="q"
                                    class="form-control custom-input"
                                    placeholder="Ej. Duki, Divididos, Festival..."
                                >

                            </div>

                        </div>


                        <!-- CATEGORÍA -->

                        <div class="col-12 col-sm-6 col-md-3">

                            <label
                                class="form-label text-light-50 small fw-semibold"
                            >
                                Género / Categoría
                            </label>

                            <select
                                name="categoria"
                                class="form-select custom-input"
                            >

                                <option value="">
                                    Todas las categorías
                                </option>

                                <option value="rock">
                                    Rock
                                </option>

                                <option value="reggaeton">
                                    Reggaetón / Trap
                                </option>

                                <option value="pop">
                                    Pop
                                </option>

                                <option value="electronica">
                                    Electrónica
                                </option>

                            </select>

                        </div>


                        <!-- UBICACIÓN -->

                        <div class="col-12 col-sm-6 col-md-3">

                            <label
                                class="form-label text-light-50 small fw-semibold"
                            >
                                Ubicación
                            </label>

                            <select
                                name="ciudad"
                                class="form-select custom-input"
                            >

                                <option value="">
                                    Todas las ciudades
                                </option>

                                <option value="buenos-aires">
                                    Buenos Aires
                                </option>

                                <option value="cordoba">
                                    Córdoba
                                </option>

                                <option value="rosario">
                                    Rosario
                                </option>

                            </select>

                        </div>


                        <!-- BOTÓN -->

                        <div class="col-12 col-md-2">

                            <button
                                type="submit"
                                class="btn btn-violet w-100 py-2 rounded-3"
                            >

                                <i class="bi bi-funnel-fill me-1"></i>

                                Filtrar

                            </button>

                        </div>

                    </div>

                </form>

            </div>

        </div>

    </header>



    <!-- =========================================================
         CONTENIDO PRINCIPAL
         Cada vista arma su propio contenido en $pageContent
         (con ob_start / ob_get_clean) antes de incluir este layout.
         El layout solo se encarga de ubicarlo dentro de la
         estructura global (por encima del fondo, entre header y footer).
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
    <footer
        class="bg-dark-footer text-light-50 py-5 border-top border-dark-subtle position-relative z-1"
    >

        <div class="container-xl">


            <!-- =================================================
                 CONTENIDO PRINCIPAL DEL FOOTER
            ================================================== -->
            <div class="row g-4 mb-4">


                <!-- =================================================
                     MARCA
                ================================================== -->
                <div class="col-12 col-md-4">

                    <a
                        class="d-inline-block text-decoration-none mb-3"
                        href="/src/views/index.php"
                    >

                        <img
                            src="/assets/img/logo-Allticket1.png"
                            alt="AllTicket Logo"
                            class="brand-logo"
                        >

                    </a>


                    <p class="small text-secondary-custom mb-0">

                        Plataforma líder en venta de entradas digitales
                        para conciertos, recitales y eventos musicales
                        en vivo.

                    </p>

                </div>



                <!-- =================================================
                     SECCIONES
                ================================================== -->
                <div class="col-6 col-md-2 offset-md-2">

                    <h5
                        class="text-white small fw-bold text-uppercase mb-3"
                    >
                        Secciones
                    </h5>


                    <ul
                        class="list-unstyled small d-flex flex-column gap-2 mb-0"
                    >

                        <li>

                            <a
                                href="/src/views/index.php"
                                class="text-secondary-custom text-decoration-none nav-footer-link"
                            >
                                Inicio
                            </a>

                        </li>


                        <li>

                            <a
                                href="#eventos"
                                class="text-secondary-custom text-decoration-none nav-footer-link"
                            >
                                Eventos
                            </a>

                        </li>


                        <li>

                            <a
                                href="#como-funciona"
                                class="text-secondary-custom text-decoration-none nav-footer-link"
                            >
                                Cómo funciona
                            </a>

                        </li>

                    </ul>

                </div>



                <!-- =================================================
                     SOPORTE
                ================================================== -->
                <div class="col-6 col-md-4">

                    <h5
                        class="text-white small fw-bold text-uppercase mb-3"
                    >
                        Soporte & Contacto
                    </h5>


                    <ul
                        class="list-unstyled small d-flex flex-column gap-2 mb-0 text-secondary-custom"
                    >

                        <li>

                            <i
                                class="bi bi-envelope me-2 text-violet-neon"
                            ></i>

                            soporte@allticket.com

                        </li>


                        <li>

                            <i
                                class="bi bi-shield-check me-2 text-blue-neon"
                            ></i>

                            Compra 100% segura

                        </li>

                    </ul>

                </div>

            </div>



            <!-- =================================================
                 COPYRIGHT
            ================================================== -->
            <div
                class="border-top border-secondary-subtle pt-4 text-center small text-secondary-custom"
            >

                <p class="mb-0">

                    &copy;
                    <?= date('Y') ?>
                    AllTicket. Todos los derechos reservados.

                </p>

            </div>


        </div>

    </footer>


</body>

</html>