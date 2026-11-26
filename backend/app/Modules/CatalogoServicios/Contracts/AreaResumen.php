<?php

namespace App\Modules\CatalogoServicios\Contracts;

/**
 * Área expuesta a otros módulos (RF-C15). Incluye el color para agenda.
 */
final class AreaResumen
{
    public function __construct(
        public readonly int $id,
        public readonly string $nombre,
        public readonly ?string $descripcion,
        public readonly string $color,
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
            descripcion: $data['descripcion'] !== null ? (string) $data['descripcion'] : null,
            color: (string) $data['color'],
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
            'descripcion' => $this->descripcion,
            'color' => $this->color,
            'activo' => $this->activo,
        ];
    }
}
