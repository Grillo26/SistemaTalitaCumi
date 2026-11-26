<?php

namespace App\Modules\InscripcionesAtenciones\Support;

use App\Models\User;
use App\Modules\InscripcionesAtenciones\Contracts\AtencionExporterContract;
use App\Modules\InscripcionesAtenciones\Contracts\ComprobanteAtencionPdfExporterContract;
use App\Modules\InscripcionesAtenciones\Contracts\PagoAtencionContract;
use App\Modules\InscripcionesAtenciones\Enums\EstadoAtencion;
use App\Modules\Pacientes\Support\PacientesPermission;

/**
 * Banderas `can` del submódulo de atenciones. Solo ocultan acciones; el
 * backend revalida permisos y alcance.
 */
final class AtencionAbilities
{
    /**
     * @return array<string, bool>
     */
    public static function for(User $user): array
    {
        return [
            'view' => $user->can(AtencionPermission::VIEW),
            'create' => $user->can(AtencionPermission::CREATE),
            'update' => $user->can(AtencionPermission::EDIT),
            'withdraw' => $user->can(AtencionPermission::WITHDRAW),
            'export' => self::exportDisponible($user),
            'print' => true,
            'pdf' => self::pdfDisponible(),
            'pago_disponible' => self::pagoDisponible(),
            'view_tutor_contact' => $user->can(PacientesPermission::TUTOR_VIEW_CONTACT),
        ];
    }

    /**
     * @return array<string, bool>
     */
    public static function forRow(User $user, EstadoAtencion $estado): array
    {
        return [
            'view' => $user->can(AtencionPermission::VIEW),
            'update' => $user->can(AtencionPermission::EDIT) && $estado->permiteEdicion(),
            'withdraw' => $user->can(AtencionPermission::WITHDRAW) && $estado->permiteBaja(),
        ];
    }

    private static function pagoDisponible(): bool
    {
        return app(PagoAtencionContract::class)->disponible();
    }

    private static function pdfDisponible(): bool
    {
        return app(ComprobanteAtencionPdfExporterContract::class)->disponible();
    }

    private static function exportDisponible(User $user): bool
    {
        return $user->can(AtencionPermission::EXPORT)
            && app(AtencionExporterContract::class)->disponible();
    }
}
