<?php

namespace App\Modules\Organizacion\Contracts;

use App\Modules\Organizacion\Models\Branch;

/**
 * Datos mínimos de sucursal para selectores y documentos.
 */
final readonly class BranchResumen
{
    public function __construct(
        public int $id,
        public string $codigo,
        public string $nombre,
        public bool $activo,
    ) {}

    public static function fromBranch(Branch $branch): self
    {
        return new self(
            id: (int) $branch->id,
            codigo: (string) $branch->codigo,
            nombre: (string) $branch->nombre,
            activo: (bool) $branch->activo,
        );
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'codigo' => $this->codigo,
            'nombre' => $this->nombre,
            'activo' => $this->activo,
        ];
    }
}
