<?php

namespace App\Modules\Pagos\Support;

/**
 * Formato del número correlativo con `comprobante.prefijo` y `comprobante.formato` (01.03).
 *
 * Marcadores admitidos: {prefijo}, {numero} y {numero:N}, igual que la validación
 * de Configuración.
 */
final class PagoNumberFormatter
{
    public static function formatear(string $prefijo, string $formato, int $numero): string
    {
        $resultado = str_replace('{prefijo}', $prefijo, $formato);

        return (string) preg_replace_callback(
            '/\{numero(?::(\d+))?\}/',
            static fn (array $coincidencia): string => str_pad(
                (string) $numero,
                isset($coincidencia[1]) ? (int) $coincidencia[1] : 1,
                '0',
                STR_PAD_LEFT,
            ),
            $resultado,
        );
    }
}
