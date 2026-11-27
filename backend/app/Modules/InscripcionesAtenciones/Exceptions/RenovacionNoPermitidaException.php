<?php

namespace App\Modules\InscripcionesAtenciones\Exceptions;

use App\Modules\InscripcionesAtenciones\Enums\MotivoNoRenovable;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

final class RenovacionNoPermitidaException extends InscripcionesAtencionesDomainException
{
    public function __construct(public readonly MotivoNoRenovable $motivo)
    {
        parent::__construct($motivo->label());
    }

    public function status(): int
    {
        return $this->motivo === MotivoNoRenovable::SIN_PERMISO_RENOVAR ? 403 : 409;
    }

    public function errorCode(): string
    {
        return 'RENOVACION_NO_PERMITIDA';
    }

    public function render(Request $request): JsonResponse
    {
        return response()->json([
            'message' => $this->getMessage(),
            'code' => $this->errorCode(),
            'motivo' => $this->motivo->value,
        ], $this->status());
    }
}
