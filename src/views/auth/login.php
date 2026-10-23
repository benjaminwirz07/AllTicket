<?php
require_once __DIR__ . '/../../config/bootstrap.php';

requireGuest();

$errors  = $_SESSION['errors'] ?? [];
$old     = $_SESSION['old'] ?? [];
$flash   = consumeFlash();
$success = $_SESSION['success'] ?? ($flash['msg'] ?? null);
unset($_SESSION['errors'], $_SESSION['old'], $_SESSION['success']);

$e = static fn($value): string => htmlspecialchars(
    is_scalar($value) ? (string) $value : '',
    ENT_QUOTES,
    'UTF-8'
);
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>AllTicket - Iniciar Sesión</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">
    <link rel="stylesheet" href="<?= e(url('/assets/css/loginRegistro.css')) ?>">
</head>
<body>
    <main class="login-page">
        <div class="brand-logo-container text-center mb-4">
            <img src="<?= e(url('/assets/img/logo-Allticket2.png')) ?>" alt="AllTicket Logo" class="brand-logo img-fluid">
        </div>

        <section class="login-card text-center">
            <div class="allticket-auth-blob" aria-hidden="true"></div>
            <div class="allticket-auth-bg">
                <div class="user-icon"><i class="fa-regular fa-user"></i></div>
                <h2>Bienvenido</h2>
                <p class="description">
                    Iniciá sesión en tu cuenta para continuar<br>
                    o creá una nueva si aún no tenés una.
                </p>

                <?php if ($success): ?>
                    <div class="alert-success-custom mb-3" role="status">
                        <i class="fa-solid fa-circle-check"></i>
                        <span><?= $e($success) ?></span>
                    </div>
                <?php endif; ?>

                <?php if (!empty($errors['auth']) || !empty($errors['db'])): ?>
                    <div class="alert-danger-custom mb-3" role="alert">
                        <i class="fa-solid fa-circle-exclamation"></i>
                        <span><?= $e($errors['auth'] ?? $errors['db']) ?></span>
                    </div>
                <?php endif; ?>

                <form action="<?= e(url('/src/controllers/auth/login.php')) ?>" method="POST">
                    <input type="hidden" name="csrf_token" value="<?= $e(csrfToken()) ?>">
                    <div class="mb-3 text-start">
                        <label class="form-label-custom" for="email">Correo electrónico</label>
                        <div class="input-box<?= isset($errors['email']) ? ' input-error' : '' ?>">
                            <i class="fa-regular fa-envelope"></i>
                            <input type="email" id="email" name="email"
                                placeholder="Ingresá el correo de tu cuenta"
                                value="<?= $e($old['email'] ?? '') ?>" required>
                        </div>
                        <?php if (isset($errors['email'])): ?>
                            <span class="error-text">
                                <i class="fa-solid fa-circle-exclamation"></i>
                                <span><?= $e($errors['email']) ?></span>
                            </span>
                        <?php endif; ?>
                    </div>

                    <div class="mb-4 text-start">
                        <label class="form-label-custom" for="password">Contraseña</label>
                        <div class="input-box<?= isset($errors['password']) ? ' input-error' : '' ?>">
                            <i class="fa-solid fa-lock"></i>
                            <input type="password" name="password" id="password" placeholder="Tu contraseña" required>
                            <i class="fa-regular fa-eye eye" data-target="password" role="button" tabindex="0" aria-label="Mostrar contraseña"></i>
                        </div>
                        <?php if (isset($errors['password'])): ?>
                            <span class="error-text">
                                <i class="fa-solid fa-circle-exclamation"></i>
                                <span><?= $e($errors['password']) ?></span>
                            </span>
                        <?php endif; ?>
                    </div>

                    <button type="submit" class="login-button">
                        Iniciar sesión <i class="fa-solid fa-arrow-right"></i>
                    </button>
                </form>

                <div class="separator">
                    <span></span>
                    <p>¿No tenés una cuenta?</p>
                    <span></span>
                </div>

                <a href="<?= e(url('/src/views/auth/register.php')) ?>" class="register-button">
                    <i class="fa-regular fa-user"></i>
                    Crear cuenta
                </a>
            </div>
        </section>
    </main>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        document.addEventListener("DOMContentLoaded", () => {
            document.querySelectorAll(".eye").forEach(icon => {
                icon.addEventListener("click", () => {
                    const input = document.querySelector(`input[name="${icon.dataset.target}"]`);
                    if (!input) return;
                    const isPassword = input.type === "password";
                    input.type = isPassword ? "text" : "password";
                    icon.classList.toggle("fa-eye", !isPassword);
                    icon.classList.toggle("fa-eye-slash", isPassword);
                    icon.setAttribute("aria-label", isPassword ? "Ocultar contraseña" : "Mostrar contraseña");
                });
                icon.addEventListener("keydown", event => {
                    if (event.key === "Enter" || event.key === " ") {
                        event.preventDefault();
                        icon.click();
                    }
                });
            });
        });
    </script>
</body>
</html>