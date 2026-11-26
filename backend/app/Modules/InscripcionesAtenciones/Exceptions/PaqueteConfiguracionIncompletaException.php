<?php

namespace App\Modules\InscripcionesAtenciones\Exceptions;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

final class PaqueteConfiguracionIncompletaException extends InscripcionesAtencionesDomainException
{
    /**
     * @param  list<string>  $claves
     */
    public function __construct(public readonly array $claves)
    {
        parent::__construct(
            'Falta configuración para los paquetes de sesiones: '.implode(', ', $claves).'.',
        );
    }

    public function status(): int
    {
        return 503;
    }

    public function errorCode(): string
    {
        return 'PAQUETE_CONFIGURACION_INCOMPLETA';
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
