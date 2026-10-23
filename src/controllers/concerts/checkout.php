<?php
require_once __DIR__ . '/../../config/bootstrap.php';

$loginUrl = url('/src/views/auth/login.php');
$userId = currentUserId();
$compradorNombre = '';
$compradorUsuario = '';
if ($userId !== null) {
    $stmtComprador = $pdo->prepare('SELECT first_name, last_name, usuario FROM users WHERE id = :id LIMIT 1');
    $stmtComprador->execute(['id' => $userId]);
    $comprador = $stmtComprador->fetch(PDO::FETCH_ASSOC);
    if ($comprador) {
        $compradorNombre = trim($comprador['first_name'] . ' ' . $comprador['last_name']);
        $compradorUsuario = $comprador['usuario'];
    }
}

$modoDemo = true;
$transferAlias = 'ALLTICKET.PAGOS';
$transferTitular = 'AllTicket S.A.';

$metodos = [
    'mercadopago'   => ['nombre' => 'Mercado Pago', 'icono' => 'bi-wallet2', 'desc' => 'Pagá con tu dinero en cuenta, tarjetas guardadas o cuotas.', 'instant' => true],
    'tarjeta'       => ['nombre' => 'Tarjeta de crédito o débito', 'icono' => 'bi-credit-card-2-front', 'desc' => 'Visa, Mastercard y American Express.', 'instant' => true],
    'transferencia' => ['nombre' => 'Transferencia bancaria', 'icono' => 'bi-bank', 'desc' => 'Transferí desde tu home banking. Se acredita al confirmar el pago.', 'instant' => false],
    'efectivo'      => ['nombre' => 'Efectivo (Rapipago / Pago Fácil)', 'icono' => 'bi-cash-coin', 'desc' => 'Pagá en sucursal con tu número de pedido.', 'instant' => false],
];

$maxEntradas = 6;

if (!function_exists('esNumerada')) {
    function esNumerada($tipo) {
        return stripos($tipo, 'vip') !== false || stripos($tipo, 'platea') !== false;
    }
}
if (!function_exists('uuidv4')) {
    function uuidv4() {
        $d = random_bytes(16);
        $d[6] = chr((ord($d[6]) & 0x0f) | 0x40);
        $d[8] = chr((ord($d[8]) & 0x3f) | 0x80);
        return vsprintf('%s%s-%s-%s-%s-%s%s%s', str_split(bin2hex($d), 4));
    }
}
if (!function_exists('textoButaca')) {
    function textoButaca($codigo) {
        $p = explode('-', $codigo);
        return 'Fila ' . $p[0] . ' · Butaca ' . $p[1];
    }
}
// CAMBIO D: vence reservas held (24 h) y ventas pendientes; devuelve el stock
if (!function_exists('vencerReservas')) {
    function vencerReservas(PDO $pdo) {
        $propia = !$pdo->inTransaction();
        if ($propia) { $pdo->beginTransaction(); }
        try {
            $ids = $pdo->query(
                "SELECT s.id FROM sales s
                  WHERE s.status = 'pending'
                    AND (s.sale_date <= NOW() - INTERVAL 24 HOUR
                         OR EXISTS (SELECT 1 FROM seat_reservations r
                                     WHERE r.sale_id = s.id AND r.status = 'held' AND r.expires_at <= NOW()))"
            )->fetchAll(PDO::FETCH_COLUMN);

            $cancelar = $pdo->prepare("UPDATE sales SET status = 'cancelled' WHERE id = ? AND status = 'pending'");
            $devolver = $pdo->prepare(
                "UPDATE tickets t JOIN sale_details d ON d.ticket_id = t.id
                    SET t.available = LEAST(t.stock, t.available + d.quantity)
                  WHERE d.sale_id = ?"
            );
            $liberar = $pdo->prepare("UPDATE seat_reservations SET status = 'expired' WHERE sale_id = ? AND status = 'held'");

            foreach ($ids as $id) {
                $cancelar->execute([$id]);
                if ($cancelar->rowCount() === 1) {
                    $devolver->execute([$id]);
                    $liberar->execute([$id]);
                }
            }

            $pdo->exec("UPDATE seat_reservations SET status = 'expired' WHERE status = 'held' AND expires_at <= NOW()");

            if ($propia) { $pdo->commit(); }
        } catch (PDOException $e) {
            if ($propia && $pdo->inTransaction()) { $pdo->rollBack(); }
            throw $e;
        }
    }
}

$csrf = csrfToken();

$vista = 'resumen';
$errores = [];
$datos = null;
$butacas = [];
$cantidad = 0;
$total = 0;
$venta = null;
$metodo = $_SERVER['REQUEST_METHOD'] ?? 'GET';
if ($metodo === 'POST') {
    requireMethod('POST');
    requireCsrfToken();
} elseif ($metodo !== 'GET') {
    http_response_code(405);
    header('Allow: GET, POST');
    exit('Método no permitido');
}

