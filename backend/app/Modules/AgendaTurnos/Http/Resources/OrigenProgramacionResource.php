<?php

namespace App\Modules\AgendaTurnos\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Datos del origen para el asistente de programación.
 */
final class OrigenProgramacionResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return (array) $this->resource;
    }
}
