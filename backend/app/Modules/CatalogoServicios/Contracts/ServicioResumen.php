<?php

namespace App\Modules\CatalogoServicios\Contracts;

/**
 * Servicio expuesto a otros módulos (RF-C15).
 */
final class ServicioResumen
{
    public function __construct(
        public readonly int $id,
        public readonly int $areaId,
        public readonly ?string $areaNombre,
        public readonly ?string $areaColor,
        public readonly int $tipoServicioId,
        public readonly ?string $tipoServicioNombre,
        public readonly string $nombre,
        public readonly ?string $descripcion,
        public readonly int $duracionMinutos,
        public readonly bool $activo,
    ) {}

    /**
     * @param  array<string, mixed>  $data
     */
    public static function fromArray(array $data): self
    {
        return new self(
            id: (int) $data['id'],
            areaId: (int) $data['area_id'],
            areaNombre: $data['area_nombre'] !== null ? (string) $data['area_nombre'] : null,
            areaColor: $data['area_color'] !== null ? (string) $data['area_color'] : null,
            tipoServicioId: (int) $data['tipo_servicio_id'],
            tipoServicioNombre: $data['tipo_servicio_nombre'] !== null
                ? (string) $data['tipo_servicio_nombre']
                : null,
            nombre: (string) $data['nombre'],
            descripcion: $data['descripcion'] !== null ? (string) $data['descripcion'] : null,
            duracionMinutos: (int) $data['duracion_minutos'],
            activo: (bool) $data['activo'],
        );
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'area_id' => $this->areaId,
            'area_nombre' => $this->areaNombre,
            'area_color' => $this->areaColor,
            'tipo_servicio_id' => $this->tipoServicioId,
            'tipo_servicio_nombre' => $this->tipoServicioNombre,
            'nombre' => $this->nombre,
            'descripcion' => $this->descripcion,
            'duracion_minutos' => $this->duracionMinutos,
            'activo' => $this->activo,
        ];
    }
}