if ($metodo === 'GET' && isset($_GET['ok'])) {
    $ventaIdGet = inputUuid($_GET, 'ok');
    if ($ventaIdGet === '') {
        http_response_code(400);
        exit('Solicitud no válida');
    }
    if (!$userId) {
        $vista = 'login';
    } else {
        $stmt = $pdo->prepare(
            "SELECT s.id, s.total, s.status, s.payment_method, s.sale_date,
                    d.quantity, d.price, t.type, t.id AS ticket_id,
                    c.id AS concert_id, c.name, c.image, c.date, c.time,
                    v.name AS venue, v.city
               FROM sales s
               JOIN sale_details d ON d.sale_id = s.id
               JOIN tickets t ON t.id = d.ticket_id
               JOIN concerts c ON c.id = t.concert_id
               JOIN venues v ON v.id = c.venue_id
              WHERE s.id = :id AND s.user_id = :u LIMIT 1"
        );
        $stmt->execute([':id' => $ventaIdGet, ':u' => $userId]);
        $venta = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$venta) {
            $errores[] = 'No encontramos esa compra.';
            $vista = 'error';
        } else {
            $vista = 'listo';
            $stmt = $pdo->prepare("SELECT seat_code FROM seat_reservations WHERE sale_id = :id ORDER BY created_at, seat_code");
            $stmt->execute([':id' => $venta['id']]);
            $butacas = $stmt->fetchAll(PDO::FETCH_COLUMN);
        }
    }
} elseif ($metodo === 'POST') {
    $confirma = isset($_POST['confirmar']);
    $concertId = inputUuid($_POST, 'concert_id');
    $ticketId = inputUuid($_POST, 'ticket_id');
    $cantidad = inputInt($_POST, 'quantity');
    $metodoSel = inputString($_POST, 'payment_method');

    $butacas = [];
    $asientosEnviados = inputString($_POST, 'seats');
    $asientosInvalidos = strlen($asientosEnviados) > 500;
    if (!$asientosInvalidos && $asientosEnviados !== '') {
        $asientosLista = explode(',', $asientosEnviados);
        foreach ($asientosLista as $b) {
            $b = trim($b);
            if ($b !== '' && preg_match('/^\d{1,3}-\d{1,3}$/', $b) === 1) {
                $butacas[$b] = $b;
            } else {
                $asientosInvalidos = true;
            }
        }
        $asientosInvalidos = $asientosInvalidos || count($butacas) !== count($asientosLista);
    }
    $butacas = array_values($butacas);

    if ($asientosInvalidos) {
        $errores[] = 'La selección de butacas no es válida. Volvé al recital y elegilas nuevamente.';
    }

    if (!$userId) {
        $vista = 'login';
    } else {
        // CAMBIO D: liberar reservas vencidas antes de consultar disponibilidad
        try {
            vencerReservas($pdo);
        } catch (PDOException $e) {
            error_log('Error al vencer reservas: ' . $e->getMessage());
            $errores[] = 'No pudimos verificar la disponibilidad. Probá nuevamente en unos minutos.';
        }

        if ($concertId === '' || $ticketId === '') {
            $errores[] = 'La solicitud de compra no es válida.';
            $vista = 'error';
        } else {
        $stmt = $pdo->prepare(
            "SELECT t.id AS ticket_id, t.type, t.price, t.available, t.stock,
                    c.id AS concert_id, c.name, c.image, c.date, c.time, c.status,
                    v.name AS venue, v.city
               FROM tickets t
               JOIN concerts c ON c.id = t.concert_id
               JOIN venues v ON v.id = c.venue_id
              WHERE t.id = :t AND c.id = :c"
        );
        $stmt->execute([':t' => $ticketId, ':c' => $concertId]);
        $datos = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$datos) {
            $errores[] = 'No encontramos esa entrada.';
        } else {
            $numerada = esNumerada($datos['type']);
            if ($numerada) {
                $cantidad = count($butacas);
            } else {
                $butacas = [];
            }

            if ($datos['status'] !== 'active' || $datos['date'] < date('Y-m-d')) {
                $errores[] = 'Este recital ya no está disponible para la compra.';
            }
            if ($cantidad < 1) {
                $errores[] = $numerada ? 'Elegí al menos una butaca.' : 'Elegí al menos una entrada.';
            }
            if ($cantidad > $maxEntradas) {
                $errores[] = 'Podés comprar hasta ' . $maxEntradas . ' entradas por vez.';
            }
            if ($cantidad > (int) $datos['available']) {
                $errores[] = 'No quedan tantas entradas disponibles.';
            }

            if ($numerada && $butacas && !$errores) {
                $marcas = implode(',', array_fill(0, count($butacas), '?'));
                // CAMBIO E: solo cuentan las butacas held o confirmed
                $q = $pdo->prepare("SELECT seat_code FROM seat_reservations WHERE concert_id = ? AND status IN ('held', 'confirmed') AND seat_code IN ($marcas)");
                $q->execute(array_merge([$concertId], $butacas));
                $tomadas = $q->fetchAll(PDO::FETCH_COLUMN);
                if ($tomadas) {
                    $errores[] = 'Estas butacas ya no están disponibles: ' . implode(', ', array_map('textoButaca', $tomadas)) . '.';
                }
            }
            $total = $cantidad * (float) $datos['price'];
        }
        }

        if ($errores) {
            $vista = 'error';
        } elseif ($confirma) {
            if (!isset($_POST['accept_terms']) || $_POST['accept_terms'] !== '1') {
                $errores[] = 'Tenés que aceptar los términos y condiciones.';
                $vista = 'resumen';
            } elseif (!isset($metodos[$metodoSel])) {
                $errores[] = 'Elegí un medio de pago.';
                $vista = 'resumen';
            } else {
                try {
                    $pdo->beginTransaction();
                    vencerReservas($pdo); // CAMBIO D: también dentro de la transacción
                    $lock = $pdo->prepare(
                        "SELECT t.available, c.status, c.date
                           FROM tickets t
                           JOIN concerts c ON c.id = t.concert_id
                          WHERE t.id = :ticket_id AND c.id = :concert_id
                          FOR UPDATE"
                    );
                    $lock->execute([':ticket_id' => $ticketId, ':concert_id' => $concertId]);
                    $ticketLocked = $lock->fetch(PDO::FETCH_ASSOC);
                    $disp = (int) ($ticketLocked['available'] ?? 0);
                    if (
                        !$ticketLocked
                        || $ticketLocked['status'] !== 'active'
                        || $ticketLocked['date'] < date('Y-m-d')
                    ) {
                        throw new RuntimeException('Este recital ya no está disponible para la compra.');
                    }
                    if ($disp < $cantidad) {
                        throw new RuntimeException('No quedan tantas entradas disponibles.');
                    }

                    $pagadoYa = $metodos[$metodoSel]['instant'] && $modoDemo;
                    $estado = $pagadoYa ? 'paid' : 'pending';
                    $ventaId = uuidv4();

                    $pdo->prepare("INSERT INTO sales (id, user_id, total, status, payment_method) VALUES (?, ?, ?, ?, ?)")
                        ->execute([$ventaId, $userId, $total, $estado, $metodoSel]);
                    $pdo->prepare("INSERT INTO sale_details (id, sale_id, ticket_id, quantity, price) VALUES (?, ?, ?, ?, ?)")
                        ->execute([uuidv4(), $ventaId, $ticketId, $cantidad, $datos['price']]);

                    // CAMBIO F: pagada = confirmed; pendiente = held con vencimiento a 24 h
                    $estadoReserva = $pagadoYa ? 'confirmed' : 'held';
                    $expiraSql = $pagadoYa ? 'NULL' : 'NOW() + INTERVAL 24 HOUR';
                    $insSeat = $pdo->prepare("INSERT INTO seat_reservations (id, concert_id, ticket_id, sale_id, seat_code, status, expires_at) VALUES (?, ?, ?, ?, ?, ?, $expiraSql)");
                    foreach ($butacas as $codigo) {
                        $insSeat->execute([uuidv4(), $concertId, $ticketId, $ventaId, $codigo, $estadoReserva]);
                    }

                    // CAMBIO C: el descuento verifica el stock por sí mismo
                    $upd = $pdo->prepare("UPDATE tickets SET available = available - ? WHERE id = ? AND available >= ?");
                    $upd->execute([$cantidad, $ticketId, $cantidad]);
                    if ($upd->rowCount() !== 1) {
                        throw new RuntimeException('No quedan tantas entradas disponibles.');
                    }

                    $pdo->commit();
                    redirectTo('/src/controllers/concerts/checkout.php?ok=' . urlencode($ventaId));
                } catch (PDOException $e) {
                    error_log('Error en checkout: ' . $e->getMessage()); // CAMBIO B: deja rastro en el log
                    if ($pdo->inTransaction()) { $pdo->rollBack(); }
                    $errores[] = ($e->getCode() == '23000')
                        ? 'Alguna de las butacas se ocupó recién. Volvé y elegí otras.'
                        : 'No pudimos registrar la compra. Probá de nuevo.';
                    $vista = 'error';
                } catch (RuntimeException $e) {
                    if ($pdo->inTransaction()) { $pdo->rollBack(); }
                    $errores[] = $e->getMessage();
                    $vista = 'error';
                }
            }
        }
    }
} else {
    redirectTo('/src/views/index.php#eventos');
}

define('ALLTICKET_CHECKOUT_RENDER', true);
require __DIR__ . '/../../views/concerts/checkout-content.php';
