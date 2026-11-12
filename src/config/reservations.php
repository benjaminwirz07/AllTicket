<?php
/**
 * Reglas de negocio para la selección de butacas.
 */

const BUTACAS_POR_FILA = 20;

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
