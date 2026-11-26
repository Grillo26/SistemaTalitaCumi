<?php

namespace App\Modules\InscripcionesAtenciones\Support;

use App\Models\User;
use App\Modules\InscripcionesAtenciones\Enums\TipoEntidadVigencia;
use App\Modules\Pacientes\Support\PacientesPermission;

/**
 * Banderas `can` para la UI del submódulo de vigencias.
 */
final class VigenciaAbilities
{
    /**
     * @return array<string, mixed>
     */
    public static function for(User $user): array
    {
        return [
            'view' => $user->can(VigenciaPermission::VIEW),
            'reports' => $user->can(VigenciaPermission::REPORTS),
            'export' => $user->can(VigenciaPermission::EXPORT),
            'config' => $user->can(VigenciaPermission::CONFIG),
            'view_tutor_contact' => $user->can(PacientesPermission::TUTOR_VIEW_CONTACT),
            'renew' => [
                TipoEntidadVigencia::INSCRIPCION_SALA->value => $user->can(VigenciaPermission::renewFor(TipoEntidadVigencia::INSCRIPCION_SALA)),
                TipoEntidadVigencia::PAQUETE_SESIONES->value => $user->can(VigenciaPermission::renewFor(TipoEntidadVigencia::PAQUETE_SESIONES)),
            ],
            'view_types' => [
                TipoEntidadVigencia::INSCRIPCION_SALA->value => $user->can(VigenciaPermission::viewFor(TipoEntidadVigencia::INSCRIPCION_SALA)),
                TipoEntidadVigencia::PAQUETE_SESIONES->value => $user->can(VigenciaPermission::viewFor(TipoEntidadVigencia::PAQUETE_SESIONES)),
            ],
        ];
    }

    /**
     * @return list<TipoEntidadVigencia>
     */
    public static function allowedTypes(User $user): array
    {
        $types = [];

        foreach (TipoEntidadVigencia::cases() as $tipo) {
            if ($user->can(VigenciaPermission::viewFor($tipo))) {
                $types[] = $tipo;
            }
        }

        return $types;
    }

    public static function puedeRenovar(User $user, TipoEntidadVigencia $tipo): bool
    {
        return $user->can(VigenciaPermission::renewFor($tipo));
    }
}
