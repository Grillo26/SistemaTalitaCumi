<?php

namespace App\Modules\InscripcionesAtenciones\Support;

use App\Models\User;
use App\Modules\InscripcionesAtenciones\Contracts\ComprobantePaquetePdfExporterContract;
use App\Modules\InscripcionesAtenciones\Contracts\ConsumoPaqueteContract;
use App\Modules\InscripcionesAtenciones\Contracts\InscripcionExporterContract;
use App\Modules\InscripcionesAtenciones\Contracts\PagoPaqueteContract;
use App\Modules\InscripcionesAtenciones\Contracts\TurnosFuturosPaqueteContract;
use App\Modules\InscripcionesAtenciones\Models\PaqueteSesiones;
use App\Modules\Pacientes\Support\PacientesPermission;

/**
 * Banderas `can` para la UI. Solo ocultan acciones; el backend revalida.
 */
final class PaqueteAbilities
{
    /**
     * @return array<string, mixed>
     */
    public static function for(User $user): array
    {
        return [
            'view' => $user->can(PaquetePermission::VIEW),
            'create' => $user->can(PaquetePermission::CREATE),
            'update' => $user->can(PaquetePermission::EDIT),
            'renew' => $user->can(PaquetePermission::RENEW),
            'withdraw' => $user->can(PaquetePermission::WITHDRAW),
            'export' => self::exportDisponible($user),
            'print' => true,
            'pdf' => self::pdfDisponible(),
            'view_tutor_contact' => $user->can(PacientesPermission::TUTOR_VIEW_CONTACT),
            'pago_disponible' => self::pagoDisponible(),
            'consumo_disponible' => self::consumoDisponible(),
            'turnos_futuros_disponible' => self::turnosFuturosDisponible(),
        ];
    }

    /**
     * @return array<string, bool>
     */
    public static function forRow(User $user, PaqueteSesiones $paquete): array
    {
        $activo = $paquete->estado->permiteEdicion();

        return [
            'view' => $user->can(PaquetePermission::VIEW),
            'update' => $user->can(PaquetePermission::EDIT) && $activo,
            'renew' => $user->can(PaquetePermission::RENEW) && $activo,
            'withdraw' => $user->can(PaquetePermission::WITHDRAW) && $paquete->estado->permiteBaja(),
        ];
    }

    private static function pagoDisponible(): bool
    {
        return app(PagoPaqueteContract::class)->disponible();
    }

    private static function consumoDisponible(): bool
    {
        return app(ConsumoPaqueteContract::class)->disponible();
    }

    private static function turnosFuturosDisponible(): bool
    {
        return app(TurnosFuturosPaqueteContract::class)->disponible();
    }

    private static function pdfDisponible(): bool
    {
        return app(ComprobantePaquetePdfExporterContract::class)->disponible();
    }

    private static function exportDisponible(User $user): bool
    {
        return $user->can(PaquetePermission::EXPORT)
            && app(InscripcionExporterContract::class)->disponible();
    }
}
