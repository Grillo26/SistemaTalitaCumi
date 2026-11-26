<?php

namespace App\Modules\CatalogoServicios\Contracts;

/**
 * Consumo de historial de un servicio (RF-C13 / RN-C08).
 *
 * Se implementará cuando existan tarifas (submódulo 02), inscripciones (06)
 * y turnos (07). Hoy el endpoint DELETE no existe y siempre responde 409.
 */
interface ServicioUsageContract
{
    public function hasTarifas(int $servicioId): bool;

    public function hasInscripciones(int $servicioId): bool;

    public function hasTurnos(int $servicioId): bool;
}
