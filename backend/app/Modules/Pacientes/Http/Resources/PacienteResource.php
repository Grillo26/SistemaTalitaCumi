<?php

namespace App\Modules\Pacientes\Http\Resources;

use App\Models\User;
use App\Modules\Organizacion\Models\Branch;
use App\Modules\Pacientes\Models\ContactoEmergencia;
use App\Modules\Pacientes\Models\Paciente;
use App\Modules\Pacientes\Models\PacienteTutor;
use App\Modules\Pacientes\Support\PacienteAbilities;
use App\Modules\Pacientes\Support\PacienteAgeCalculator;
use App\Modules\Pacientes\Support\PacientesModule;
use App\Modules\Pacientes\Support\PacientesPermission;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Representación del paciente (04.01/RF-005, 04.01/RF-006).
 *
 * El listado nunca incluye datos sensibles. La ficha los incluye solo si el
 * actor tiene `paciente.ver_sensibles`.
 */
final class PacienteResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public static function forList(User $actor, Paciente $paciente, Request $request): array
    {
        return self::base($paciente, $request) + [
            'can' => PacienteAbilities::for($actor, $paciente),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public static function forFicha(User $actor, Paciente $paciente, Request $request): array
    {
        $data = self::base($paciente, $request);
        $data['observaciones'] = $paciente->observaciones;

        $canViewSensitive = $actor->can(PacientesPermission::PATIENT_VIEW_SENSITIVE);

        if ($canViewSensitive) {
            $data['alergias'] = $paciente->alergias;
            $data['condiciones_medicas'] = $paciente->condiciones_medicas;
        }

        $data['sensibles_protegidos'] = ! $canViewSensitive;
        $data['tutores_disponibles'] = PacientesModule::TUTORES_DISPONIBLES;
        $data['tutores_mensaje'] = PacientesModule::TUTORES_DISPONIBLES
            ? null
            : 'Disponible con el submódulo de tutores y contactos (04.02).';
        $data['tutores'] = PacientesModule::TUTORES_DISPONIBLES
            ? self::tutores($paciente, $actor, $request)
            : null;
        $data['contactos_emergencia'] = PacientesModule::CONTACTOS_EMERGENCIA_DISPONIBLES
            && $actor->can(PacientesPermission::CONTACT_EMERGENCY_VIEW)
                ? self::contactosEmergencia($paciente, $actor, $request)
                : [];
        $data['can'] = PacienteAbilities::for($actor, $paciente);

        return $data;
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        /** @var Paciente $paciente */
        $paciente = $this->resource;

        return self::base($paciente, $request);
    }

    /**
     * @return array<string, mixed>
     */
    private static function base(Paciente $paciente, Request $request): array
    {
        $edad = app(PacienteAgeCalculator::class)->for($paciente);
        $sucursal = $paciente->relationLoaded('sucursalRegistro') ? $paciente->sucursalRegistro : null;

        return [
            'id' => (int) $paciente->id,
            'codigo' => $paciente->codigo,
            'nombre' => $paciente->nombre,
            'apellido_paterno' => $paciente->apellido_paterno,
            'apellido_materno' => $paciente->apellido_materno,
            'nombre_completo' => $paciente->nombre_completo,
            'fecha_nacimiento' => $paciente->fecha_nacimiento->toDateString(),
            'edad' => $edad,
            'sexo' => $paciente->sexo->value,
            'sexo_label' => $paciente->sexo->label(),
            'ci' => $paciente->ci,
            'tipo_diagnostico_id' => $paciente->tipo_diagnostico_id === null ? null : (int) $paciente->tipo_diagnostico_id,
            'sin_diagnostico_definido' => $paciente->tipo_diagnostico_id === null,
            'sucursal_registro' => $sucursal instanceof Branch
                ? [
                    'id' => (int) $sucursal->id,
                    'codigo' => $sucursal->codigo,
                    'nombre' => $sucursal->nombre,
                    'activo' => (bool) $sucursal->activo,
                ]
                : ['id' => (int) $paciente->sucursal_registro_id],
            'estado' => $paciente->estado->value,
            'estado_label' => $paciente->estado->label(),
            'version' => (int) $paciente->version,
            'created_at' => $paciente->created_at?->toIso8601String(),
            'updated_at' => $paciente->updated_at?->toIso8601String(),
        ];
    }

    /**
     * @return list<array<string, mixed>>
     */
    private static function tutores(Paciente $paciente, User $actor, Request $request): array
    {
        $paciente->loadMissing([
            'relacionesTutor.tutor.parentescoCatalogo',
            'relacionesTutor.tutor.sucursalRegistro',
            'relacionesTutor.parentesco',
        ]);

        return $paciente->relacionesTutor
            ->filter(static fn (PacienteTutor $relacion): bool => $relacion->tutor !== null)
            ->sortBy(static fn (PacienteTutor $relacion): string => sprintf(
                '%d-%s',
                $relacion->es_principal ? 0 : 1,
                mb_strtolower((string) $relacion->tutor->nombre_completo),
            ))
            ->map(static fn (PacienteTutor $relacion): array => TutorResource::forPaciente(
                $actor,
                $relacion,
                $request,
            ))
            ->filter(static fn (array $tutor): bool => $tutor !== [])
            ->values()
            ->all();
    }

    /**
     * @return list<array<string, mixed>>
     */
    private static function contactosEmergencia(Paciente $paciente, User $actor, Request $request): array
    {
        $paciente->loadMissing('contactosEmergencia.parentesco');

        return $paciente->contactosEmergencia
            ->sortBy(static fn (ContactoEmergencia $contacto): string => mb_strtolower((string) $contacto->nombre_completo))
            ->map(static fn (ContactoEmergencia $contacto): array => ContactoEmergenciaResource::for(
                $actor,
                $contacto,
                $request,
            ))
            ->values()
            ->all();
    }
}
