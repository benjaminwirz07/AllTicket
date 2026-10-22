<?php
require_once __DIR__ . '/../../config/bootstrap.php';

header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store');

function responderChatbot(array $data, int $status = 200): void
{
    http_response_code($status);
    echo json_encode($data, JSON_UNESCAPED_UNICODE | JSON_INVALID_UTF8_SUBSTITUTE);
    exit;
}

function normalizarMensajeChatbot(string $mensaje): string
{
    $mensaje = mb_strtolower($mensaje, 'UTF-8');
    return strtr($mensaje, [
        'á' => 'a',
        'é' => 'e',
        'í' => 'i',
        'ó' => 'o',
        'ú' => 'u',
        'ü' => 'u',
    ]);
}

function generarRespuestaChatbot(PDO $pdo, string $mensaje): string
{
    $normalizado = normalizarMensajeChatbot($mensaje);

    if (preg_match('/\b(hola|buenas|buenos dias|buenas tardes|buenas noches)\b/', $normalizado)) {
        return '¡Hola! Soy el asistente de AllTicket. Puedo ayudarte con recitales, entradas, compras y noticias.';
    }

    if (preg_match('/\b(comprar|compra|pagar|pago|checkout|tarjeta|transferencia)\b/', $normalizado)) {
        return 'Para comprar, elegí un recital, seleccioná el tipo y la cantidad de entradas y continuá al pago. '
            . 'AllTicket está en modo demo: no se realiza ningún cobro real.';
    }

    if (preg_match('/\b(noticias|novedades|publicaciones)\b/', $normalizado)) {
        $noticias = $pdo->query(
            'SELECT title FROM posts ORDER BY created_at DESC LIMIT 3'
        )->fetchAll(PDO::FETCH_COLUMN);

        if (!$noticias) {
            return 'Todavía no hay noticias publicadas. Podés volver a consultar más adelante.';
        }

        return "Estas son las últimas noticias:\n- " . implode("\n- ", $noticias)
            . "\nLeé más en la sección Noticias de la página principal.";
    }

    if (preg_match('/\b(eventos|recitales|conciertos|disponibles|entradas|shows)\b/', $normalizado)) {
        $eventos = $pdo->query(
            "SELECT c.name, c.date, v.name AS venue, v.city,
                    MIN(CASE WHEN t.available > 0 THEN t.price END) AS min_price
               FROM concerts c
               JOIN venues v ON v.id = c.venue_id
               LEFT JOIN tickets t ON t.concert_id = c.id
              WHERE c.status = 'active' AND c.date >= CURDATE()
              GROUP BY c.id, c.name, c.date, v.name, v.city
              ORDER BY c.date ASC
              LIMIT 5"
        )->fetchAll(PDO::FETCH_ASSOC);

        if (!$eventos) {
            return 'Por el momento no hay recitales próximos publicados.';
        }

        $lineas = [];
        foreach ($eventos as $evento) {
            $detalle = $evento['name'] . ' — ' . formatFecha($evento['date'])
                . ', ' . $evento['venue'] . ' (' . $evento['city'] . ')';
            if ($evento['min_price'] !== null) {
                $detalle .= ' — desde ' . formatPrecio($evento['min_price']);
            } else {
                $detalle .= ' — entradas no disponibles por el momento';
            }
            $lineas[] = $detalle;
        }

        return "Estos son algunos recitales próximos:\n- " . implode("\n- ", $lineas)
            . "\nEncontrás más detalles y disponibilidad en la sección Eventos.";
    }

    if (preg_match('/\b(ayuda|soporte|contacto|problema)\b/', $normalizado)) {
        return 'Puedo orientarte sobre recitales, entradas, compras y noticias. Para más ayuda, escribí a soporte@allticket.com.';
    }

    if (preg_match('/\b(chau|adios|hasta luego)\b/', $normalizado)) {
        return '¡Hasta luego! Gracias por visitar AllTicket.';
    }

    return 'No estoy seguro de haber entendido. Podés preguntarme por recitales, entradas, compras o noticias.';
}

if (!isset($_SESSION['chatbot_id']) || !is_string($_SESSION['chatbot_id'])) {
    $_SESSION['chatbot_id'] = bin2hex(random_bytes(32));
}
$chatId = $_SESSION['chatbot_id'];

if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    try {
        $stmt = $pdo->prepare(
            'SELECT sender, message, created_at
               FROM chatbot_messages
              WHERE chat_id = :chat_id
              ORDER BY id DESC
              LIMIT 100'
        );
        $stmt->execute(['chat_id' => $chatId]);
        $historial = array_reverse($stmt->fetchAll(PDO::FETCH_ASSOC));
        responderChatbot(['messages' => $historial]);
    } catch (PDOException $e) {
        error_log('Error al cargar el historial del chatbot: ' . $e->getMessage());
        responderChatbot(['error' => 'No se pudo cargar el chat. Intentá nuevamente.'], 500);
    }
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    responderChatbot(['error' => 'Método no permitido.'], 405);
}

$token = $_POST['csrf_token'] ?? null;
$sessionToken = $_SESSION['csrf_token'] ?? null;
if (
    !is_string($token) ||
    !is_string($sessionToken) ||
    $sessionToken === '' ||
    !hash_equals($sessionToken, $token)
) {
    responderChatbot(['error' => 'La sesión expiró. Recargá la página e intentá nuevamente.'], 403);
}

$mensaje = $_POST['mensaje'] ?? null;
if (!is_string($mensaje)) {
    responderChatbot(['error' => 'El mensaje no es válido.'], 400);
}

$mensaje = trim($mensaje);
if ($mensaje === '' || mb_strlen($mensaje, 'UTF-8') > 1000) {
    responderChatbot(['error' => 'Escribí un mensaje de hasta 1000 caracteres.'], 400);
}

try {
    $pdo->beginTransaction();

    $guardar = $pdo->prepare(
        'INSERT INTO chatbot_messages (chat_id, sender, message)
         VALUES (:chat_id, :sender, :message)'
    );
    $guardar->execute([
        'chat_id' => $chatId,
        'sender' => 'user',
        'message' => $mensaje,
    ]);

    $respuesta = generarRespuestaChatbot($pdo, $mensaje);
    $guardar->execute([
        'chat_id' => $chatId,
        'sender' => 'assistant',
        'message' => $respuesta,
    ]);

    $pdo->commit();
    responderChatbot(['reply' => $respuesta]);
} catch (PDOException $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    error_log('Error al guardar el mensaje del chatbot: ' . $e->getMessage());
    responderChatbot(['error' => 'No se pudo enviar el mensaje. Intentá nuevamente.'], 500);
}
