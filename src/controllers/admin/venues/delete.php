<?php
require_once __DIR__ . '/../../../config/bootstrap.php';
requireAdmin($pdo);

if ($_SERVER['REQUEST_METHOD'] !== 'POST' || empty($_POST['id'])) {
    http_response_code(405);
    exit('Método no permitido');
}

try {
    $stmt = $pdo->prepare('DELETE FROM venues WHERE id = :id');
    $stmt->execute(['id' => $_POST['id']]);
    $_SESSION['flash'] = ['type' => 'success', 'msg' => 'Lugar eliminado.'];
} catch (PDOException $e) {
    // 23000 = lo bloquea una clave foránea (el lugar tiene recitales)
    if ($e->getCode() === '23000') {
        $_SESSION['flash'] = ['type' => 'danger', 'msg' => 'No se puede eliminar: el lugar tiene recitales asociados.'];
    } else {
        $_SESSION['flash'] = ['type' => 'danger', 'msg' => 'Error al eliminar el lugar.'];
    }
}

header('Location: /src/controllers/admin/venues/index.php');
exit;