<?php

namespace App\Modules\InscripcionesAtenciones\Rules;

use App\Modules\InscripcionesAtenciones\Exceptions\MotivoEstadoInvalidoException;

/**
 * [Estados Paciente e Inscripción] RF-005 / RF-006 / RN-004 / CA-005.
 */
final class MotivoEstadoObligatorioRule
{
    /**
     * @param  list<string>  $codigosValidos
     */
    public static function assert(
        ?string $codigo,
        ?string $texto,
        array $codigosValidos,
        bool $requerido,
    ): void {
        $codigo = trim((string) $codigo);
        $texto = trim((string) $texto);

        if ($requerido && $codigo === '') {
            throw new MotivoEstadoInvalidoException('El motivo es obligatorio.');
        }

        if ($codigo !== '' && ! in_array($codigo, $codigosValidos, true)) {
            throw new MotivoEstadoInvalidoException('El motivo seleccionado no es válido.');
        }

        if ($codigo === 'otro' && $texto === '') {
            throw new MotivoEstadoInvalidoException('El motivo "Otro" requiere un detalle.');
        }

        if (mb_strlen($texto) > 500) {
            throw new MotivoEstadoInvalidoException('El detalle del motivo no puede superar los 500 caracteres.');
        }
    }
}
