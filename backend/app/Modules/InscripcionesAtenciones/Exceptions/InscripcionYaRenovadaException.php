<?php

namespace App\Modules\InscripcionesAtenciones\Exceptions;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

final class InscripcionYaRenovadaException extends InscripcionesAtencionesDomainException
{
    public function __construct(public readonly int $renovacionId)
    {
        parent::__construct('La inscripción ya tiene una renovación activa vinculada.');
    }

    public function status(): int
    {
        return 409;
    }

    public function errorCode(): string
    {
        return 'INSCRIPCION_YA_RENOVADA';
    }

    public function render(Request $request): JsonResponse
    {
        return response()->json([
            'message' => $this->getMessage(),
            'code' => $this->errorCode(),
            'renovacion_id' => $this->renovacionId,
        ], $this->status());
    }
}
