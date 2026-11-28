<?php

namespace App\Modules\Pagos\Rules;

/**
 * Huella canónica de una solicitud de registro (RT-001).
 *
 * Misma clave con el mismo contenido devuelve el pago original; con otro
 * contenido responde 409. El orden de las claves no altera la huella.
 */
final class PagoIdempotenciaRule
{
    /**
     * @param  array<string, mixed>  $canonico
     */
    public static function huella(array $canonico): string
    {
        return hash('sha256', json_encode(self::ordenar($canonico), JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR));
    }

    /**
     * @param  array<mixed>  $datos
     * @return array<mixed>
     */
    private static function ordenar(array $datos): array
    {
        foreach ($datos as $clave => $valor) {
            if (is_array($valor)) {
                $datos[$clave] = self::ordenar($valor);
            }
        }

        if (! array_is_list($datos)) {
            ksort($datos);
        }

        return $datos;
    }
}
