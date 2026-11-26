<?php

namespace App\Modules\CatalogoServicios\Rules;

/**
 * Compara especialidades requeridas contra participantes ya consultados
 * (RF-D08 / FP-09). Es una regla pura: no consulta profesionales.
 *
 * Los participantes son datos del módulo de agenda/diagnósticos, no de este
 * catálogo.
 */
final class ParticipantesRequeridosRule
{
    /**
     * @param  list<array{especialidad_id: int, cantidad_minima: int}>  $requeridos
     * @param  list<array{especialidad_id: int, cantidad: int}>  $participantes
     * @return list<array{especialidad_id: int, requeridos: int, presentes: int, faltan: int}>
     */
    public static function faltantes(array $requeridos, array $participantes): array
    {
        $presentes = [];

        foreach ($participantes as $participante) {
            $especialidadId = (int) ($participante['especialidad_id'] ?? 0);
            $presentes[$especialidadId] = ($presentes[$especialidadId] ?? 0)
                + max(0, (int) ($participante['cantidad'] ?? 0));
        }

        $faltantes = [];

        foreach ($requeridos as $requerido) {
            $especialidadId = (int) ($requerido['especialidad_id'] ?? 0);
            $cantidadRequerida = (int) ($requerido['cantidad_minima'] ?? 0);
            $cantidadPresente = $presentes[$especialidadId] ?? 0;

            if ($cantidadPresente < $cantidadRequerida) {
                $faltantes[] = [
                    'especialidad_id' => $especialidadId,
                    'requeridos' => $cantidadRequerida,
                    'presentes' => $cantidadPresente,
                    'faltan' => $cantidadRequerida - $cantidadPresente,
                ];
            }
        }

        return $faltantes;
    }
}
