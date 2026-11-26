<?php

namespace App\Modules\CatalogoServicios\Http\Resources;

use App\Models\User;
use App\Modules\CatalogoServicios\Support\TarifaAccessScope;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

final class TarifaResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        /** @var User|null $user */
        $user = $request->user();
        $scope = app(TarifaAccessScope::class);
        $canManage = $user instanceof User && $scope->canManage($user, $this->resource);

        return [
            'id' => $this->id,
            'servicio' => $this->servicio === null ? null : [
                'id' => $this->servicio->id,
                'nombre' => $this->servicio->nombre,
                'activo' => (bool) $this->servicio->activo,
                'area' => $this->servicio->area === null ? null : [
                    'id' => $this->servicio->area->id,
                    'nombre' => $this->servicio->area->nombre,
                    'color' => $this->servicio->area->color,
                ],
            ],
            'categoria' => $this->categoria === null ? null : [
                'id' => $this->categoria->id,
                'nombre' => $this->categoria->nombre,
                'activo' => (bool) $this->categoria->activo,
            ],
            'sucursal' => $this->sucursal === null ? null : [
                'id' => $this->sucursal->id,
                'nombre' => $this->sucursal->nombre,
            ],
            'monto' => (string) $this->monto,
            'vigente_desde' => $this->vigente_desde?->toDateString(),
            'vigente_hasta' => $this->vigente_hasta?->toDateString(),
            'activo' => (bool) $this->activo,
            'estado_vigencia' => $this->estado_vigencia,
            'version' => (int) $this->version,
            'created_at' => $this->created_at?->toIso8601String(),
            'updated_at' => $this->updated_at?->toIso8601String(),
            'can' => [
                'update' => $canManage && ! $this->estaCerrada(),
                'close' => $canManage && $this->vigente_hasta === null,
                'delete' => false,
            ],
        ];
    }
}
