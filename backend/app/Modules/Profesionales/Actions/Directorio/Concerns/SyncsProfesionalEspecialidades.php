<?php

namespace App\Modules\Profesionales\Actions\Directorio\Concerns;

use App\Modules\Profesionales\Enums\TipoEspecialidad;
use App\Modules\Profesionales\Models\Profesional;
use App\Modules\Profesionales\Models\ProfesionalEspecialidad;

/**
 * Sincroniza el set activo de especialidades sin borrar historial.
 *
 * Mantiene exactamente una principal activa y reutiliza filas inactivas para
 * no perder trazabilidad. Debe ejecutarse dentro de la transacción de la Action.
 */
trait SyncsProfesionalEspecialidades
{
    /**
     * @param  list<array{especialidad_id: int, tipo: string}>  $desired
     */
    protected function syncEspecialidades(Profesional $profesional, array $desired): void
    {
        /** @var array<int, string> $desiredById */
        $desiredById = [];

        foreach ($desired as $assignment) {
            $desiredById[(int) $assignment['especialidad_id']] = (string) $assignment['tipo'];
        }

        $principalId = (int) array_search(TipoEspecialidad::PRINCIPAL->value, $desiredById, true);

        /** @var list<ProfesionalEspecialidad> $relations */
        $relations = $profesional->relacionesEspecialidad()->get()->all();
        $bySpecialty = [];

        foreach ($relations as $relation) {
            $bySpecialty[(int) $relation->especialidad_id] ??= [];
            $bySpecialty[(int) $relation->especialidad_id][] = $relation;
        }

        $oldPrincipal = null;

        foreach ($relations as $relation) {
            if ($relation->activo && $relation->tipo === TipoEspecialidad::PRINCIPAL) {
                $oldPrincipal = $relation;
                break;
            }
        }

        if ($oldPrincipal !== null && (int) $oldPrincipal->especialidad_id !== $principalId) {
            $oldId = (int) $oldPrincipal->especialidad_id;

            if (($desiredById[$oldId] ?? null) === TipoEspecialidad::SECUNDARIA->value) {
                $oldPrincipal->tipo = TipoEspecialidad::SECUNDARIA;
                $oldPrincipal->save();
            } else {
                $oldPrincipal->activo = false;
                $oldPrincipal->save();
            }
        }

        foreach ($relations as $relation) {
            if (! $relation->activo) {
                continue;
            }

            $specialtyId = (int) $relation->especialidad_id;

            if (! array_key_exists($specialtyId, $desiredById)) {
                $relation->activo = false;
                $relation->save();
            }
        }

        $ensure = function (int $specialtyId, TipoEspecialidad $tipo) use ($profesional, $bySpecialty): void {
            /** @var ProfesionalEspecialidad|null $relation */
            $relation = null;

            foreach ($bySpecialty[$specialtyId] ?? [] as $candidate) {
                if ($relation === null || ($candidate->activo && ! $relation->activo)) {
                    $relation = $candidate;
                }
            }

            if ($relation === null) {
                ProfesionalEspecialidad::query()->create([
                    'profesional_id' => $profesional->id,
                    'especialidad_id' => $specialtyId,
                    'tipo' => $tipo->value,
                    'activo' => true,
                ]);

                return;
            }

            $relation->tipo = $tipo;
            $relation->activo = true;
            $relation->save();
        };

        $ensure($principalId, TipoEspecialidad::PRINCIPAL);

        foreach ($desiredById as $specialtyId => $tipo) {
            if ($tipo === TipoEspecialidad::SECUNDARIA->value) {
                $ensure($specialtyId, TipoEspecialidad::SECUNDARIA);
            }
        }

        $profesional->unsetRelation('relacionesEspecialidad');
    }
}
