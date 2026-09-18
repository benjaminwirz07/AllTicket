<?php


require_once __DIR__ . '/../../config/bootstrap.php';

if (isset($_SESSION['user'])) {
    header('Location: /src/views/index.php');
    exit;
}

$errors = $_SESSION['errors'] ?? [];
$old = $_SESSION['old'] ?? [];

unset($_SESSION['errors'], $_SESSION['old']);

?>

<!DOCTYPE html>
<html lang="es">

<head>

    <meta charset="UTF-8">

    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>AllTicket - Crear Cuenta</title>

    <!-- Google Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">

    <link
        rel="preconnect"
        href="https://fonts.gstatic.com"
        crossorigin
    >

    <link
        href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap"
        rel="stylesheet"
    >

    <!-- Bootstrap -->
    <link
        href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css"
        rel="stylesheet"
    >

    <!-- Font Awesome -->
    <link
        rel="stylesheet"
        href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css"
    >

    <!-- CSS personalizado -->
    <link
        rel="stylesheet"
        href="/assets/css/custom.css"
    >

</head>

<body>

    <main class="login-page">

        <!-- Logo -->
        <div class="brand-logo-container text-center mb-4">

            <img
                src="/assets/img/logo-Allticket2.png"
                alt="Logo de AllTicket"
                class="brand-logo img-fluid"
            >

        </div>

        <!-- Tarjeta de registro -->
        <section class="login-card register-wide">

            <div
                class="allticket-auth-blob"
                aria-hidden="true"
            ></div>

            <div class="allticket-auth-bg">

                <!-- Icono -->
                <div class="user-icon">

                    <i class="fa-solid fa-user-plus"></i>

                </div>

                <!-- Título -->
                <h2 class="text-center">
                    Crear cuenta
                </h2>

                <p class="description text-center">

                    Completá tus datos para acceder a las mejores
                    entradas de recitales y eventos.

                </p>

                <!-- Error general -->
                <?php if (isset($errors['general'])): ?>

                    <div class="alert-danger-custom mb-4">

                        <i class="fa-solid fa-circle-exclamation me-2"></i>

                        <?= htmlspecialchars(
                            $errors['general'],
                            ENT_QUOTES,
                            'UTF-8'
                        ) ?>

                    </div>

                <?php endif; ?>

                <!-- Formulario -->
                <form
                    action="/src/controllers/auth/register.php"
                    method="POST"
                    novalidate
                >

                    <!-- Datos personales -->
                    <div class="section-title">

                        <i class="fa-solid fa-address-card"></i>

                        Datos personales

                    </div>

                    <div class="row g-3 mb-4">

                        <!-- Nombre -->
                        <div class="col-12 col-md-6">

                            <label class="form-label-custom">
                                Nombre
                            </label>

                            <div
                                class="input-box <?= isset($errors['first_name']) ? 'input-error' : '' ?>"
                            >

                                <i class="fa-solid fa-user"></i>

                                <input
                                    type="text"
                                    name="first_name"
                                    placeholder="Ej. Benjamin"
                                    value="<?= htmlspecialchars(
                                        $old['first_name'] ?? '',
                                        ENT_QUOTES,
                                        'UTF-8'
                                    ) ?>"
                                >

                            </div>

                            <?php if (isset($errors['first_name'])): ?>

                                <span class="error-text">

                                    <?= htmlspecialchars(
                                        $errors['first_name'],
                                        ENT_QUOTES,
                                        'UTF-8'
                                    ) ?>

                                </span>

                            <?php endif; ?>

                        </div>

                        <!-- Segundo nombre -->
                        <div class="col-12 col-md-6">

                            <label class="form-label-custom">
                                Segundo nombre
                            </label>

                            <div
                                class="input-box <?= isset($errors['second_name']) ? 'input-error' : '' ?>"
                            >

                                <i class="fa-solid fa-user"></i>

                                <input
                                    type="text"
                                    name="second_name"
                                    placeholder="Ej. José"
                                    value="<?= htmlspecialchars(
                                        $old['second_name'] ?? '',
                                        ENT_QUOTES,
                                        'UTF-8'
                                    ) ?>"
                                >

                            </div>

                            <?php if (isset($errors['second_name'])): ?>

                                <span class="error-text">

                                    <?= htmlspecialchars(
                                        $errors['second_name'],
                                        ENT_QUOTES,
                                        'UTF-8'
                                    ) ?>

                                </span>

                            <?php endif; ?>

                        </div>

                        <!-- Apellido -->
                        <div class="col-12 col-md-6">

                            <label class="form-label-custom">
                                Apellido
                            </label>

                            <div
                                class="input-box <?= isset($errors['last_name']) ? 'input-error' : '' ?>"
                            >

                                <i class="fa-solid fa-user"></i>

                                <input
                                    type="text"
                                    name="last_name"
                                    placeholder="Ej. Pérez"
                                    value="<?= htmlspecialchars(
                                        $old['last_name'] ?? '',
                                        ENT_QUOTES,
                                        'UTF-8'
                                    ) ?>"
                                >

                            </div>

                            <?php if (isset($errors['last_name'])): ?>

                                <span class="error-text">

                                    <?= htmlspecialchars(
                                        $errors['last_name'],
                                        ENT_QUOTES,
                                        'UTF-8'
                                    ) ?>

                                </span>

                            <?php endif; ?>

                        </div>

                        <!-- DNI -->
                        <div class="col-12 col-md-6">

                            <label class="form-label-custom">
                                DNI / Documento
                            </label>

                            <div
                                class="input-box <?= isset($errors['dni']) ? 'input-error' : '' ?>"
                            >

                                <i class="fa-solid fa-id-card"></i>

                                <input
                                    type="text"
                                    id="dni_input"
                                    name="dni"
                                    placeholder="Ej. 42123456"
                                    maxlength="8"
                                    inputmode="numeric"
                                    value="<?= htmlspecialchars(
                                        $old['dni'] ?? '',
                                        ENT_QUOTES,
                                        'UTF-8'
                                    ) ?>"
                                >

                            </div>

                            <?php if (isset($errors['dni'])): ?>

                                <span class="error-text">

                                    <?= htmlspecialchars(
                                        $errors['dni'],
                                        ENT_QUOTES,
                                        'UTF-8'
                                    ) ?>

                                </span>

                            <?php endif; ?>

                        </div>

                        <!-- Fecha de nacimiento -->
                        <div class="col-12 col-md-6">

                            <label class="form-label-custom">
                                Fecha de nacimiento
                            </label>

                            <div
                                class="input-box <?= isset($errors['birth_date']) ? 'input-error' : '' ?>"
                            >

                                <i class="fa-solid fa-calendar-days"></i>

                                <input
                                    type="date"
                                    name="birth_date"
                                    value="<?= htmlspecialchars(
                                        $old['birth_date'] ?? '',
                                        ENT_QUOTES,
                                        'UTF-8'
                                    ) ?>"
                                >

                            </div>

                            <?php if (isset($errors['birth_date'])): ?>

                                <span class="error-text">

                                    <?= htmlspecialchars(
                                        $errors['birth_date'],
                                        ENT_QUOTES,
                                        'UTF-8'
                                    ) ?>

                                </span>

                            <?php endif; ?>

                        </div>

                        <!-- Provincia -->
                        <div class="col-12 col-md-6">

                            <label class="form-label-custom">
                                Provincia
                            </label>

                            <div
                                class="input-box <?= isset($errors['province']) ? 'input-error' : '' ?>"
                            >

                                <i class="fa-solid fa-map-location-dot"></i>

                                <select
                                    name="province"
                                    id="province_select"
                                    class="form-select-custom"
                                >

                                    <option
                                        value=""
                                        disabled
                                        <?= empty($old['province']) ? 'selected' : '' ?>
                                    >
                                        Seleccionar provincia
                                    </option>

                                    <?php

                                    $provinces = [
                                        'Buenos Aires',
                                        'CABA',
                                        'Catamarca',
                                        'Chaco',
                                        'Chubut',
                                        'Córdoba',
                                        'Corrientes',
                                        'Entre Ríos',
                                        'Formosa',
                                        'Jujuy',
                                        'La Pampa',
                                        'La Rioja',
                                        'Mendoza',
                                        'Misiones',
                                        'Neuquén',
                                        'Río Negro',
                                        'Salta',
                                        'San Juan',
                                        'San Luis',
                                        'Santa Cruz',
                                        'Santa Fe',
                                        'Santiago del Estero',
                                        'Tierra del Fuego',
                                        'Tucumán'
                                    ];

                                    foreach ($provinces as $province):

                                    ?>

                                        <option
                                            value="<?= htmlspecialchars(
                                                $province,
                                                ENT_QUOTES,
                                                'UTF-8'
                                            ) ?>"
                                            <?= ($old['province'] ?? '') === $province ? 'selected' : '' ?>
                                        >

                                            <?= htmlspecialchars(
                                                $province,
                                                ENT_QUOTES,
                                                'UTF-8'
                                            ) ?>

                                        </option>

                                    <?php endforeach; ?>

                                </select>

                            </div>

                            <?php if (isset($errors['province'])): ?>

                                <span class="error-text">

                                    <?= htmlspecialchars(
                                        $errors['province'],
                                        ENT_QUOTES,
                                        'UTF-8'
                                    ) ?>

                                </span>

                            <?php endif; ?>

                        </div>

                        <!-- Localidad -->
                        <div class="col-12 col-md-6">

                            <label class="form-label-custom">
                                Localidad
                            </label>

                            <div
                                class="input-box <?= isset($errors['city']) ? 'input-error' : '' ?>"
                            >

                                <i class="fa-solid fa-city"></i>

                                <input
                                    type="text"
                                    name="city"
                                    placeholder="Ej. Quilmes / La Plata"
                                    value="<?= htmlspecialchars(
                                        $old['city'] ?? '',
                                        ENT_QUOTES,
                                        'UTF-8'
                                    ) ?>"
                                >

                            </div>

                            <?php if (isset($errors['city'])): ?>

                                <span class="error-text">

                                    <?= htmlspecialchars(
                                        $errors['city'],
                                        ENT_QUOTES,
                                        'UTF-8'
                                    ) ?>

                                </span>

                            <?php endif; ?>

                        </div>

                    </div>

                    <!-- Contacto y cuenta -->
                    <div class="section-title">

                        <i class="fa-solid fa-at"></i>

                        Contacto y cuenta

                    </div>

                    <div class="row g-3 mb-4">

                        <!-- Usuario -->
                        <div class="col-12 col-md-6">

                            <label class="form-label-custom">
                                Nombre de usuario
                            </label>

                            <div
                                class="input-box <?= isset($errors['name']) ? 'input-error' : '' ?>"
                            >

                                <i class="fa-solid fa-at"></i>

                                <input
                                    type="text"
                                    name="name"
                                    placeholder="benjawz"
                                    value="<?= htmlspecialchars(
                                        $old['name'] ?? '',
                                        ENT_QUOTES,
                                        'UTF-8'
                                    ) ?>"
                                    required
                                >

                            </div>

                            <?php if (isset($errors['name'])): ?>

                                <span class="error-text">

                                    <?= htmlspecialchars(
                                        $errors['name'],
                                        ENT_QUOTES,
                                        'UTF-8'
                                    ) ?>

                                </span>

                            <?php endif; ?>

                        </div>

                        <!-- Email -->
                        <div class="col-12 col-md-6">

                            <label class="form-label-custom">
                                Correo electrónico
                            </label>

                            <div
                                class="input-box <?= isset($errors['email']) ? 'input-error' : '' ?>"
                            >

                                <i class="fa-solid fa-envelope"></i>

                                <input
                                    type="email"
                                    name="email"
                                    placeholder="hola123@gmail.com"
                                    value="<?= htmlspecialchars(
                                        $old['email'] ?? '',
                                        ENT_QUOTES,
                                        'UTF-8'
                                    ) ?>"
                                    required
                                >

                            </div>

                            <?php if (isset($errors['email'])): ?>

                                <span class="error-text">

                                    <?= htmlspecialchars(
                                        $errors['email'],
                                        ENT_QUOTES,
                                        'UTF-8'
                                    ) ?>

                                </span>

                            <?php endif; ?>

                        </div>

                        <!-- Teléfono -->
                        <div class="col-12 col-md-6">

                            <label class="form-label-custom">
                                Número de teléfono
                            </label>

                            <div class="row g-2">

                                <div class="col-5 col-sm-4">

                                    <select
                                        class="country-select w-100 <?= isset($errors['phone']) ? 'input-error' : '' ?>"
                                        name="phone_prefix"
                                    >

                                        <option value="+54">
                                            AR +54
                                        </option>

                                    </select>

                                </div>

                                <div class="col-7 col-sm-8">

                                    <div
                                        class="input-box <?= isset($errors['phone']) ? 'input-error' : '' ?>"
                                    >

                                        <i class="fa-solid fa-phone"></i>

                                        <input
                                            type="tel"
                                            name="phone"
                                            placeholder="1112345678"
                                            value="<?= htmlspecialchars(
                                                $old['phone'] ?? '',
                                                ENT_QUOTES,
                                                'UTF-8'
                                            ) ?>"
                                        >

                                    </div>

                                </div>

                            </div>

                            <?php if (isset($errors['phone'])): ?>

                                <span class="error-text">

                                    <?= htmlspecialchars(
                                        $errors['phone'],
                                        ENT_QUOTES,
                                        'UTF-8'
                                    ) ?>

                                </span>

                            <?php endif; ?>

                        </div>

                    </div>

                    <!-- Seguridad -->
                    <div class="section-title">

                        <i class="fa-solid fa-shield-halved"></i>

                        Seguridad

                    </div>

                    <div class="row g-3 mb-4">

                        <!-- Contraseña -->
                        <div class="col-12 col-md-6">

                            <label class="form-label-custom">
                                Contraseña
                            </label>

                            <div
                                class="input-box <?= isset($errors['password']) ? 'input-error' : '' ?>"
                            >

                                <i class="fa-solid fa-lock"></i>

                                <input
                                    type="password"
                                    name="password"
                                    id="password"
                                    placeholder="Contraseña"
                                    required
                                >

                                <i
                                    class="fa-solid fa-eye eye"
                                    data-target="password"
                                    role="button"
                                    tabindex="0"
                                    aria-label="Mostrar contraseña"
                                ></i>

                            </div>

                            <!-- Requisitos -->
                            <div
                                class="password-requirements is-hidden"
                                id="password-requirements"
                            >

                                <small class="d-block text-muted mb-1 fw-bold">

                                    La contraseña debe contener:

                                </small>

                                <ul class="list-unstyled m-0 p-0">

                                    <li
                                        id="req-length"
                                        class="req-item unfulfilled"
                                    >

                                        <i class="fa-solid fa-circle-xmark status-icon"></i>

                                        Al menos 8 caracteres

                                    </li>

                                    <li
                                        id="req-uppercase"
                                        class="req-item unfulfilled"
                                    >

                                        <i class="fa-solid fa-circle-xmark status-icon"></i>

                                        Una letra mayúscula

                                    </li>

                                    <li
                                        id="req-number"
                                        class="req-item unfulfilled"
                                    >

                                        <i class="fa-solid fa-circle-xmark status-icon"></i>

                                        Un número

                                    </li>

                                    <li
                                        id="req-special"
                                        class="req-item unfulfilled"
                                    >

                                        <i class="fa-solid fa-circle-xmark status-icon"></i>

                                        Un carácter especial

                                    </li>

                                </ul>

                            </div>

                            <?php if (isset($errors['password'])): ?>

                                <span class="error-text">

                                    <?= htmlspecialchars(
                                        $errors['password'],
                                        ENT_QUOTES,
                                        'UTF-8'
                                    ) ?>

                                </span>

                            <?php endif; ?>

                        </div>

                        <!-- Confirmar contraseña -->
                        <div class="col-12 col-md-6">

                            <label class="form-label-custom">
                                Confirmar contraseña
                            </label>

                            <div
                                class="input-box <?= isset($errors['repeatPassword']) ? 'input-error' : '' ?>"
                            >

                                <i class="fa-solid fa-lock"></i>

                                <input
                                    type="password"
                                    name="repeatPassword"
                                    id="repeatPassword"
                                    placeholder="Repetir contraseña"
                                    required
                                >

                                <i
                                    class="fa-solid fa-eye eye"
                                    data-target="repeatPassword"
                                    role="button"
                                    tabindex="0"
                                    aria-label="Mostrar contraseña"
                                ></i>

                            </div>

                            <?php if (isset($errors['repeatPassword'])): ?>

                                <span class="error-text">

                                    <?= htmlspecialchars(
                                        $errors['repeatPassword'],
                                        ENT_QUOTES,
                                        'UTF-8'
                                    ) ?>

                                </span>

                            <?php endif; ?>

                        </div>

                    </div>

                    <!-- Botón -->
                    <button
                        type="submit"
                        class="login-button mt-2"
                    >

                        Crear cuenta

                        <i class="fa-solid fa-arrow-right"></i>

                    </button>

                </form>

                <!-- Separador -->
                <div class="separator">

                    <span></span>

                    <p>o</p>

                    <span></span>

                </div>

                <!-- Volver al login -->
                <a
                    href="/src/views/auth/login.php"
                    class="register-button"
                >

                    <i class="fa-solid fa-arrow-left"></i>

                    Iniciar sesión

                </a>

            </div>

        </section>

    </main>

    <!-- Bootstrap JS -->
    <script
        src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"
    ></script>

    <script>

        document.addEventListener("DOMContentLoaded", () => {

            /*
            |--------------------------------------------------------------------------
            | Validación del DNI
            |--------------------------------------------------------------------------
            */

            const dniInput = document.getElementById("dni_input");

            if (dniInput) {

                dniInput.addEventListener("input", (event) => {

                    event.target.value = event.target.value
                        .replace(/\D/g, "")
                        .slice(0, 8);

                });

            }

            /*
            |--------------------------------------------------------------------------
            | Mostrar y ocultar contraseñas
            |--------------------------------------------------------------------------
            */

            document.querySelectorAll(".eye").forEach(icon => {

                icon.addEventListener("click", () => {

                    const input = document.querySelector(
                        `input[name="${icon.dataset.target}"]`
                    );

                    if (!input) {
                        return;
                    }

                    const isPassword = input.type === "password";

                    input.type = isPassword ? "text" : "password";

                    icon.classList.toggle(
                        "fa-eye",
                        !isPassword
                    );

                    icon.classList.toggle(
                        "fa-eye-slash",
                        isPassword
                    );

                });

                icon.addEventListener("keydown", event => {

                    if (event.key === "Enter" || event.key === " ") {

                        event.preventDefault();

                        icon.click();

                    }

                });

            });

            /*
            |--------------------------------------------------------------------------
            | Requisitos de contraseña
            |--------------------------------------------------------------------------
            */

            const passwordInput = document.getElementById("password");

            const requirementsBox = document.getElementById(
                "password-requirements"
            );

            if (!passwordInput || !requirementsBox) {
                return;
            }

            const reqLength = document.getElementById("req-length");

            const reqUppercase = document.getElementById("req-uppercase");

            const reqNumber = document.getElementById("req-number");

            const reqSpecial = document.getElementById("req-special");

            function updateItem(element, isMet) {

                if (!element) {
                    return;
                }

                const icon = element.querySelector(".status-icon");

                element.classList.toggle(
                    "fulfilled",
                    isMet
                );

                element.classList.toggle(
                    "unfulfilled",
                    !isMet
                );

                if (icon) {

                    icon.classList.toggle(
                        "fa-circle-check",
                        isMet
                    );

                    icon.classList.toggle(
                        "fa-circle-xmark",
                        !isMet
                    );

                }

            }

            function evaluatePassword() {

                const value = passwordInput.value;

                updateItem(
                    reqLength,
                    value.length >= 8
                );

                updateItem(
                    reqUppercase,
                    /[A-Z]/.test(value)
                );

                updateItem(
                    reqNumber,
                    /[0-9]/.test(value)
                );

                updateItem(
                    reqSpecial,
                    /[^A-Za-z0-9]/.test(value)
                );

            }

            passwordInput.addEventListener("focus", () => {

                evaluatePassword();

                requirementsBox.classList.remove("is-hidden");

            });

            passwordInput.addEventListener("blur", () => {

                requirementsBox.classList.add("is-hidden");

            });

            passwordInput.addEventListener(
                "input",
                evaluatePassword
            );

        });

    </script>

</body>

</html>