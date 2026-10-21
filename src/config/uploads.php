<?php
const IMG_MAX_BYTES = 2 * 1024 * 1024; // 2 MB

/**
 * Guarda una imagen subida y devuelve su ruta (ej: assets/img/uploads/concerts/ab12.jpg).
 * Devuelve null si no se subió ningún archivo. Lanza RuntimeException si hay un problema.
 */
function guardarImagen(array $archivo, string $subcarpeta): ?string
{
    if (!isset($archivo['error']) || $archivo['error'] === UPLOAD_ERR_NO_FILE) {
        return null;
    }
    if ($archivo['error'] === UPLOAD_ERR_INI_SIZE || $archivo['error'] === UPLOAD_ERR_FORM_SIZE) {
        throw new RuntimeException('La imagen pesa demasiado (máximo 2 MB).');
    }
    if ($archivo['error'] !== UPLOAD_ERR_OK) {
        throw new RuntimeException('No se pudo subir la imagen. Probá de nuevo.');
    }
    if ($archivo['size'] > IMG_MAX_BYTES) {
        throw new RuntimeException('La imagen pesa demasiado (máximo 2 MB).');
    }
    if (!is_uploaded_file($archivo['tmp_name'])) {
        throw new RuntimeException('Archivo no válido.');
    }

    // Se verifica el contenido real del archivo, no el nombre que trae
    $info = @getimagesize($archivo['tmp_name']);
    $permitidos = [
        IMAGETYPE_JPEG => 'jpg',
        IMAGETYPE_PNG  => 'png',
        IMAGETYPE_WEBP => 'webp',
    ];
    if ($info === false || !isset($permitidos[$info[2]])) {
        throw new RuntimeException('Formato no válido. Subí un JPG, PNG o WebP.');
    }

    $carpeta = dirname(__DIR__, 2) . '/assets/img/uploads/' . $subcarpeta;
    if (!is_dir($carpeta) && !mkdir($carpeta, 0755, true)) {
        throw new RuntimeException('No se pudo crear la carpeta de imágenes.');
    }

    $nombre = bin2hex(random_bytes(16)) . '.' . $permitidos[$info[2]];
    if (!move_uploaded_file($archivo['tmp_name'], $carpeta . '/' . $nombre)) {
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
        @unlink($archivo);
    }
}