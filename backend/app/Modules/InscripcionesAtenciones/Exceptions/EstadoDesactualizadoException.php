<?php

namespace App\Modules\InscripcionesAtenciones\Exceptions;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * [Estados Paciente e Inscripción] RT-001 / concurrencia optimista.
 */
final class EstadoDesactualizadoException extends InscripcionesAtencionesDomainException
{
    /**
     * @param  array<string, mixed>  $detalle
     */
    public function __construct(
        public readonly string $estadoActual,
        public readonly string $estadoEsperado,
        public readonly array $detalle = [],
    ) {
        parent::__construct('El estado cambió desde que cargaste la pantalla. Recargá los datos.');
    }

    public function status(): int
    {
        return 409;
    }

    public function errorCode(): string
    {
        return 'ESTADO_DESACTUALIZADO';
    }

    public function render(Request $request): JsonResponse
    {
        return response()->json([
            'message' => $this->getMessage(),
            'code' => $this->errorCode(),
            'estado_actual' => $this->estadoActual,
            'estado_esperado' => $this->estadoEsperado,
            'detalle' => $this->detalle,
        ], $this->status());
    }
}
