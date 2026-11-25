<?php

namespace App\Modules\Pacientes\Exceptions;

use App\Modules\Pacientes\Enums\EstadoPaciente;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

final class PacienteNoAgendableException extends PacientesDomainException
{
    public function __construct(private readonly EstadoPaciente $estado)
    {
        parent::__construct(match ($estado) {
            EstadoPaciente::EN_PAUSA => 'El paciente está en pausa y no admite turnos nuevos.',
            EstadoPaciente::RETIRADO => 'El paciente está retirado y no admite turnos nuevos.',
            default => 'El paciente no está activo y no admite turnos nuevos.',
        });
    }

    public function status(): int
    {
        return 409;
    }

    public function errorCode(): string
    {
        return 'PATIENT_NOT_SCHEDULABLE';
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
