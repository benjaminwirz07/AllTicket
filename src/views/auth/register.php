<?php

require_once __DIR__ . '/../../config/bootstrap.php';

if (isset($_SESSION['user'])) {
    header('Location: /src/views/index.php');
    exit;
}

$errors = $_SESSION['errors'] ?? [];
$old = $_SESSION['old'] ?? [];

unset($_SESSION['errors'], $_SESSION['old']);

/*
|--------------------------------------------------------------------------
| Helpers de presentación (solo para esta vista)
|--------------------------------------------------------------------------
| Se guardan en variables (closures) para no chocar con funciones
| que ya existan en bootstrap.php.
*/

// Escapa cualquier valor antes de imprimirlo
$e = static fn($value): string => htmlspecialchars(
    is_scalar($value) ? (string) $value : '',
    ENT_QUOTES,
    'UTF-8'
);

// Devuelve " input-error" si el campo tiene error
$hasError = static fn(string $key): string => isset($errors[$key]) ? ' input-error' : '';

// Atributos de accesibilidad cuando el campo tiene error
$aria = static fn(string $key): string => isset($errors[$key])
    ? ' aria-invalid="true" aria-describedby="' . $key . '-error"'
    : '';

// Mensaje de error debajo del campo (ya escapado)
$errorText = static function (string $key) use ($errors, $e): string {
    if (!isset($errors[$key])) {
        return '';
    }

    return '<span class="error-text" id="' . $key . '-error">'
        . '<i class="fa-solid fa-circle-exclamation" aria-hidden="true"></i>'
        . '<span>' . $e($errors[$key]) . '</span>'
        . '</span>';
};

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

    <!-- CSS compartido (login + registro) -->
    <link
        rel="stylesheet"
        href="/assets/css/custom.css"
    >

    <!-- CSS exclusivo del registro (va DESPUÉS de custom.css) -->
    <link
        rel="stylesheet"
        href="/assets/css/register.css"
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

                <!-- Encabezado -->
                <header class="auth-header">

                    <div class="user-icon">

                        <i class="fa-solid fa-user-plus"></i>

                    </div>

                    <h2 class="text-center">
                        Crear cuenta
                    </h2>

                    <p class="description text-center">

                        Completá tus datos para acceder a las mejores
                        entradas de recitales y eventos.

                    </p>

                </header>

                <!-- Error general -->
                <?php if (isset($errors['general'])): ?>

                    <div class="alert-danger-custom mb-4" role="alert">

                        <i class="fa-solid fa-circle-exclamation"></i>

                        <span><?= $e($errors['general']) ?></span>

                    </div>

                <?php endif; ?>

                <!-- Formulario -->
                <form
                    action="/src/controllers/auth/register.php"
                    method="POST"
                    novalidate
                >

                    <!-- ============================================
                         1. DATOS PERSONALES
                         ============================================ -->
                    <div class="form-section">

                        <div class="section-title">

                            <i class="fa-solid fa-address-card"></i>

                            <span>Datos personales</span>

                        </div>

                        <div class="row g-3">

                            <!-- Nombre -->
                            <div class="col-12 col-sm-6 col-lg-4">

                                <label class="form-label-custom" for="first_name">
                                    Nombre
                                </label>

                                <div class="input-box<?= $hasError('first_name') ?>">

                                    <i class="fa-solid fa-user" aria-hidden="true"></i>

                                    <input
                                        type="text"
                                        id="first_name"
                                        name="first_name"
                                        placeholder="Tu primer nombre"
                                        autocomplete="given-name"
                                        value="<?= $e($old['first_name'] ?? '') ?>"
                                        <?= $aria('first_name') ?>
                                    >

                                </div>

                                <?= $errorText('first_name') ?>

                            </div>

                            <!-- Segundo nombre -->
                            <div class="col-12 col-sm-6 col-lg-4">

                                <label class="form-label-custom" for="second_name">
                                    Segundo nombre
                                </label>

                                <div class="input-box<?= $hasError('second_name') ?>">

                                    <i class="fa-solid fa-user" aria-hidden="true"></i>

                                    <input
                                        type="text"
                                        id="second_name"
                                        name="second_name"
                                        placeholder="Tu segundo nombre"
                                        autocomplete="additional-name"
                                        value="<?= $e($old['second_name'] ?? '') ?>"
                                        <?= $aria('second_name') ?>
                                    >

                                </div>

                                <?= $errorText('second_name') ?>

                            </div>

                            <!-- Apellido -->
                            <div class="col-12 col-lg-4">

                                <label class="form-label-custom" for="last_name">
                                    Apellido
                                </label>

                                <div class="input-box<?= $hasError('last_name') ?>">

                                    <i class="fa-solid fa-user" aria-hidden="true"></i>

                                    <input
                                        type="text"
                                        id="last_name"
                                        name="last_name"
                                        placeholder="Tu apellido"
                                        autocomplete="family-name"
                                        value="<?= $e($old['last_name'] ?? '') ?>"
                                        <?= $aria('last_name') ?>
                                    >

                                </div>

                                <?= $errorText('last_name') ?>

                            </div>

                            <!-- DNI -->
                            <div class="col-12 col-md-6">

                                <label class="form-label-custom" for="dni_input">
                                    DNI / Documento
                                </label>

                                <div class="input-box<?= $hasError('dni') ?>">

                                    <i class="fa-solid fa-id-card" aria-hidden="true"></i>

                                    <input
                                        type="text"
                                        id="dni_input"
                                        name="dni"
                                        placeholder="Ingresá tu DNI"
                                        maxlength="8"
                                        inputmode="numeric"
                                        autocomplete="off"
                                        value="<?= $e($old['dni'] ?? '') ?>"
                                        <?= $aria('dni') ?>
                                    >

                                </div>

                                <?= isset($errors['dni'])
                                    ? $errorText('dni')
                                    : '<span class="field-hint">Solo números, sin puntos.</span>' ?>

                            </div>

                            <!-- Fecha de nacimiento -->
                            <div class="col-12 col-md-6">

                                <label class="form-label-custom" for="birth_date">
                                    Fecha de nacimiento
                                </label>

                                <div class="input-box<?= $hasError('birth_date') ?>">

                                    <i class="fa-solid fa-calendar-days" aria-hidden="true"></i>

                                    <input
                                        type="date"
                                        id="birth_date"
                                        name="birth_date"
                                        autocomplete="bday"
                                        value="<?= $e($old['birth_date'] ?? '') ?>"
                                        <?= $aria('birth_date') ?>
                                    >

                                </div>

                                <?= $errorText('birth_date') ?>

                            </div>

                            <!-- Provincia -->
                            <div class="col-12 col-md-6">

                                <label class="form-label-custom" for="province_select">
                                    Provincia
                                </label>

                                <div class="input-box<?= $hasError('province') ?>">

                                    <i class="fa-solid fa-map-location-dot" aria-hidden="true"></i>

                                    <select
                                        name="province"
                                        id="province_select"
                                        class="form-select-custom"
                                        <?= $aria('province') ?>
                                    >

                                        <option
                                            value=""
                                            disabled
                                            <?= empty($old['province']) ? 'selected' : '' ?>
                                        >
                                            Seleccioná tu provincia
                                        </option>

                                        <?php foreach ($provinces as $province): ?>

                                            <option
                                                value="<?= $e($province) ?>"
                                                <?= ($old['province'] ?? '') === $province ? 'selected' : '' ?>
                                            >
                                                <?= $e($province) ?>
                                            </option>

                                        <?php endforeach; ?>

                                    </select>

                                    <i class="fa-solid fa-chevron-down select-caret" aria-hidden="true"></i>

                                </div>

                                <?= $errorText('province') ?>

                            </div>

                            <!-- Localidad -->
                            <div class="col-12 col-md-6">

                                <label class="form-label-custom" for="city">
                                    Localidad
                                </label>

                                <div class="input-box<?= $hasError('city') ?>">

                                    <i class="fa-solid fa-city" aria-hidden="true"></i>

                                    <input
                                        type="text"
                                        id="city"
                                        name="city"
                                        placeholder="Ingresá tu localidad"
                                        autocomplete="address-level2"
                                        value="<?= $e($old['city'] ?? '') ?>"
                                        <?= $aria('city') ?>
                                    >

                                </div>

                                <?= $errorText('city') ?>

                            </div>

                        </div>

                    </div>

                    <!-- ============================================
                         2. CONTACTO Y CUENTA
                         ============================================ -->
                    <div class="form-section">

                        <div class="section-title">

                            <i class="fa-solid fa-at"></i>

                            <span>Contacto y cuenta</span>

                        </div>

                        <div class="row g-3">

                            <!-- Email -->
                            <div class="col-12">

                                <label class="form-label-custom" for="email">
                                    Correo electrónico
                                </label>

                                <div class="input-box<?= $hasError('email') ?>">

                                    <i class="fa-solid fa-envelope" aria-hidden="true"></i>

                                    <input
                                        type="email"
                                        id="email"
                                        name="email"
                                        placeholder="Ingresá el correo de tu cuenta"
                                        autocomplete="email"
                                        value="<?= $e($old['email'] ?? '') ?>"
                                        required
                                        <?= $aria('email') ?>
                                    >

                                </div>

                                <?= $errorText('email') ?>

                            </div>

                            <!-- Usuario -->
                            <div class="col-12 col-md-6">

                                <label class="form-label-custom" for="username">
                                    Nombre de usuario
                                </label>

                                <div class="input-box<?= $hasError('name') ?>">

                                    <i class="fa-solid fa-at" aria-hidden="true"></i>

                                    <input
                                        type="text"
                                        id="username"
                                        name="name"
                                        placeholder="Elegí tu nombre de usuario"
                                        autocomplete="username"
                                        value="<?= $e($old['name'] ?? '') ?>"
                                        required
                                        <?= $aria('name') ?>
                                    >

                                </div>

                                <?= $errorText('name') ?>

                            </div>

                            <!-- Teléfono -->
                            <div class="col-12 col-md-6">

                                <label class="form-label-custom" for="phone">
                                    Número de teléfono
                                </label>

                                <div class="input-box<?= $hasError('phone') ?>">

                                    <i class="fa-solid fa-phone" aria-hidden="true"></i>

                                    <select
                                        class="phone-prefix"
                                        id="phone_prefix"
                                        name="phone_prefix"
                                        aria-label="Prefijo del país"
                                    >

                                        <option value="+54">
                                            AR +54
                                        </option>

                                    </select>

                                    <span class="phone-divider" aria-hidden="true"></span>

                                    <input
                                        type="tel"
                                        id="phone"
                                        name="phone"
                                        placeholder="1112345678"
                                        inputmode="tel"
                                        autocomplete="tel-national"
                                        value="<?= $e($old['phone'] ?? '') ?>"
                                        <?= $aria('phone') ?>
                                    >

                                </div>

                                <?= isset($errors['phone'])
                                    ? $errorText('phone')
                                    : '<span class="field-hint">Código de área + número, sin 0 ni 15.</span>' ?>

                            </div>

                        </div>

                    </div>

                    <!-- ============================================
                         3. SEGURIDAD
                         ============================================ -->
                    <div class="form-section">

                        <div class="section-title">

                            <i class="fa-solid fa-shield-halved"></i>

                            <span>Seguridad</span>

                        </div>

                        <div class="row g-3">

                            <!-- Contraseña -->
                            <div class="col-12 col-md-6">

                                <label class="form-label-custom" for="password">
                                    Contraseña
                                </label>

                                <div class="field-control">

                                    <div class="input-box<?= $hasError('password') ?>">

                                        <i class="fa-solid fa-lock" aria-hidden="true"></i>

                                        <input
                                            type="password"
                                            name="password"
                                            id="password"
                                            placeholder="Ingresá tu contraseña"
                                            autocomplete="new-password"
                                            aria-describedby="password-requirements<?= isset($errors['password']) ? ' password-error' : '' ?>"
                                            <?= isset($errors['password']) ? 'aria-invalid="true"' : '' ?>
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

                                    <!-- Requisitos (flotan debajo del campo, no empujan el layout) -->
                                    <div
                                        class="password-requirements is-hidden"
                                        id="password-requirements"
                                    >

                                        <span class="req-title">
                                            La contraseña debe contener
                                        </span>

                                        <ul class="list-unstyled m-0 p-0">

                                            <li id="req-length" class="req-item unfulfilled">
                                                <i class="fa-solid fa-circle-xmark status-icon"></i>
                                                Al menos 8 caracteres
                                            </li>

                                            <li id="req-uppercase" class="req-item unfulfilled">
                                                <i class="fa-solid fa-circle-xmark status-icon"></i>
                                                Una letra mayúscula
                                            </li>

                                            <li id="req-number" class="req-item unfulfilled">
                                                <i class="fa-solid fa-circle-xmark status-icon"></i>
                                                Un número
                                            </li>

                                            <li id="req-special" class="req-item unfulfilled">
                                                <i class="fa-solid fa-circle-xmark status-icon"></i>
                                                Un carácter especial
                                            </li>

                                        </ul>

                                    </div>

                                </div>

                                <?= $errorText('password') ?>

                            </div>

                            <!-- Confirmar contraseña -->
                            <div class="col-12 col-md-6">

                                <label class="form-label-custom" for="repeatPassword">
                                    Confirmar contraseña
                                </label>

                                <div class="input-box<?= $hasError('repeatPassword') ?>">

                                    <i class="fa-solid fa-lock" aria-hidden="true"></i>

                                    <input
                                        type="password"
                                        name="repeatPassword"
                                        id="repeatPassword"
                                        placeholder="Repetí tu contraseña"
                                        autocomplete="new-password"
                                        required
                                        <?= $aria('repeatPassword') ?>
                                    >

                                    <i
                                        class="fa-solid fa-eye eye"
                                        data-target="repeatPassword"
                                        role="button"
                                        tabindex="0"
                                        aria-label="Mostrar contraseña"
                                    ></i>

                                </div>

                                <?= $errorText('repeatPassword') ?>

                            </div>

                        </div>

                    </div>

                    <!-- Botón -->
                    <div class="form-actions">

                        <button
                            type="submit"
                            class="login-button"
                        >

                            Crear cuenta

                            <i class="fa-solid fa-arrow-right"></i>

                        </button>

                    </div>

                </form>

                <!-- Volver al login -->
                <div class="auth-footer">

                    <div class="separator">

                        <span></span>

                        <p>¿Ya tenés cuenta?</p>

                        <span></span>

                    </div>

                    <a
                        href="/src/views/auth/login.php"
                        class="register-button"
                    >

                        <i class="fa-solid fa-arrow-left"></i>

                        Iniciar sesión

                    </a>

                </div>

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