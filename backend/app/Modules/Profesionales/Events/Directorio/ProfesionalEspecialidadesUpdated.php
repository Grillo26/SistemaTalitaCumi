<?php

namespace App\Modules\Profesionales\Events\Directorio;

use Illuminate\Foundation\Events\Dispatchable;

final class ProfesionalEspecialidadesUpdated
{
    use Dispatchable;

    /**
     * @param  list<array{especialidad_id: int, nombre: string, tipo: string, activo: bool}>|null  $before
     * @param  list<array{especialidad_id: int, nombre: string, tipo: string, activo: bool}>  $after
     */
    public function __construct(
        public readonly int $profesionalId,
        public readonly ?array $before,
        public readonly array $after,
        public readonly ?int $actorId,
        public readonly ?string $ip,
    ) {}
}
