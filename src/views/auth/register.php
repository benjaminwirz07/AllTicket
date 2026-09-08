<?php
require_once __DIR__ . '/../../config/bootstrap.php';

// Si ya inició sesión, lo mandamos al inicio
if (isset($_SESSION['user'])) {
    header('Location: /src/views/index.php');
    exit;
}
?>

<!DOCTYPE html>
<html lang="es">

<head>

    <meta charset="UTF-8">

    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>AllTicket - Crear cuenta</title>

    <!-- CSS de AllTicket -->
    <link rel="stylesheet" href="/assets/css/custom.css">

    <!-- Iconos -->
    <link
        rel="stylesheet"
        href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css"
    >

</head>

<body>

    <!-- Contenedor principal -->
    <main class="login-page">

        <!-- LOGO -->
        <section class="brand">

            <!-- Ícono del ticket -->
            <div class="ticket-icon">
                <i class="fa-solid fa-ticket"></i>
            </div>

            <!-- Nombre AllTicket -->
            <h1>
                <span class="all">All</span><span class="ticket">Ticket</span>
            </h1>

            <!-- Eslogan -->
            <p class="slogan">
                MOMENTOS INOLVIDABLES A SOLO UN CLICK
            </p>

        </section>


        <!-- REGISTRO -->
        <section class="login-card">

            <!-- Ícono de usuario -->
            <div class="user-icon">
                <i class="fa-regular fa-user"></i>
            </div>

            <!-- Título -->
            <h2>Crear cuenta</h2>

            <!-- Descripción -->
            <p class="description">
                Creá tu cuenta en AllTicket para comenzar<br>
                a disfrutar de tus próximos eventos.
            </p>


            <!-- FORMULARIO -->
            <form action="/src/controllers/auth/register.php" method="POST">

                <!-- Email -->
                <div class="input-box">

                    <i class="fa-regular fa-envelope"></i>

                    <input
                        type="email"
                        name="email"
                        placeholder="Correo electrónico"
                        required
                    >

                </div>


                <!-- Usuario -->
                <div class="input-box">

                    <i class="fa-regular fa-user"></i>

                    <input
                        type="text"
                        name="name"
                        placeholder="Nombre de usuario"
                        required
                    >

                </div>


                <!-- Contraseña -->
                <div class="input-box">

                    <i class="fa-solid fa-lock"></i>

                    <input
                        type="password"
                        name="password"
                        id="password"
                        placeholder="Contraseña"
                        required
                    >

                    <i
                        class="fa-regular fa-eye eye"
                        onclick="mostrarPassword('password')">
                    </i>

                </div>


                <!-- Repetir contraseña -->
                <div class="input-box">

                    <i class="fa-solid fa-lock"></i>

                    <input
                        type="password"
                        name="repeatPassword"
                        id="repeatPassword"
                        placeholder="Repetí tu contraseña"
                        required
                    >

                    <i
                        class="fa-regular fa-eye eye"
                        onclick="mostrarPassword('repeatPassword')">
                    </i>

                </div>


                <!-- Botón crear cuenta -->
                <button type="submit" class="login-button">

                    Crear cuenta

                    <i class="fa-solid fa-arrow-right"></i>

                </button>

            </form>


            <!-- Separador -->
            <div class="separator">

                <span></span>

                <p>¿Ya tenés una cuenta?</p>

                <span></span>

            </div>


            <!-- Volver al login -->
            <a href="/src/views/auth/login.php" class="register-button">

                <i class="fa-solid fa-arrow-left"></i>

                Iniciar sesión

            </a>

        </section>

    </main>


    <!-- JavaScript para mostrar contraseña -->
    <script>

        function mostrarPassword(id) {

            const password = document.getElementById(id);

            if (password.type === "password") {

                password.type = "text";

            } else {

                password.type = "password";

            }

        }

    </script>

</body>

</html>

