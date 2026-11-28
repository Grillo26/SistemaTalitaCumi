<?php

namespace App\Modules\AgendaTurnos\Support;

use App\Modules\AgendaTurnos\Enums\EstadoTurno;
use App\Modules\AgendaTurnos\Models\Turno;
use App\Modules\InscripcionesAtenciones\Contracts\DiagnosticoAtencionResumen;

/**
 * Materializa los turnos de un diagnóstico para las reglas puras de 07.05.
 */
final class DiagnosticoTurnosResolver
{
    /**
     * @return list<Turno>
     */
    public function delDiagnostico(int $diagnosticoId): array
    {
        return Turno::query()
            ->where('atencion_diagnostico_id', $diagnosticoId)
            ->ordenado()
            ->get()
            ->all();
    }

    /**
     * @param  list<Turno>  $turnos
     * @return list<array{id: int, profesional_id: int|null, estado: string}>
     */
    public function paraRegla(array $turnos): array
    {
        return array_map(
            static fn (Turno $turno): array => [
                'id' => (int) $turno->id,
                'profesional_id' => $turno->profesional_id === null ? null : (int) $turno->profesional_id,
                'estado' => $turno->estado->value,
            ],
            $turnos,
        );
    }

    /**
     * @return array<int, list<int>>
     */
    public function especialidadesPorProfesional(DiagnosticoAtencionResumen $resumen): array
    {
        $mapa = [];

        foreach ($resumen->profesionales as $profesional) {
            $mapa[(int) $profesional['id']] = array_map(
                static fn (array $especialidad): int => (int) $especialidad['id'],
                $profesional['especialidades'],
            );
        }

        return $mapa;
    }

    /**
     * @param  list<Turno>  $turnos
     * @return list<Turno>
     */
    public function vigentes(array $turnos): array
    {
        return array_values(array_filter(
            $turnos,
            static fn (Turno $turno): bool => ! in_array(
                $turno->estado,
                [EstadoTurno::CANCELADO, EstadoTurno::REPROGRAMADO],
                true,
            ),
        ));
    }
}
