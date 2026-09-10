<?php
require_once __DIR__ . '/../../config/bootstrap.php';

if (isset($_SESSION['user'])) {
    header('Location: ../index.php');
    exit;
}

// Obtenemos errores y datos persistidos
$errors = $_SESSION['errors'] ?? [];
$old    = $_SESSION['old'] ?? [];

// Limpiamos la sesión inmediatamente para no mantener alertas en futuras recargas
unset($_SESSION['errors'], $_SESSION['old']);
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>AllTicket - Crear Cuenta</title>
    <!-- Estilos de tu interfaz -->
    <link rel="stylesheet" href="../../../assets/css/custom.css">
    <!-- Font Awesome para los íconos (usado por .ticket-icon i, .user-icon i, etc.) -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
</head>
<body>

<div class="login-page">

    <div class="brand">
        <div class="ticket-icon">
            <i class="fa-solid fa-ticket"></i>
        </div>
        <h1><span class="all">All</span><span class="ticket">Ticket</span></h1>
        <p class="slogan">TU PRÓXIMO EVENTO</p>
    </div>

    <section class="login-card">
        <div class="user-icon">
            <i class="fa-solid fa-user"></i>
        </div>
        <h2>Crear cuenta</h2>
        <p class="description">Creá tu cuenta en AllTicket para comenzar a disfrutar de tus próximos eventos.</p>

        <!-- Alerta General de Error si aplica -->
        <?php if (isset($errors['general'])): ?>
            <div class="alert alert-danger">
                <?= htmlspecialchars($errors['general']) ?>
            </div>
        <?php endif; ?>

        <form action="../../controllers/auth/register.php" method="POST" novalidate>

            <!-- Campo Email -->
            <div class="input-box <?= isset($errors['email']) ? 'input-error' : '' ?>">
                <i class="fa-solid fa-envelope"></i>
                <input
                    type="email"
                    name="email"
                    placeholder="hola123@gmail.com"
                    value="<?= htmlspecialchars($old['email'] ?? '') ?>"
                    required
                >
            </div>
            <?php if (isset($errors['email'])): ?>
                <span class="error-text"><?= htmlspecialchars($errors['email']) ?></span>
            <?php endif; ?>

            <!-- Campo Usuario -->
            <div class="input-box <?= isset($errors['name']) ? 'input-error' : '' ?>">
                <i class="fa-solid fa-user"></i>
                <input
                    type="text"
                    name="name"
                    placeholder="benjawz"
                    value="<?= htmlspecialchars($old['name'] ?? '') ?>"
                    required
                >
            </div>
            <?php if (isset($errors['name'])): ?>
                <span class="error-text"><?= htmlspecialchars($errors['name']) ?></span>
            <?php endif; ?>

            <!-- Campo Contraseña -->
            <div class="input-box <?= isset($errors['password']) ? 'input-error' : '' ?>">
                <i class="fa-solid fa-lock"></i>
                <input
                    type="password"
                    name="password"
                    placeholder="Contraseña"
                    required
                >
                <i class="fa-solid fa-eye eye" data-target="password"></i>
            </div>
            <?php if (isset($errors['password'])): ?>
                <span class="error-text"><?= htmlspecialchars($errors['password']) ?></span>
            <?php endif; ?>

            <!-- Campo Repetir Contraseña -->
            <div class="input-box <?= isset($errors['repeatPassword']) ? 'input-error' : '' ?>">
                <i class="fa-solid fa-lock"></i>
                <input
                    type="password"
                    name="repeatPassword"
                    placeholder="Repetir contraseña"
                    required
                >
                <i class="fa-solid fa-eye eye" data-target="repeatPassword"></i>
            </div>
            <?php if (isset($errors['repeatPassword'])): ?>
                <span class="error-text"><?= htmlspecialchars($errors['repeatPassword']) ?></span>
            <?php endif; ?>

            <button type="submit" class="login-button">
                Crear cuenta <i class="fa-solid fa-arrow-right"></i>
            </button>
        </form>

        <div class="separator">
            <span></span>
            <p>o</p>
            <span></span>
        </div>

        <a href="login.php" class="register-button">
            <i class="fa-solid fa-arrow-left"></i> Iniciar sesión
        </a>
    </section>
</div>

<script>
    // Alterna mostrar/ocultar contraseña
    document.querySelectorAll('.eye').forEach(icon => {
        icon.addEventListener('click', () => {
            const input = document.querySelector(`input[name="${icon.dataset.target}"]`);
            const isPassword = input.type === 'password';
            input.type = isPassword ? 'text' : 'password';
            icon.classList.toggle('fa-eye');
            icon.classList.toggle('fa-eye-slash');
        });
    });
</script>

</body>
</html>