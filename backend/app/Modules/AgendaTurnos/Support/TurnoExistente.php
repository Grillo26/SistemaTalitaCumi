<?php

namespace App\Modules\AgendaTurnos\Support;

use App\Modules\AgendaTurnos\Enums\EstadoTurno;
use App\Modules\AgendaTurnos\Models\Turno;
use App\Modules\AgendaTurnos\Rules\SesionGrupalRule;

final readonly class TurnoExistente
{
    public function __construct(
        public int $id,
        public int $sucursalId,
        public int $pacienteId,
        public ?int $profesionalId,
        public ?int $salaId,
        public int $servicioId,
        public string $fecha,
        public string $horaInicio,
        public string $horaFin,
        public EstadoTurno $estado,
        public ?string $sesionKey = null,
        public ?int $loteGeneracionId = null,
    ) {}

    public static function fromTurno(Turno $turno): self
    {
        return new self(
            id: (int) $turno->id,
            sucursalId: (int) $turno->sucursal_id,
            pacienteId: (int) $turno->paciente_id,
            profesionalId: $turno->profesional_id === null ? null : (int) $turno->profesional_id,
            salaId: $turno->sala_id === null ? null : (int) $turno->sala_id,
            servicioId: (int) $turno->servicio_id,
            fecha: $turno->fecha->toDateString(),
            horaInicio: (string) $turno->hora_inicio,
            horaFin: (string) $turno->hora_fin,
            estado: $turno->estado,
            sesionKey: SesionGrupalRule::clave(
                $turno->sala_id === null ? null : (int) $turno->sala_id,
                $turno->fecha->toDateString(),
                (string) $turno->hora_inicio,
                (string) $turno->hora_fin,
                (int) $turno->servicio_id,
            ),
            loteGeneracionId: $turno->lote_generacion_id === null
                ? null
                : (int) $turno->lote_generacion_id,
        );
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'sucursal_id' => $this->sucursalId,
            'fecha' => $this->fecha,
            'hora_inicio' => $this->horaInicio,
            'hora_fin' => $this->horaFin,
            'estado' => $this->estado->value,
            'estado_label' => $this->estado->label(),
            'paciente_id' => $this->pacienteId,
            'profesional_id' => $this->profesionalId,
            'sala_id' => $this->salaId,
            'servicio_id' => $this->servicioId,
            'sesion_key' => $this->sesionKey,
            'lote_generacion_id' => $this->loteGeneracionId,
        ];
    }
}
