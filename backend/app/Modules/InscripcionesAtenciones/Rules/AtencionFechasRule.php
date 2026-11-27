<?php

namespace App\Modules\InscripcionesAtenciones\Rules;

use App\Modules\InscripcionesAtenciones\Exceptions\AtencionFechasInvalidasException;
use DateTimeImmutable;

/**
 * RF-007: entrevista de un día; diagnóstico con rango coherente.
 *
 * No se restringen fechas pasadas ni una duración máxima.
 */
final class AtencionFechasRule
{
    public static function assertEntrevista(?string $fecha): void
    {
        if (! self::esFechaValida($fecha)) {
            throw new AtencionFechasInvalidasException('La fecha de la entrevista es obligatoria y debe ser válida.');
        }
    }

    public static function assertDiagnostico(?string $inicio, ?string $fin): void
    {
        if (! self::esFechaValida($inicio) || ! self::esFechaValida($fin)) {
            throw new AtencionFechasInvalidasException(
                'Las fechas de inicio y fin del diagnóstico son obligatorias y deben ser válidas.',
            );
        }

        if ($fin < $inicio) {
            throw new AtencionFechasInvalidasException(
                'La fecha de fin del diagnóstico no puede ser anterior a la fecha de inicio.',
            );
        }
    }

    private static function esFechaValida(?string $fecha): bool
    {
        if ($fecha === null || trim($fecha) === '') {
            return false;
        }

        $normalizada = trim($fecha);
        $date = DateTimeImmutable::createFromFormat('!Y-m-d', $normalizada);

        return $date !== false && $date->format('Y-m-d') === $normalizada;
    }
}
