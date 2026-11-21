<?php

namespace App\Modules\Organizacion\Queries;

use App\Modules\Organizacion\Models\Area;
use Illuminate\Database\Eloquent\Collection;

/**
 * Catálogo de áreas de solo lectura (prerequisito del módulo 05).
 */
final class ListAreasQuery
{
    /**
     * @return Collection<int, Area>
     */
    public function __invoke(): Collection
    {
        return Area::query()->orderBy('nombre')->get();
    }
}
