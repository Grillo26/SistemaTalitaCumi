<?php

namespace App\Modules\Pacientes\Http\Resources;

use App\Models\User;
use App\Modules\Pacientes\Models\ContactoEmergencia;
use App\Modules\Pacientes\Models\FichaIngreso;
use App\Modules\Pacientes\Support\FichaIngresoAbilities;
use App\Modules\Pacientes\Support\FichaIngresoFields;
use App\Modules\Pacientes\Support\FichaIngresoPermission;
use App\Modules\Pacientes\Support\PacientesPermission;
use Illuminate\Http\Request;

/**
 * Representación de la ficha de ingreso (04.03/RF-009, 04.03/RF-015).
 */
final class FichaIngresoResource
{
    /**
     * @return array<string, mixed>
     */
    public static function for(User $actor, FichaIngreso $ficha, Request $request): array
    {
        $ficha->loadMissing(['paciente', 'sucursalIngreso', 'tutorPrincipal']);
        $fullSensitive = $actor->can(FichaIngresoPermission::VIEW_SENSITIVE);
        $contactos = $actor->can(PacientesPermission::CONTACT_EMERGENCY_VIEW)
            ? self::contactos($ficha, $actor, $request)
            : [];

        $data = [
            'id' => (int) $ficha->id,
            'paciente_id' => (int) $ficha->paciente_id,
            'sucursal_ingreso' => self::sucursal($ficha),
            'tutor_principal_id' => $ficha->tutor_principal_id === null
                ? null
                : (int) $ficha->tutor_principal_id,
            'fecha_ingreso' => $ficha->fecha_ingreso->toDateString(),
            'derivado_por' => $ficha->derivado_por,
            'derivado_por_institucion' => $ficha->derivado_por_institucion,
            'estado' => $ficha->estado->value,
            'estado_label' => $ficha->estado->label(),
            'version' => (int) $ficha->version,
            'completada_en' => $ficha->completada_en?->toIso8601String(),
            'archivada_en' => $ficha->archivada_en?->toIso8601String(),
            'created_at' => $ficha->created_at?->toIso8601String(),
            'updated_at' => $ficha->updated_at?->toIso8601String(),
            'sensibles_protegidos' => ! $fullSensitive,
            'campos_protegidos' => $fullSensitive ? [] : FichaIngresoFields::SENSITIVE,
            'entrevista_disponible' => false,
            'entrevista_mensaje' => 'La vinculación con la entrevista estará disponible con el módulo 06.',
            'contactos_emergencia' => $contactos,
            'can' => FichaIngresoAbilities::for($actor, $ficha),
        ];

        if ($fullSensitive) {
            foreach (FichaIngresoFields::SENSITIVE as $field) {
                $data[$field] = $ficha->{$field};
            }
        }

        return $data;
    }

    /**
     * @return array<string, mixed>|null
     */
    public static function sucursal(FichaIngreso $ficha): ?array
    {
        $branch = $ficha->sucursalIngreso;

        if ($branch === null) {
            return null;
        }

        return [
            'id' => (int) $branch->id,
            'codigo' => $branch->codigo,
            'nombre' => $branch->nombre,
            'activo' => (bool) $branch->activo,
        ];
    }

    /**
     * @return list<array<string, mixed>>
     */
    public static function historial(FichaIngreso $ficha): array
    {
        return [
            'id' => (int) $ficha->id,
            'fecha_ingreso' => $ficha->fecha_ingreso->toDateString(),
            'estado' => $ficha->estado->value,
            'estado_label' => $ficha->estado->label(),
            'sucursal_ingreso' => self::sucursal($ficha),
            'version' => (int) $ficha->version,
        ];
    }

    /**
     * @return list<array<string, mixed>>
     */
    private static function contactos(FichaIngreso $ficha, User $actor, Request $request): array
    {
        $ficha->paciente->loadMissing('contactosEmergencia.parentesco');

        return $ficha->paciente->contactosEmergencia
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
