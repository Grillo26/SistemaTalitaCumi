<?php

namespace App\Modules\InscripcionesAtenciones\Exceptions;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

final class TarifaCambioException extends InscripcionesAtencionesDomainException
{
    /**
     * @param  array<string, mixed>  $tarifaAplicable
     */
    public function __construct(public readonly array $tarifaAplicable)
    {
        parent::__construct('La tarifa vigente cambió. Revisá el monto y confirmá la actualización.');
    }

    public function status(): int
    {
        return 409;
    }

    public function errorCode(): string
    {
        return 'TARIFA_CAMBIO';
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
