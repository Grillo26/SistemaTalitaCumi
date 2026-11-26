<?php

namespace App\Modules\InscripcionesAtenciones\Exceptions;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

final class ComprobanteConfiguracionIncompletaException extends InscripcionesAtencionesDomainException
{
    /**
     * @param  list<string>  $claves
     */
    public function __construct(public readonly array $claves)
    {
        parent::__construct(
            'Falta configuración del centro para generar el comprobante: '.implode(', ', $claves).'.',
        );
    }

    public function status(): int
    {
        return 503;
    }

    public function errorCode(): string
    {
        return 'COMPROBANTE_CONFIGURACION_INCOMPLETA';
    }

    public function render(Request $request): JsonResponse
    {
        return response()->json([
            'message' => $this->getMessage(),
            'code' => $this->errorCode(),
            'claves_faltantes' => $this->claves,
        ], $this->status());
    }
}
