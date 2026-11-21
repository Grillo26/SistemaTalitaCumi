<?php

namespace App\Modules\Pacientes\Support;

use App\Models\User;
use App\Modules\Pacientes\Enums\EstadoFichaIngreso;
use App\Modules\Pacientes\Models\FichaIngreso;

/**
 * Habilidades de UI de la ficha de ingreso (04.03 Fase A).
 */
final class FichaIngresoAbilities
{
    /**
     * @return array{view: bool, create: bool, edit: bool, complete: bool, archive: bool, reactivate: bool, view_sensibles: bool, export: bool, consent_view: bool, consent_register: bool, consent_revoke: bool, consent_catalog: bool}
     */
    public static function collection(User $user): array
    {
        return [
            'view' => $user->can(FichaIngresoPermission::VIEW),
            'create' => $user->can(FichaIngresoPermission::CREATE),
            'edit' => $user->can(FichaIngresoPermission::EDIT),
            'complete' => $user->can(FichaIngresoPermission::EDIT),
            'archive' => $user->can(FichaIngresoPermission::ARCHIVE),
            'reactivate' => $user->can(FichaIngresoPermission::REACTIVATE),
            'view_sensibles' => $user->can(FichaIngresoPermission::VIEW_SENSITIVE),
            'export' => false,
            'consent_view' => $user->can(ConsentimientoPermission::VIEW),
            'consent_register' => $user->can(ConsentimientoPermission::REGISTER),
            'consent_revoke' => $user->can(ConsentimientoPermission::REVOKE),
            'consent_catalog' => $user->can(ConsentimientoPermission::CATALOG),
        ];
    }

    /**
     * @return array{view: bool, create: bool, edit: bool, complete: bool, archive: bool, reactivate: bool, view_sensibles: bool, export: bool, consent_view: bool, consent_register: bool, consent_revoke: bool, consent_catalog: bool}
     */
    public static function for(User $user, FichaIngreso $ficha): array
    {
        $archived = $ficha->estado === EstadoFichaIngreso::ARCHIVADA;
        $draft = $ficha->estado === EstadoFichaIngreso::BORRADOR;

        return [
            'view' => $user->can(FichaIngresoPermission::VIEW),
            'create' => $user->can(FichaIngresoPermission::CREATE),
            'edit' => ! $archived && $user->can(FichaIngresoPermission::EDIT),
            'complete' => $draft && $user->can(FichaIngresoPermission::EDIT),
            'archive' => ! $archived && $user->can(FichaIngresoPermission::ARCHIVE),
            'reactivate' => $archived && $user->can(FichaIngresoPermission::REACTIVATE),
            'view_sensibles' => $user->can(FichaIngresoPermission::VIEW_SENSITIVE),
            'export' => false,
            'consent_view' => $user->can(ConsentimientoPermission::VIEW),
            'consent_register' => ! $archived && $user->can(ConsentimientoPermission::REGISTER),
            'consent_revoke' => ! $archived && $user->can(ConsentimientoPermission::REVOKE),
            'consent_catalog' => $user->can(ConsentimientoPermission::CATALOG),
        ];
    }
}
