<?php
require_once __DIR__ . '/../../../config/bootstrap.php';
requireAuth();
requireAdmin($pdo);

requireMethod('POST');
requireCsrfToken();

$id = inputUuid($_POST, 'id');
if ($id === '') {
    http_response_code(400);
    exit('Solicitud no válida');
}

try {
    $stmt = $pdo->prepare("UPDATE concerts SET status = 'cancelled' WHERE id = :id");
    $stmt->execute(['id' => $id]);
    setFlash('success', 'Recital cancelado. Ya no aparece en el home.');
} catch (PDOException $e) {
    error_log('Error al cancelar el recital: ' . $e->getMessage());
    setFlash('danger', 'No se pudo cancelar el recital.');
}

redirectTo('/src/controllers/admin/concerts/index.php');