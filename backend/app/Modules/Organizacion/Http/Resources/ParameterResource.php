<?php

namespace App\Modules\Organizacion\Http\Resources;

use App\Modules\Organizacion\Support\ParameterValueCaster;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\Storage;

/**
 * Parámetro de configuración (RF-001/RF-002).
 */
final class ParameterResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $logoUrl = null;

        if ($this->clave === 'centro.logo' && is_string($this->valor) && $this->valor !== '') {
            $logoUrl = Storage::disk('public')->url($this->valor);
        }

        return [
            'id' => $this->id,
            'clave' => $this->clave,
            'categoria' => $this->categoria->value,
            'categoria_label' => $this->categoria->label(),
            'descripcion' => $this->descripcion,
            'tipo' => $this->tipo->value,
            'tipo_label' => $this->tipo->label(),
            'valor' => ParameterValueCaster::cast($this->tipo, $this->valor),
            'valor_texto' => $this->valor,
            'valor_defecto' => ParameterValueCaster::cast($this->tipo, $this->valor_defecto),
            'valor_defecto_texto' => $this->valor_defecto,
            'unidad' => $this->unidad,
            'minimo' => $this->minimo,
            'maximo' => $this->maximo,
            'sensible' => $this->sensible,
            'editable' => $this->editable,
            'visible_recepcion' => $this->visible_recepcion,
            'version' => $this->version,
            'logo_url' => $logoUrl,
            'updated_at' => $this->updated_at?->toIso8601String(),
        ];
    }
}
