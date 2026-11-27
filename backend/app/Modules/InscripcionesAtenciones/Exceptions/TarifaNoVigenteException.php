<?php

namespace App\Modules\InscripcionesAtenciones\Exceptions;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

final class TarifaNoVigenteException extends InscripcionesAtencionesDomainException
{
    /**
     * @param  array<string, mixed>|null  $tarifaAplicable
     */
    public function __construct(public readonly ?array $tarifaAplicable = null)
    {
        parent::__construct(
            $tarifaAplicable === null
                ? 'No hay una tarifa vigente para el servicio, sucursal, categoría y fecha seleccionados.'
                : 'La tarifa vigente cambió. Revisá el monto y confirmá la actualización.',
        );
    }

    public function status(): int
    {
        return 409;
    }

    public function errorCode(): string
    {
        return $this->tarifaAplicable === null ? 'TARIFA_NO_VIGENTE' : 'TARIFA_CAMBIO';
    }

    public function render(Request $request): JsonResponse
    {
        return response()->json([
            'message' => $this->getMessage(),
            'code' => $this->errorCode(),
            'tarifa_aplicable' => $this->tarifaAplicable,
        ], $this->status());
    }
}
