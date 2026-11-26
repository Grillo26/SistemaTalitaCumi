<?php

namespace App\Modules\CatalogoServicios\Exceptions;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

final class AreaConServiciosActivosException extends CatalogoServiciosDomainException
{
    public function __construct(
        public readonly string $areaNombre,
        public readonly int $activeServicesCount,
    ) {
        parent::__construct(
            'No se puede desactivar un área con servicios activos. Desactivá primero los servicios.',
        );
    }

    public function status(): int
    {
        return 409;
    }

    public function errorCode(): string
    {
        return 'AREA_CON_SERVICIOS_ACTIVOS';
    }

    public function render(Request $request): JsonResponse
    {
        return response()->json([
            'message' => $this->getMessage(),
            'code' => $this->errorCode(),
            'active_services_count' => $this->activeServicesCount,
            'area' => ['nombre' => $this->areaNombre],
        ], $this->status());
    }
}
