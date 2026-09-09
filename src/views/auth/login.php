<?php 
require_once __DIR__ . '/../../config/bootstrap.php';

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

    <title>AllTicket - Iniciar sesión</title>

    <!-- Conectamos el archivo CSS -->
<link rel="stylesheet" href="/assets/css/custom.css">

    <!-- Iconos -->
    <link rel="stylesheet"
          href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">
</head>

<body>

    <!-- Contenedor principal -->
    <main class="login-page">

        <!-- LOGO -->
        <section class="brand">

            <!-- Ícono de entrada -->
            <div class="ticket-icon">
                <i class="fa-solid fa-ticket"></i>
            </div>

            <!-- Nombre de la página -->
            <h1>
                <span class="all">All</span><span class="ticket">Ticket</span>
            </h1>

            <!-- Eslogan -->
            <p class="slogan">
                MOMENTOS INOLVIDABLES A SOLO UN CLICK
            </p>

        </section>


        <!-- LOGIN -->
        <section class="login-card">

            <!-- Ícono de usuario -->
            <div class="user-icon">
                <i class="fa-regular fa-user"></i>
            </div>

            <!-- Título -->
            <h2>Bienvenido</h2>

            <!-- Descripción -->
            <p class="description">
                Iniciá sesión en tu cuenta para continuar<br>
                o creá una nueva si aún no tenés una.
            </p>


            <!-- FORMULARIO -->
            <form action="/src/controllers/auth/login.php" method="POST">

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

                    <!-- Mostrar contraseña -->
                    <i
                        class="fa-regular fa-eye eye"
                        onclick="mostrarPassword()">
                    </i>

                </div>


                <!-- Botón iniciar sesión -->
                <button type="submit" class="login-button">

                    Iniciar sesión

                    <i class="fa-solid fa-arrow-right"></i>

                </button>

            </form>


            <!-- Separador -->
            <div class="separator">

                <span></span>

                <p>¿No tenés una cuenta?</p>

                <span></span>

            </div>


            <!-- Crear cuenta -->
            <a href="/src/views/auth/register.php" class="register-button">

                <i class="fa-regular fa-user"></i>

                Crear cuenta

            </a>

        </section>

    </main>


    <!-- JavaScript para mostrar contraseña -->
    <script>

        function mostrarPassword() {

            const password = document.getElementById("password");

            if (password.type === "password") {

                password.type = "text";

            } else {

                password.type = "password";

            }

        }

    </script>

</body>
</html>