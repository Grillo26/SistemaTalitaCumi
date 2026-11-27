<?php

namespace App\Modules\InscripcionesAtenciones\Exceptions;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

final class CambioTarifaSinConfirmarException extends InscripcionesAtencionesDomainException
{
    /**
     * @param  array<string, mixed>  $tarifaVigente
     */
    public function __construct(
        public readonly array $tarifaVigente,
        public readonly string $montoVisto,
        string $message = 'La tarifa cambió. Revisá el monto antes de confirmar.',
    ) {
        parent::__construct($message);
    }

    public function status(): int
    {
        return 409;
    }

    public function errorCode(): string
    {
        return 'CAMBIO_TARIFA_SIN_CONFIRMAR';
    }

    public function render(Request $request): JsonResponse
    {
        return response()->json([
            'message' => $this->getMessage(),
            'code' => $this->errorCode(),
            'monto_visto' => $this->montoVisto,
            'tarifa_vigente' => $this->tarifaVigente,
            'tarifa_aplicable' => $this->tarifaVigente,
        ], $this->status());
    }
}
