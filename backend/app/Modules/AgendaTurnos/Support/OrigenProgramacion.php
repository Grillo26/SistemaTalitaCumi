<?php

namespace App\Modules\AgendaTurnos\Support;

use App\Modules\AgendaTurnos\Enums\EstadoOrigenProgramacion;
use App\Modules\AgendaTurnos\Enums\TipoOrigenTurno;

/**
 * Datos de la entidad origen ya resueltos para calcular la programación.
 *
 * `patron` es null cuando la entidad no expone días/hora/duración; en ese
 * caso `patronNoDisponibleMotivo` explica el bloqueo (por ejemplo, salas sin
 * patrón configurado).
 */
final readonly class OrigenProgramacion
{
    /**
     * @param  list<int>  $diasSemana
     */
    public function __construct(
        public TipoOrigenTurno $tipo,
        public int $id,
        public int $sucursalId,
        public int $pacienteId,
        public int $servicioId,
        public ?int $salaId,
        public string $fechaInicio,
        public string $fechaFin,
        public ?int $cantidadSesiones,
        public EstadoOrigenProgramacion $estadoEfectivo,
        public ?PatronProgramacion $patron,
        public ?string $patronNoDisponibleMotivo = null,
    ) {}

    public function esSala(): bool
    {
        return $this->tipo === TipoOrigenTurno::INSCRIPCION_SALA;
    }

    public function esPaquete(): bool
    {
        return $this->tipo === TipoOrigenTurno::PAQUETE_SESIONES;
    }

    public function patronDisponible(): bool
    {
        return $this->patron !== null && $this->patron->diasSemana !== [];
    }

    public function conPatron(PatronProgramacion $patron): self
    {
        return new self(
            tipo: $this->tipo,
            id: $this->id,
            sucursalId: $this->sucursalId,
            pacienteId: $this->pacienteId,
            servicioId: $this->servicioId,
            salaId: $this->salaId,
            fechaInicio: $this->fechaInicio,
            fechaFin: $this->fechaFin,
            cantidadSesiones: $this->cantidadSesiones,
            estadoEfectivo: $this->estadoEfectivo,
            patron: $patron,
            patronNoDisponibleMotivo: null,
        );
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'tipo' => $this->tipo->value,
            'tipo_label' => $this->tipo->label(),
            'id' => $this->id,
            'sucursal_id' => $this->sucursalId,
            'paciente_id' => $this->pacienteId,
            'servicio_id' => $this->servicioId,
            'sala_id' => $this->salaId,
            'fecha_inicio' => $this->fechaInicio,
            'fecha_fin' => $this->fechaFin,
            'cantidad_sesiones' => $this->cantidadSesiones,
            'estado_efectivo' => $this->estadoEfectivo->value,
            'estado_efectivo_label' => $this->estadoEfectivo->label(),
            'patron' => $this->patron?->toArray(),
            'patron_disponible' => $this->patronDisponible(),
            'patron_no_disponible_motivo' => $this->patronNoDisponibleMotivo,
        ];
    }
}
