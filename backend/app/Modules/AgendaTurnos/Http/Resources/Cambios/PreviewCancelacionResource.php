<?php

namespace App\Modules\AgendaTurnos\Http\Resources\Cambios;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

final class PreviewCancelacionResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return (array) $this->resource;
    }
}
