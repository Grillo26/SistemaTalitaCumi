<?php

namespace App\Modules\CatalogoServicios\Contracts;

/**
 * Resultado de la resolución de tarifa aplicable (RF-T08 / RF-T16).
 *
 * `monto` se mantiene como string para no operar con float.
 */
final class TarifaAplicableResumen
{
    public function __construct(
        public readonly int $tarifaId,
        public readonly int $servicioId,
        public readonly ?string $servicioNombre,
        public readonly int $categoriaTarifaId,
        public readonly ?string $categoriaNombre,
        public readonly ?int $sucursalId,
        public readonly ?string $sucursalNombre,
        public readonly string $monto,
        public readonly string $vigenteDesde,
        public readonly ?string $vigenteHasta,
        public readonly string $origen,
    ) {}

    /**
     * @param  array<string, mixed>  $data
     */
    public static function fromArray(array $data): self
    {
        return new self(
            tarifaId: (int) $data['id'],
            servicioId: (int) $data['servicio_id'],
            servicioNombre: $data['servicio_nombre'] !== null ? (string) $data['servicio_nombre'] : null,
            categoriaTarifaId: (int) $data['categoria_tarifa_id'],
            categoriaNombre: $data['categoria_nombre'] !== null ? (string) $data['categoria_nombre'] : null,
            sucursalId: $data['sucursal_id'] !== null ? (int) $data['sucursal_id'] : null,
            sucursalNombre: $data['sucursal_nombre'] !== null ? (string) $data['sucursal_nombre'] : null,
            monto: (string) $data['monto'],
            vigenteDesde: (string) $data['vigente_desde'],
            vigenteHasta: $data['vigente_hasta'] !== null ? (string) $data['vigente_hasta'] : null,
            origen: (string) $data['origen'],
        );
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'tarifa_id' => $this->tarifaId,
            'servicio_id' => $this->servicioId,
            'servicio_nombre' => $this->servicioNombre,
            'categoria_tarifa_id' => $this->categoriaTarifaId,
            'categoria_nombre' => $this->categoriaNombre,
            'sucursal_id' => $this->sucursalId,
            'sucursal_nombre' => $this->sucursalNombre,
            'monto' => $this->monto,
            'vigente_desde' => $this->vigenteDesde,
            'vigente_hasta' => $this->vigenteHasta,
            'origen' => $this->origen,
        ];
    }
}
