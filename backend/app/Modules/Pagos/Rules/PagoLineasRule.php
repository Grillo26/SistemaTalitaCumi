<?php

namespace App\Modules\Pagos\Rules;

use App\Modules\Pagos\Exceptions\PagoLineasInvalidasException;

/**
 * Líneas de un pago (CU-01 FA-01, RF-005, RN-002).
 *
 * Una o dos líneas, medios distintos y disponibles en el catálogo. El QR exige
 * referencia manual del comprobante bancario (RT-004).
 */
final class PagoLineasRule
{
    public const MAXIMO_LINEAS = 2;

    public const REFERENCIA_MAXIMA = 100;

    public const MEDIO_QR = 'qr';

    /**
     * @param  list<array{medio_pago: string, referencia: ?string}>  $lineas
     * @param  list<string>  $mediosActivos
     */
    public static function assertValidas(array $lineas, array $mediosActivos): void
    {
        $cantidad = count($lineas);

        if ($cantidad < 1 || $cantidad > self::MAXIMO_LINEAS) {
            throw new PagoLineasInvalidasException;
        }

        $medios = array_column($lineas, 'medio_pago');

        if (count(array_unique($medios)) !== $cantidad) {
            throw new PagoLineasInvalidasException;
        }

        foreach ($lineas as $linea) {
            if (! in_array($linea['medio_pago'], $mediosActivos, true)) {
                throw new PagoLineasInvalidasException;
            }

            if ($linea['medio_pago'] === self::MEDIO_QR) {
                $referencia = trim((string) $linea['referencia']);

                if ($referencia === '' || mb_strlen($referencia) > self::REFERENCIA_MAXIMA) {
                    throw new PagoLineasInvalidasException(
                        'El pago por QR requiere la referencia del comprobante bancario (máximo '
                        .self::REFERENCIA_MAXIMA.' caracteres).',
                    );
                }
            }
        }
    }
}
