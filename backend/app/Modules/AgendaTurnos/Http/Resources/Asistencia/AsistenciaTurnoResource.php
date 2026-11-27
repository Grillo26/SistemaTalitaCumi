<?php

namespace App\Modules\AgendaTurnos\Http\Resources\Asistencia;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

final class AsistenciaTurnoResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return (array) $this->resource;
    }
}
