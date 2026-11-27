<?php

namespace App\Modules\InscripcionesAtenciones\Exceptions;

use App\Modules\InscripcionesAtenciones\Enums\AccionEstado;
use App\Modules\InscripcionesAtenciones\Enums\EstadoInscripcion;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * [Estados Paciente e Inscripción] RN-003 / RN-002.
 */
final class TransicionEstadoNoPermitidaException extends InscripcionesAtencionesDomainException
{
    public function __construct(
        public readonly EstadoInscripcion $desde,
        public readonly EstadoInscripcion $hacia,
    ) {
        parent::__construct(
            "No se puede pasar de \"{$desde->label()}\" a \"{$hacia->label()}\".",
        );
    }

    public static function paraAccion(EstadoInscripcion $desde, AccionEstado $accion): self
    {
        return new self($desde, $accion->estadoDestino());
    }

    public function status(): int
    {
        return 409;
    }

    public function errorCode(): string
    {
        return 'TRANSICION_ESTADO_NO_PERMITIDA';
    }

    public function render(Request $request): JsonResponse
    {
        return response()->json([
            'message' => $this->getMessage(),
            'code' => $this->errorCode(),
            'estado_actual' => $this->desde->value,
            'estado_actual_label' => $this->desde->label(),
            'estado_solicitado' => $this->hacia->value,
        ], $this->status());
    }
}
