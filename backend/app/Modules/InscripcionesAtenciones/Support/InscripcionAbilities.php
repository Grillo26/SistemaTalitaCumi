<?php

namespace App\Modules\InscripcionesAtenciones\Support;

use App\Models\User;
use App\Modules\InscripcionesAtenciones\Contracts\AsistenciaInscripcionContract;
use App\Modules\InscripcionesAtenciones\Contracts\ComprobantePdfExporterContract;
use App\Modules\InscripcionesAtenciones\Contracts\InscripcionExporterContract;
use App\Modules\InscripcionesAtenciones\Contracts\PagoInscripcionContract;
use App\Modules\InscripcionesAtenciones\Contracts\TurnosFuturosInscripcionContract;
use App\Modules\InscripcionesAtenciones\Models\InscripcionSala;
use App\Modules\Pacientes\Support\PacientesPermission;

/**
 * Banderas `can` para la UI. Solo ocultan acciones; el backend revalida.
 */
final class InscripcionAbilities
{
    /**
     * @return array<string, mixed>
     */
    public static function for(User $user): array
    {
        return [
            'view' => $user->can(InscripcionPermission::VIEW),
            'create' => $user->can(InscripcionPermission::CREATE),
            'update' => $user->can(InscripcionPermission::EDIT),
            'renew' => $user->can(InscripcionPermission::RENEW),
            'withdraw' => $user->can(InscripcionPermission::WITHDRAW),
            'export' => self::exportDisponible($user),
            'print' => true,
            'pdf' => self::pdfDisponible(),
            'view_tutor_contact' => $user->can(PacientesPermission::TUTOR_VIEW_CONTACT),
            'pago_disponible' => self::pagoDisponible(),
            'asistencia_disponible' => self::asistenciaDisponible(),
            'turnos_futuros_disponible' => self::turnosFuturosDisponible(),
        ];
    }

    /**
     * @return array<string, bool>
     */
    public static function forRow(User $user, InscripcionSala $inscripcion): array
    {
        $activa = $inscripcion->estado->permiteEdicion();

        return [
            'view' => $user->can(InscripcionPermission::VIEW),
            'update' => $user->can(InscripcionPermission::EDIT) && $activa,
            'renew' => $user->can(InscripcionPermission::RENEW) && $activa,
            'withdraw' => $user->can(InscripcionPermission::WITHDRAW) && $activa,
        ];
    }

    private static function pagoDisponible(): bool
    {
        return app(PagoInscripcionContract::class)->disponible();
    }

    private static function asistenciaDisponible(): bool
    {
        return app(AsistenciaInscripcionContract::class)->disponible();
    }

    private static function turnosFuturosDisponible(): bool
    {
        return app(TurnosFuturosInscripcionContract::class)->disponible();
    }

    private static function pdfDisponible(): bool
    {
        return app(ComprobantePdfExporterContract::class)->disponible();
    }

    private static function exportDisponible(User $user): bool
    {
        return $user->can(InscripcionPermission::EXPORT)
            && app(InscripcionExporterContract::class)->disponible();
    }
}
