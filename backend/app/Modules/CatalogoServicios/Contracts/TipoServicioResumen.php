<?php

namespace App\Modules\CatalogoServicios\Contracts;

/**
 * Tipo de servicio expuesto a otros módulos (RF-C15).
 */
final class TipoServicioResumen
{
    public function __construct(
        public readonly int $id,
        public readonly string $nombre,
        public readonly bool $activo,
    ) {}

    /**
     * @param  array<string, mixed>  $data
     */
    public static function fromArray(array $data): self
    {
        return new self(
            id: (int) $data['id'],
            nombre: (string) $data['nombre'],
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
            'nombre' => $this->nombre,
            'activo' => $this->activo,
        ];
    }
}
