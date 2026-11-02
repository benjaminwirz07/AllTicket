<?php
/**
 * Reglas de negocio de las reservas de butacas (seat holds).
 *
 * Vive acá para que la lógica de vencimiento sea una sola y no se repita
 * en cada archivo que necesita disponibilidad (checkout, detalle del recital,
 * panel de admin, etc.).
 */

// Cuánto dura un hold temporal antes de vencer.
const RESERVA_HORAS_EXPIRACION = 24;
const BUTACAS_POR_FILA = 20;

/**
 * Vence los holds vencidos y las ventas pendientes que ya superaron el plazo.
 *
 * Para cada venta afectada: la marca como 'cancelled', devuelve el stock a
 * tickets.available y pasa sus reservas 'held' a 'expired'. Después barre
 * cualquier hold vencido que haya quedado suelto.
 *
 * Transaccional: si la llama dentro de una transacción abierta se suma a ella
 * y NO hace commit (la decide el llamador). Si la llama sola, abre y cierra
 * su propia transacción.
 */
function vencerReservas(PDO $pdo): void
{
    $propia = !$pdo->inTransaction();
    if ($propia) {
        $pdo->beginTransaction();
    }

    try {
        $limite = RESERVA_HORAS_EXPIRACION . ' HOUR';

        $ids = $pdo->query(
            "SELECT s.id FROM sales s
              WHERE s.status = 'pending'
                AND (s.sale_date <= NOW() - INTERVAL $limite
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

        if ($propia) {
            $pdo->commit();
        }
    } catch (PDOException $e) {
        if ($propia && $pdo->inTransaction()) {
            $pdo->rollBack();
        }
        throw $e;
    }
}

/** Formatea 'FILA-BUTACA' como 'Fila X · Butaca Y' para mostrar al usuario. */
function textoButaca(string $codigo): string
{
    $partes = explode('-', $codigo);
    return 'Fila ' . ($partes[0] ?? '') . ' · Butaca ' . ($partes[1] ?? '');
}

/** Comprueba que cada código identifica una butaca creada para el stock de la entrada. */
function butacasDentroDelStock(array $butacas, int $stock): bool
{
    foreach ($butacas as $codigo) {
        if (!is_string($codigo) || preg_match('/^([1-9]\d{0,2})-([1-9]\d{0,2})$/', $codigo, $partes) !== 1) {
            return false;
        }

        $fila = (int) $partes[1];
        $columna = (int) $partes[2];
        $indice = (($fila - 1) * BUTACAS_POR_FILA) + $columna;
        if ($columna > BUTACAS_POR_FILA || $indice > $stock) {
            return false;
        }
    }

    return true;
}

// ============================================================
// CLASIFICACIÓN DE TIPOS DE ENTRADA
// ============================================================
// El "tipo" es texto libre en la tabla tickets, así que se clasifica
// por palabra clave. Si mañana aparece un tipo nuevo (por ej. "campo"),
// se cambia SOLO acá.

/** Una entrada es VIP si el tipo contiene "vip". */
function esVip(string $tipo): bool
{
    return stripos($tipo, 'vip') !== false;
}

/** Una entrada es numerada (se elige butaca) si es VIP o Platea. */
function esNumerada(string $tipo): bool
{
    return esVip($tipo) || stripos($tipo, 'platea') !== false;
}

/** Días que faltan para una fecha 'Y-m-d' (negativo si ya pasó). */
function diasRestantes(string $fecha): int
{
    $hoy = strtotime(date('Y-m-d'));
    $dia = strtotime($fecha);
    return (int) round(($dia - $hoy) / 86400);
}
