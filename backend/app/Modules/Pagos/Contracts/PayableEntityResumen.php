<?php

namespace App\Modules\Pagos\Contracts;

use App\Modules\Pagos\Enums\TipoEntidadPago;

/**
 * Resumen de una entidad pagable (08.01/RF-001, RF-006, RN-010).
 *
 * Lo entrega el módulo que origina la entidad (06 o 10.3). Pagos nunca
 * calcula el monto esperado ni el estado: los toma de aquí.
 */
final readonly class PayableEntityResumen
{
    public function __construct(
        public TipoEntidadPago $tipo,
        public int $id,
        public int $sucursalId,
        public string $sucursalCodigo,
        public string $sucursalNombre,
        public int $pacienteId,
        public string $pacienteNombre,
        public string $referencia,
        public string $estado,
        public string $montoEsperado,
        public ?int $tutorSugeridoId,
        public ?string $tutorSugeridoNombre,
        public ?string $periodo = null,
    ) {}

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
            'sucursal_codigo' => $this->sucursalCodigo,
            'sucursal_nombre' => $this->sucursalNombre,
            'paciente_id' => $this->pacienteId,
            'paciente_nombre' => $this->pacienteNombre,
            'referencia' => $this->referencia,
            'estado' => $this->estado,
            'monto_esperado' => $this->montoEsperado,
            'tutor_sugerido_id' => $this->tutorSugeridoId,
            'tutor_sugerido_nombre' => $this->tutorSugeridoNombre,
            'periodo' => $this->periodo,
        ];
    }
}
