<?php

namespace App\Modules\CatalogoServicios\Contracts;

use LogicException;

/**
 * Stub seguro: no inventa datos ni devuelve false silenciosamente.
 */
final class StubServicioUsageContract implements ServicioUsageContract
{
    public function hasTarifas(int $servicioId): bool
    {
        throw $this->notImplemented();
    }

    public function hasInscripciones(int $servicioId): bool
    {
        throw $this->notImplemented();
    }

    public function hasTurnos(int $servicioId): bool
    {
        throw $this->notImplemented();
    }

    private function notImplemented(): LogicException
    {
        return new LogicException(
            'Módulo 02/06/07 — Tarifas, inscripciones y turnos no implementados todavía.',
        );
    }
}
