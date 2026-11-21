<?php

namespace App\Modules\Profesionales\Rules;

use App\Modules\Profesionales\Enums\TipoEspecialidad;
use App\Modules\Profesionales\Exceptions\ProfesionalEspecialidadInvalidException;

/**
 * Asignación de especialidades del profesional (03/RF-E04, 03/RF-E05 / 03/RN-02).
 *
 * Regla pura. La Action valida existencia y estado de las especialidades; esta
 * regla garantiza exactamente una principal, ausencia de duplicados y el
 * supuesto de secundarias.
 */
final class ProfesionalSpecialtyAssignmentRule
{
    /**
     * @param  list<array{especialidad_id: int, tipo: string}>  $assignments
     */
    public function assertValid(array $assignments, bool $allowSecondaries): void
    {
        if ($assignments === []) {
            throw new ProfesionalEspecialidadInvalidException(
                'El profesional debe tener una especialidad principal.',
                ['especialidad_principal_id' => ['Seleccioná una especialidad principal.']],
            );
        }

        $principalCount = 0;
        $seen = [];

        foreach ($assignments as $assignment) {
            $especialidadId = (int) ($assignment['especialidad_id'] ?? 0);
            $tipo = (string) ($assignment['tipo'] ?? '');

            if (! in_array($tipo, [TipoEspecialidad::PRINCIPAL->value, TipoEspecialidad::SECUNDARIA->value], true)) {
                throw new ProfesionalEspecialidadInvalidException(
                    'El tipo de especialidad no es válido.',
                    ['especialidades' => ['El tipo de especialidad no es válido.']],
                );
            }

            if (isset($seen[$especialidadId])) {
                throw new ProfesionalEspecialidadInvalidException(
                    'No se puede repetir una especialidad en el profesional.',
                    ['especialidades_secundarias' => ['No se puede repetir una especialidad.']],
                );
            }

            $seen[$especialidadId] = true;

            if ($tipo === TipoEspecialidad::PRINCIPAL->value) {
                $principalCount++;
            }
        }

        if ($principalCount !== 1) {
            throw new ProfesionalEspecialidadInvalidException(
                'El profesional debe tener exactamente una especialidad principal.',
                ['especialidad_principal_id' => ['Seleccioná exactamente una especialidad principal.']],
            );
        }

        if (! $allowSecondaries) {
            $hasSecondary = count(array_filter(
                $assignments,
                static fn (array $assignment): bool => ($assignment['tipo'] ?? null) === TipoEspecialidad::SECUNDARIA->value,
            )) > 0;

            if ($hasSecondary) {
                throw new ProfesionalEspecialidadInvalidException(
                    'Las especialidades secundarias están pendientes de confirmación con el cliente.',
                    ['especialidades_secundarias' => ['Las especialidades secundarias están pendientes de confirmación.']],
                );
            }
        }
    }
}
