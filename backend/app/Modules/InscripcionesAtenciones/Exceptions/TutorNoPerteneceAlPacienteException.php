<?php

namespace App\Modules\InscripcionesAtenciones\Exceptions;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

final class TutorNoPerteneceAlPacienteException extends InscripcionesAtencionesDomainException
{
    public function __construct(
        public readonly int $tutorId,
        public readonly int $pacienteId,
    ) {
        parent::__construct('El tutor seleccionado no está asociado al paciente.');
    }

    public function status(): int
    {
        return 422;
    }

    public function errorCode(): string
    {
        return 'TUTOR_NO_PERTENECE_AL_PACIENTE';
    }

    public function render(Request $request): JsonResponse
    {
        return response()->json([
            'message' => $this->getMessage(),
            'code' => $this->errorCode(),
            'errors' => [
                'tutor_responsable_id' => [$this->getMessage()],
            ],
        ], $this->status());
    }
}
