<?php

namespace App\Modules\CatalogoServicios\Contracts;

/**
 * Consumo de historial de un servicio (RF-C13 / RN-C08).
 *
 * `hasTarifas` es real desde el submódulo 02. `hasInscripciones` y
 * `hasTurnos` siguen en stub hasta los módulos 06 y 07. El endpoint DELETE
 * no existe: siempre responde 409.
 */
interface ServicioUsageContract
{
    public function hasTarifas(int $servicioId): bool;

    public function hasInscripciones(int $servicioId): bool;

    public function hasTurnos(int $servicioId): bool;
}
