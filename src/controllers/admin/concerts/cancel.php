<?php
require_once __DIR__ . '/../../../config/bootstrap.php';
requireAdmin($pdo);

if ($_SERVER['REQUEST_METHOD'] !== 'POST' || empty($_POST['id'])) {
    http_response_code(405);
    exit('Método no permitido');
}

try {
    $stmt = $pdo->prepare("UPDATE concerts SET status = 'cancelled' WHERE id = :id");
    $stmt->execute(['id' => $_POST['id']]);
    $_SESSION['flash'] = ['type' => 'success', 'msg' => 'Recital cancelado. Ya no aparece en el home.'];
} catch (PDOException $e) {
    $_SESSION['flash'] = ['type' => 'danger', 'msg' => 'No se pudo cancelar el recital.'];
}

header('Location: /src/controllers/admin/concerts/index.php');
exit;