<?php

namespace App\Modules\CatalogoServicios\Contracts;

use App\Modules\CatalogoServicios\Models\Tarifa;
use LogicException;

/**
 * Uso real de servicios: tarifas ya existen; inscripciones y turnos no.
 */
final class EloquentServicioUsageContract implements ServicioUsageContract
{
    public function hasTarifas(int $servicioId): bool
    {
        return Tarifa::query()->where('servicio_id', $servicioId)->exists();
    }

    public function hasInscripciones(int $servicioId): bool
    {
        throw new LogicException(
            'Módulo 06 — Inscripciones no implementado todavía.',
        );
    }

    public function hasTurnos(int $servicioId): bool
    {
        throw new LogicException(
            'Módulo 07 — Agenda y turnos no implementado todavía.',
        );
    }
}
