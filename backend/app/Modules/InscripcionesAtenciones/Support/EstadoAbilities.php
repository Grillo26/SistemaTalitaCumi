<?php

namespace App\Modules\InscripcionesAtenciones\Support;

use App\Models\User;
use App\Modules\InscripcionesAtenciones\Enums\TipoEntidadVigencia;
use App\Modules\Pacientes\Support\PacientesPermission;

/**
 * Banderas `can` del submódulo de estados.
 */
final class EstadoAbilities
{
    /**
     * @return array<string, mixed>
     */
    public static function for(User $user): array
    {
        $tipos = [];

        foreach (TipoEntidadVigencia::cases() as $tipo) {
            $tipos[$tipo->value] = [
                'view' => $user->can(EstadoPermission::viewFor($tipo)),
                'withdraw' => $user->can(EstadoPermission::withdrawFor($tipo)),
            ];
        }

        return [
            'view' => $user->can(EstadoPermission::VIEW),
            'reports' => $user->can(EstadoPermission::REPORTS),
            'export' => $user->can(EstadoPermission::EXPORT),
            'patient' => [
                'pause' => $user->can(PacientesPermission::PATIENT_CHANGE_STATUS),
                'reactivate' => $user->can(PacientesPermission::PATIENT_CHANGE_STATUS),
                'retire' => $user->can(PacientesPermission::PATIENT_RETIRE),
            ],
            'inscription' => [
                'pause' => $user->can(EstadoPermission::INSCRIPTION_PAUSE),
                'reactivate' => $user->can(EstadoPermission::INSCRIPTION_REACTIVATE),
            ],
            'types' => $tipos,
        ];
    }

    public static function puedePausarInscripcion(User $user, TipoEntidadVigencia $tipo): bool
    {
        return $user->can(EstadoPermission::INSCRIPTION_PAUSE)
            && $user->can(EstadoPermission::viewFor($tipo));
    }

    public static function puedeReactivarInscripcion(User $user, TipoEntidadVigencia $tipo): bool
    {
        return $user->can(EstadoPermission::INSCRIPTION_REACTIVATE)
            && $user->can(EstadoPermission::viewFor($tipo));
    }

    public static function puedeRetirarInscripcion(User $user, TipoEntidadVigencia $tipo): bool
    {
        return $user->can(EstadoPermission::withdrawFor($tipo));
    }
}
