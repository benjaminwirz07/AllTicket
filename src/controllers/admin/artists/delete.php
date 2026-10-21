<?php
require_once __DIR__ . '/../../../config/bootstrap.php';
requireAdmin($pdo);

if ($_SERVER['REQUEST_METHOD'] !== 'POST' || empty($_POST['id'])) {
    http_response_code(405);
    exit('Método no permitido');
}

try {
    $stmt = $pdo->prepare('DELETE FROM artists WHERE id = :id');
    $stmt->execute(['id' => $_POST['id']]);
    $_SESSION['flash'] = ['type' => 'success', 'msg' => 'Artista eliminado.'];
} catch (PDOException $e) {
    if ($e->getCode() === '23000') {
        $_SESSION['flash'] = ['type' => 'danger', 'msg' => 'No se puede eliminar: el artista tiene recitales asociados.'];
    } else {
        $_SESSION['flash'] = ['type' => 'danger', 'msg' => 'Error al eliminar el artista.'];
    }
}

header('Location: /src/controllers/admin/artists/index.php');
exit;