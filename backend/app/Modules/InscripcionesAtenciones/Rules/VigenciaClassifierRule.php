<?php

namespace App\Modules\InscripcionesAtenciones\Rules;

use App\Modules\InscripcionesAtenciones\Enums\EstadoVigencia;
use App\Modules\InscripcionesAtenciones\Exceptions\AtencionFechasInvalidasException;
use DateTimeImmutable;

/**
 * [Vigencia y Renovación] RF-001, RF-002, RN-001, RN-004, RN-007,
 * RN-011 / CA-001, CA-002.
 *
 * Regla pura: no consulta base de datos, hora del sistema ni configuración.
 * El llamador inyecta `hoy` y la ventana vigente.
 */
final class VigenciaClassifierRule
{
    public static function classify(
        string $fechaFin,
        string $hoy,
        int $ventanaDias,
        bool $retirada,
        bool $renovada,
    ): EstadoVigencia {
        if ($retirada) {
            return EstadoVigencia::RETIRADO;
        }

        if ($renovada) {
            return EstadoVigencia::RENOVADO;
        }

        $fin = self::parse($fechaFin);
        $desde = self::parse($hoy);

        if ($fin < $desde) {
            return EstadoVigencia::VENCIDO;
        }

        $limite = $desde->modify('+'.$ventanaDias.' days');

        return $fin <= $limite
            ? EstadoVigencia::POR_VENCER
            : EstadoVigencia::VIGENTE;
    }

    public static function diasHastaVencimiento(string $fechaFin, string $hoy): int
    {
        $diferencia = self::parse($hoy)->diff(self::parse($fechaFin));

        return $diferencia->invert === 1
            ? -(int) $diferencia->days
            : (int) $diferencia->days;
    }

    private static function parse(string $fecha): DateTimeImmutable
    {
        $normalizada = trim($fecha);
        $date = DateTimeImmutable::createFromFormat('!Y-m-d', $normalizada);

        if ($date === false || $date->format('Y-m-d') !== $normalizada) {
            throw new AtencionFechasInvalidasException(
                'La fecha de vigencia no tiene un formato válido.',
            );
        }

        return $date;
    }
}
