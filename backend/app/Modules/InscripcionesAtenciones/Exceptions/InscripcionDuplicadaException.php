<?php

namespace App\Modules\InscripcionesAtenciones\Exceptions;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

final class InscripcionDuplicadaException extends InscripcionesAtencionesDomainException
{
    /**
     * @param  array<string, mixed>  $conflict
     */
    public function __construct(public readonly array $conflict)
    {
        parent::__construct(
            'Ya existe una inscripción activa del paciente en esa sala para el período indicado.',
        );
    }

    public function status(): int
    {
        return 409;
    }

    public function errorCode(): string
    {
        return 'INSCRIPCION_DUPLICADA';
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
