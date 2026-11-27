<?php

namespace App\Modules\InscripcionesAtenciones\Support;

use App\Modules\InscripcionesAtenciones\Exceptions\ProfesionalNoDisponibleException;
use App\Modules\Profesionales\Contracts\ProfessionalDirectoryContract;
use Illuminate\Support\Facades\DB;

/**
 * Valida profesional activo y con relación activa a la sucursal.
 *
 * No valida especialidad porque el Catálogo no tiene una relación
 * servicio → especialidad. No modifica el módulo 03.
 */
final class ProfesionalPaqueteGuard
{
    public function __construct(private readonly ProfessionalDirectoryContract $professionals) {}

    public function assertDisponible(?int $profesionalId, int $sucursalId): void
    {
        if ($profesionalId === null) {
            return;
        }

        if (! $this->professionals->existe($profesionalId)) {
            throw new ProfesionalNoDisponibleException(
                $profesionalId,
                $sucursalId,
                'No se encontró el profesional indicado.',
            );
        }

        if (! $this->professionals->estaActivo($profesionalId)) {
            throw new ProfesionalNoDisponibleException(
                $profesionalId,
                $sucursalId,
                'El profesional está inactivo y no puede asignarse a nuevos paquetes.',
            );
        }

        $trabajaEnSucursal = DB::table('profesional_sucursal')
            ->where('profesional_id', $profesionalId)
            ->where('sucursal_id', $sucursalId)
            ->where('activo', true)
            ->exists();

        if (! $trabajaEnSucursal) {
            throw new ProfesionalNoDisponibleException($profesionalId, $sucursalId);
        }
    }
}
