<?php

namespace App\Modules\Pacientes\Exceptions;

use App\Modules\Pacientes\Enums\EstadoPaciente;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

final class PacienteNoInscribibleException extends PacientesDomainException
{
    public function __construct(private readonly EstadoPaciente $estado)
    {
        parent::__construct(match ($estado) {
            EstadoPaciente::EN_PAUSA => 'El paciente está en pausa. Reactivalo antes de crear una nueva inscripción.',
            EstadoPaciente::RETIRADO => 'El paciente está retirado. Reactivalo antes de crear una nueva inscripción.',
            default => 'El paciente no está activo y no puede inscribirse.',
        });
    }

    public function status(): int
    {
        return 409;
    }

    public function errorCode(): string
    {
        return 'PATIENT_NOT_INSCRIBIBLE';
    }

    public function motivoCodigo(): string
    {
        return $this->estado === EstadoPaciente::RETIRADO ? 'PATIENT_RETIRED' : 'PATIENT_PAUSED';
    }

    public function render(Request $request): JsonResponse
    {
        return response()->json([
            'message' => $this->getMessage(),
            'code' => $this->errorCode(),
            'motivo_codigo' => $this->motivoCodigo(),
            'motivo_mensaje' => $this->getMessage(),
        ], $this->status());
    }
}
