<?php

namespace App\Modules\CatalogoServicios\Exceptions;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

final class TarifaSolapadaException extends CatalogoServiciosDomainException
{
    /**
     * @param  array<string, mixed>  $conflict
     */
    public function __construct(public readonly array $conflict)
    {
        parent::__construct(
            'La vigencia se superpone con otra tarifa del mismo servicio, sucursal y categoría.',
        );
    }

    public function status(): int
    {
        return 409;
    }

    public function errorCode(): string
    {
        return 'TARIFA_SOLAPADA';
    }

    public function render(Request $request): JsonResponse
    {
        return response()->json([
            'message' => $this->getMessage(),
            'code' => $this->errorCode(),
            'conflict' => $this->conflict,
        ], $this->status());
    }
}
