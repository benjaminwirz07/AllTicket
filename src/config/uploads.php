<?php
const IMG_MAX_BYTES = 2 * 1024 * 1024; // 2 MB

/**
 * Guarda una imagen subida y devuelve su ruta (ej: assets/img/uploads/concerts/ab12.jpg).
 * Devuelve null si no se subió ningún archivo. Lanza RuntimeException si hay un problema.
 */
function guardarImagen(array $archivo, string $subcarpeta): ?string
{
    if (!in_array($subcarpeta, ['artists', 'concerts'], true)) {
        throw new InvalidArgumentException('Carpeta de destino no válida.');
    }
    if (!isset($archivo['error']) || $archivo['error'] === UPLOAD_ERR_NO_FILE) {
        return null;
    }
    if ($archivo['error'] === UPLOAD_ERR_INI_SIZE || $archivo['error'] === UPLOAD_ERR_FORM_SIZE) {
        throw new RuntimeException('La imagen pesa demasiado (máximo 2 MB).');
    }
    if ($archivo['error'] !== UPLOAD_ERR_OK) {
        throw new RuntimeException('No se pudo subir la imagen. Probá de nuevo.');
    }
    if (!isset($archivo['size']) || !is_numeric($archivo['size']) || (int) $archivo['size'] > IMG_MAX_BYTES) {
        throw new RuntimeException('La imagen pesa demasiado (máximo 2 MB).');
    }
    $temporaryPath = $archivo['tmp_name'] ?? null;
    if (!is_string($temporaryPath) || !is_uploaded_file($temporaryPath)) {
        throw new RuntimeException('Archivo no válido.');
    }

    set_error_handler(static function (int $severity, string $message): bool {
        throw new ErrorException($message, 0, $severity);
    });
    try {
        $info = getimagesize($temporaryPath);
    } catch (ErrorException $e) {
        throw new RuntimeException('Formato no válido. Subí un JPG, PNG o WebP.', 0, $e);
    } finally {
        restore_error_handler();
    }

    $finfo = new finfo(FILEINFO_MIME_TYPE);
    $mime = $finfo->file($temporaryPath);
    $permitidos = [
        IMAGETYPE_JPEG => ['extension' => 'jpg', 'mime' => 'image/jpeg'],
        IMAGETYPE_PNG  => ['extension' => 'png', 'mime' => 'image/png'],
        IMAGETYPE_WEBP => ['extension' => 'webp', 'mime' => 'image/webp'],
    ];
    if (
        $info === false
        || !isset($permitidos[$info[2]])
        || $mime !== $permitidos[$info[2]]['mime']
        || $info[0] < 1
        || $info[1] < 1
        || $info[0] * $info[1] > 20000000
    ) {
        throw new RuntimeException('Formato no válido. Subí un JPG, PNG o WebP.');
    }

    $carpeta = dirname(__DIR__, 2) . '/assets/img/uploads/' . $subcarpeta;
    if (!is_dir($carpeta) && !mkdir($carpeta, 0755, true)) {
        throw new RuntimeException('No se pudo crear la carpeta de imágenes.');
    }

    $nombre = bin2hex(random_bytes(16)) . '.' . $permitidos[$info[2]]['extension'];
    $destino = $carpeta . DIRECTORY_SEPARATOR . $nombre;
    if (file_exists($destino) || !move_uploaded_file($temporaryPath, $destino)) {
        throw new RuntimeException('No se pudo guardar la imagen.');
    }

    return 'assets/img/uploads/' . $subcarpeta . '/' . $nombre;
}

/** Borra una imagen subida por el panel. Ignora rutas que no sean de uploads. */
function borrarImagenSubida(?string $ruta): void
{
    if (!$ruta || strpos($ruta, 'assets/img/uploads/') !== 0 || strpos($ruta, '..') !== false) {
        return;
    }
    $archivo = dirname(__DIR__, 2) . '/' . $ruta;
    if (is_file($archivo)) {
        if (!unlink($archivo)) {
            error_log('No se pudo borrar una imagen subida: ' . $archivo);
        }
    }
}