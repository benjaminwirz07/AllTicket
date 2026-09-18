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



    <title>AllTicket - Iniciar Sesión</title>



    <link rel="preconnect" href="https://fonts.googleapis.com">

    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>



    <link

        href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap"

        rel="stylesheet">



    <link

        href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css"

        rel="stylesheet">



    <link

        rel="stylesheet"

        href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">



    <link rel="stylesheet" href="/assets/css/custom.css">

</head>



<body>



    <main class="login-page">



        <div class="brand-logo-container text-center mb-4">

            <img

                src="/assets/img/logo-Allticket2.png"

                alt="AllTicket Logo"

                class="brand-logo img-fluid">

        </div>



        <section class="login-card text-center">



            <div

                class="allticket-auth-blob"

                aria-hidden="true">

            </div>



            <div class="allticket-auth-bg">



                <div class="user-icon">

                    <i class="fa-regular fa-user"></i>

                </div>



                <h2>Bienvenido</h2>



                <p class="description">

                    Iniciá sesión en tu cuenta para continuar<br>

                    o creá una nueva si aún no tenés una.

                </p>



                <form

                    action="/src/controllers/auth/login.php"

                    method="POST">



                    <div class="mb-3 text-start">



                        <label class="form-label-custom">

                            Correo electrónico

                        </label>



                        <div class="input-box">



                            <i class="fa-regular fa-envelope"></i>



                            <input

                                type="email"

                                name="email"

                                placeholder="ejemplo@correo.com"

                                required>



                        </div>



                    </div>



                    <div class="mb-4 text-start">



                        <label class="form-label-custom">

                            Contraseña

                        </label>



                        <div class="input-box">



                            <i class="fa-solid fa-lock"></i>



                            <input

                                type="password"

                                name="password"

                                id="password"

                                placeholder="Tu contraseña"

                                required>



                            <i

                                class="fa-regular fa-eye eye"

                                onclick="mostrarPassword()">

                            </i>



                        </div>



                    </div>



                    <button

                        type="submit"

                        class="login-button">



                        Iniciar sesión



                        <i class="fa-solid fa-arrow-right"></i>



                    </button>



                </form>



                <div class="separator">



                    <span></span>



                    <p>¿No tenés una cuenta?</p>



                    <span></span>



                </div>



                <a

                    href="/src/views/auth/register.php"

                    class="register-button">



                    <i class="fa-regular fa-user"></i>



                    Crear cuenta



                </a>



            </div>



        </section>



    </main>



    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>



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