<?php

namespace App\Modules\Pacientes\Rules;

use App\Modules\Pacientes\Enums\TipoCoincidencia;

/**
 * Clasificación pura de duplicados (04.01/RF-003, 04.01/RF-016).
 *
 * La Action/Detector carga candidatos y normaliza; esta regla no consulta DB.
 */
final class PacienteDuplicateRule
{
    /**
     * @param  array{nombre: string, apellido_paterno: string, apellido_materno: ?string, fecha_nacimiento: string, tutor_principal_id: ?int}  $input
     * @param  array{id: int, nombre: string, apellido_paterno: string, apellido_materno: ?string, fecha_nacimiento: string, tutor_principal_id: ?int}  $candidate
     */
    public function classify(array $input, array $candidate): ?TipoCoincidencia
    {
        if ($input['fecha_nacimiento'] !== $candidate['fecha_nacimiento']) {
            return null;
        }

        $sameName = $input['nombre'] === $candidate['nombre'];
        $samePaternal = $input['apellido_paterno'] === $candidate['apellido_paterno'];
        $sameMaternal = $input['apellido_materno'] !== null
            && $candidate['apellido_materno'] !== null
            && $input['apellido_materno'] === $candidate['apellido_materno'];

        if ($sameName && $samePaternal && $sameMaternal) {
            return TipoCoincidencia::IDENTIDAD_COMPLETA;
        }

        if ($sameName && $samePaternal) {
            return TipoCoincidencia::IDENTIDAD_PARCIAL;
        }

        if (
            $samePaternal
            && $input['tutor_principal_id'] !== null
            && $input['tutor_principal_id'] === $candidate['tutor_principal_id']
        ) {
            return TipoCoincidencia::IDENTIDAD_PARCIAL_CON_TUTOR;
        }

        return null;
    }
}
