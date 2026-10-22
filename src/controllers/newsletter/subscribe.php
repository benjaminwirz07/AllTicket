dale<?php
require_once __DIR__ . '/../../config/bootstrap.php';

requireMethod('POST');

requireCsrfToken();

$destino = '/src/views/index.php';
$email = inputEmail($_POST, 'email');

// Limita a cinco intentos por IP cada quince minutos sin guardar la IP en claro.
$ip = $_SERVER['REMOTE_ADDR'] ?? '';
if (!is_string($ip) || filter_var($ip, FILTER_VALIDATE_IP) === false) {
    $ip = 'unknown';
}
$ipHash = hash('sha256', $ip);

try {
    $pdo->beginTransaction();
    $insert = $pdo->prepare(
        'INSERT IGNORE INTO newsletter_rate_limits (ip_hash, window_started_at, attempts)
         VALUES (:ip_hash, CURRENT_TIMESTAMP, 0)'
    );
    $insert->execute(['ip_hash' => $ipHash]);

    $limit = $pdo->prepare(
        'SELECT window_started_at, attempts
           FROM newsletter_rate_limits
          WHERE ip_hash = :ip_hash
          FOR UPDATE'
    );
    $limit->execute(['ip_hash' => $ipHash]);
    $record = $limit->fetch(PDO::FETCH_ASSOC);

    if (!$record) {
        throw new RuntimeException('No se pudo comprobar el límite de suscripciones.');
    }

    $windowStart = strtotime($record['window_started_at']);
    if ($windowStart === false || $windowStart <= time() - 900) {
        $update = $pdo->prepare(
            'UPDATE newsletter_rate_limits
                SET window_started_at = CURRENT_TIMESTAMP, attempts = 1
              WHERE ip_hash = :ip_hash'
        );
        $update->execute(['ip_hash' => $ipHash]);
        $attempts = 1;
    } else {
        $attempts = (int) $record['attempts'] + 1;
        if ($attempts <= 5) {
            $update = $pdo->prepare(
                'UPDATE newsletter_rate_limits SET attempts = :attempts WHERE ip_hash = :ip_hash'
            );
            $update->execute(['attempts' => $attempts, 'ip_hash' => $ipHash]);
        }
    }

    $pdo->commit();
    try {
        $pdo->exec(
            'DELETE FROM newsletter_rate_limits
              WHERE window_started_at < CURRENT_TIMESTAMP - INTERVAL 1 DAY'
        );
    } catch (PDOException $cleanupError) {
        error_log('No se pudieron limpiar límites vencidos del newsletter: ' . $cleanupError->getMessage());
    }

    if ($attempts > 5) {
        redirectTo($destino . '?newsletter=rate_limited#newsletter');
    }
} catch (PDOException $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    error_log('Error al aplicar rate limit del newsletter: ' . $e->getMessage());
    redirectTo($destino . '?newsletter=error#newsletter');
} catch (RuntimeException $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    error_log('Error al aplicar rate limit del newsletter: ' . $e->getMessage());
    redirectTo($destino . '?newsletter=error#newsletter');
}

if ($email === '' || strlen($email) > 254 || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
    redirectTo($destino . '?newsletter=invalid#newsletter');
}

try {
    $stmt = $pdo->prepare('INSERT IGNORE INTO newsletter_subscribers (email) VALUES (:email)');
    $stmt->execute(['email' => strtolower($email)]);
    $estado = 'ok';
} catch (PDOException $e) {
    error_log('Error al suscribir al newsletter: ' . $e->getMessage());
    $estado = 'error';
}

redirectTo($destino . '?newsletter=' . $estado . '#newsletter');
