<?php

namespace App\Modules\InscripcionesAtenciones\Http\Resources;

use App\Models\User;
use App\Modules\InscripcionesAtenciones\Contracts\PacienteEstadoResumen;
use App\Modules\InscripcionesAtenciones\Support\EstadoPermission;
use App\Modules\Pacientes\Enums\EstadoPaciente;
use App\Modules\Pacientes\Support\PacientesPermission;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * [Estados Paciente e Inscripción] RF-011 / RF-013 / CA-011.
 */
final class EstadoPacienteRowResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        /** @var PacienteEstadoResumen $paciente */
        $paciente = $this->resource;
        /** @var User|null $user */
        $user = $request->user();
        $activo = $paciente->estado === EstadoPaciente::ACTIVO;
        $pausa = $paciente->estado === EstadoPaciente::EN_PAUSA;

        return $paciente->toArray() + [
            'can' => [
                'pause' => $user?->can(PacientesPermission::PATIENT_CHANGE_STATUS) === true && $activo,
                'reactivate' => $user?->can(PacientesPermission::PATIENT_CHANGE_STATUS) === true && $pausa,
                'retire' => $user?->can(PacientesPermission::PATIENT_RETIRE) === true
                    && ($activo || $pausa),
                'history' => $user?->can(EstadoPermission::VIEW) === true,
            ],
        ];
    }
}
