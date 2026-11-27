<?php

namespace App\Modules\AgendaTurnos\Support;

use App\Modules\AgendaTurnos\Models\Turno;
use App\Modules\AgendaTurnos\Rules\ContrasteColorRule;
use App\Modules\AgendaTurnos\Rules\TurnoSinAsignarRule;

/**
 * Convierte un Turno + datos batch en la fila operativa de la agenda.
 *
 * Solo expone datos operativos (RNF-005): nunca CI, contacto, edad,
 * diagnóstico ni información clínica.
 */
final class TurnoAgendaPresenter
{
    public function __construct(
        private readonly ContrasteColorRule $contraste,
        private readonly TurnoSinAsignarRule $sinAsignar,
    ) {}

    /**
     * @return array<string, mixed>
     */
    public function presente(Turno $turno, DatosOperativos $datos, bool $esHoy): array
    {
        $pacienteNombre = $datos->pacientes[$turno->paciente_id] ?? null;
        $servicio = $datos->servicios[$turno->servicio_id] ?? null;
        $area = $servicio === null ? null : ($datos->areas[$servicio->areaId] ?? null);
        $salaNombre = $turno->sala_id === null ? null : ($datos->salas[$turno->sala_id] ?? null);
        $profesionalNombre = $turno->profesional_id === null
            ? null
            : ($datos->profesionales[$turno->profesional_id] ?? null);

        $areaColor = $area?->color;
        $colorTexto = $areaColor === null ? null : ($this->contraste)($areaColor);

        if ($colorTexto === null) {
            $areaColor = null;
        }

        return [
            'id' => (int) $turno->id,
            'fecha' => $turno->fecha->toDateString(),
            'hora_inicio' => $turno->hora_inicio,
            'hora_fin' => $turno->hora_fin,
            'duracion_minutos' => (int) $turno->duracion_minutos,
            'estado' => $turno->estado->value,
            'estado_label' => $turno->estado->label(),
            'paciente' => [
                'id' => (int) $turno->paciente_id,
                'nombre_completo' => $pacienteNombre,
                'disponible' => $datos->pacientesDisponibles && $pacienteNombre !== null,
            ],
            'servicio' => [
                'id' => (int) $turno->servicio_id,
                'nombre' => $servicio?->nombre,
                'area_id' => $servicio?->areaId,
                'area_nombre' => $area?->nombre,
                'area_color' => $areaColor,
                'color_texto' => $colorTexto,
                'disponible' => $datos->serviciosDisponibles && $servicio !== null,
            ],
            'sala' => $turno->sala_id === null
                ? null
                : [
                    'id' => (int) $turno->sala_id,
                    'nombre' => $salaNombre,
                    'disponible' => $datos->salasDisponibles && $salaNombre !== null,
                ],
            'profesional' => $turno->profesional_id === null
                ? null
                : [
                    'id' => (int) $turno->profesional_id,
                    'nombre_completo' => $profesionalNombre,
                    'disponible' => $datos->profesionalesDisponibles && $profesionalNombre !== null,
                ],
            'sin_asignar' => ($this->sinAsignar)($turno->profesional_id),
            'sin_sala' => $turno->sala_id === null,
            'es_hoy' => $esHoy,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function presenteDetalle(Turno $turno, DatosOperativos $datos, bool $esHoy): array
    {
        return [
            ...$this->presente($turno, $datos, $esHoy),
            'turno_origen_id' => $turno->turno_origen_id === null
                ? null
                : (int) $turno->turno_origen_id,
        ];
    }
}
