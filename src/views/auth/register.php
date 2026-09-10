<?php
require_once __DIR__ . '/../../config/bootstrap.php';

if (isset($_SESSION['user'])) {
    header('Location: ' . BASE_URL . '/src/views/index.php');
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
    <link rel="stylesheet" href="<?= BASE_URL ?>/assets/css/style.css">
</head>
<body>

<main class="auth-container">
    <section class="login-card">
        <div class="card-header">
            <div class="user-icon">👤</div>
            <h2>Crear cuenta</h2>
            <p>Creá tu cuenta en AllTicket para comenzar a disfrutar de tus próximos eventos.</p>
        </div>

        <!-- Alerta General de Error si aplica -->
        <?php if (isset($errors['general'])): ?>
            <div class="alert alert-danger">
                <?= htmlspecialchars($errors['general']) ?>
            </div>
        <?php endif; ?>

        <form action="<?= BASE_URL ?>/src/controllers/auth/register.php" method="POST" novalidate>
            
            <!-- Campo Email -->
            <div class="input-group">
                <input 
                    type="email" 
                    name="email" 
                    placeholder="hola123@gmail.com" 
                    value="<?= htmlspecialchars($old['email'] ?? '') ?>"
                    class="<?= isset($errors['email']) ? 'input-error' : '' ?>"
                    required
                >
                <?php if (isset($errors['email'])): ?>
                    <span class="error-text"><?= htmlspecialchars($errors['email']) ?></span>
                <?php endif; ?>
            </div>

            <!-- Campo Usuario -->
            <div class="input-group">
                <input 
                    type="text" 
                    name="name" 
                    placeholder="benjawz" 
                    value="<?= htmlspecialchars($old['name'] ?? '') ?>"
                    class="<?= isset($errors['name']) ? 'input-error' : '' ?>"
                    required
                >
                <?php if (isset($errors['name'])): ?>
                    <span class="error-text"><?= htmlspecialchars($errors['name']) ?></span>
                <?php endif; ?>
            </div>

            <!-- Campo Contraseña -->
            <div class="input-group">
                <input 
                    type="password" 
                    name="password" 
                    placeholder="Contraseña"
                    class="<?= isset($errors['password']) ? 'input-error' : '' ?>"
                    required
                >
                <?php if (isset($errors['password'])): ?>
                    <span class="error-text"><?= htmlspecialchars($errors['password']) ?></span>
                <?php endif; ?>
            </div>

            <!-- Campo Repetir Contraseña -->
            <div class="input-group">
                <input 
                    type="password" 
                    name="repeatPassword" 
                    placeholder="Repetir contraseña"
                    class="<?= isset($errors['repeatPassword']) ? 'input-error' : '' ?>"
                    required
                >
                <?php if (isset($errors['repeatPassword'])): ?>
                    <span class="error-text"><?= htmlspecialchars($errors['repeatPassword']) ?></span>
                <?php endif; ?>
            </div>

            <button type="submit" class="btn-primary">Crear cuenta &rarr;</button>
        </form>

        <div class="card-footer">
            <p>¿Ya tenés una cuenta?</p>
            <a href="<?= BASE_URL ?>/src/views/auth/login.php" class="btn-secondary">&larr; Iniciar sesión</a>
        </div>
    </section>
</main>

</body>
</html>