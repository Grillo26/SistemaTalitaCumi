<?php

namespace App\Modules\InscripcionesAtenciones\Support;

use App\Modules\InscripcionesAtenciones\Enums\TipoEntidadVigencia;

/**
 * Resultado normalizado de una renovación individual.
 */
final readonly class VigenciaRenovacionResult
{
    public function __construct(
        public TipoEntidadVigencia $tipo,
        public int $origenId,
        public int $nuevaId,
        public string $monto,
        public string $fechaInicio,
        public string $fechaFin,
    ) {}

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'tipo_entidad' => $this->tipo->value,
            'tipo_entidad_label' => $this->tipo->label(),
            'entidad_origen_id' => $this->origenId,
            'entidad_id' => $this->nuevaId,
            'monto_esperado' => $this->monto,
            'fecha_inicio' => $this->fechaInicio,
            'fecha_fin' => $this->fechaFin,
        ];
    }
}
