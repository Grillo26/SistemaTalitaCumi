<?php

namespace App\Modules\Pacientes\Contracts;

/**
 * Servicio vigente de un paciente (04.04/RF-006).
 *
 * Lo entrega el módulo 06 (inscripciones a salas grupales y paquetes
 * individuales) ya filtrado por el alcance de sucursales del actor.
 */
final readonly class ServicioVigenteResumen
{
    public function __construct(
        public string $tipo,
        public int $sucursalId,
        public ?string $sucursalNombre,
        public string $servicio,
        public string $periodoInicio,
        public string $periodoFin,
        public string $estado,
        public ?int $sesionesPactadas = null,
        public ?int $sesionesDisponibles = null,
    ) {}

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'tipo' => $this->tipo,
            'sucursal_id' => $this->sucursalId,
            'sucursal_nombre' => $this->sucursalNombre,
            'servicio' => $this->servicio,
            'periodo_inicio' => $this->periodoInicio,
            'periodo_fin' => $this->periodoFin,
            'estado' => $this->estado,
            'sesiones_pactadas' => $this->sesionesPactadas,
            'sesiones_disponibles' => $this->sesionesDisponibles,
        ];
    }
}
