<?php

namespace App\Modules\InscripcionesAtenciones\Rules;

use App\Modules\InscripcionesAtenciones\Exceptions\EntrevistaOmitidaInvalidaException;

/**
 * RN-004 / CU-03: la entrevista puede omitirse, pero debe quedar constancia.
 */
final class EntrevistaOmitidaRule
{
    public static function assertValida(
        ?int $entrevistaPreviaId,
        bool $omitioEntrevista,
        ?string $motivo,
    ): void {
        $motivo = $motivo === null ? null : trim($motivo);

        if ($motivo !== null && mb_strlen($motivo) > 500) {
            throw new EntrevistaOmitidaInvalidaException(
                'El motivo de omisión de la entrevista no puede superar los 500 caracteres.',
            );
        }

        if ($entrevistaPreviaId !== null && $omitioEntrevista) {
            throw new EntrevistaOmitidaInvalidaException(
                'No se puede vincular una entrevista y marcarla como omitida a la vez.',
            );
        }

        if ($entrevistaPreviaId !== null && $motivo !== null && $motivo !== '') {
            throw new EntrevistaOmitidaInvalidaException(
                'Si vinculás una entrevista previa, no corresponde indicar un motivo de omisión.',
            );
        }

        if ($entrevistaPreviaId === null && ! $omitioEntrevista) {
            throw new EntrevistaOmitidaInvalidaException(
                'Marcá "Omitir entrevista" o vinculá una entrevista previa.',
            );
        }
    }
}
