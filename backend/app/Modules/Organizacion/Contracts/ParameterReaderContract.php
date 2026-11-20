<?php

namespace App\Modules\Organizacion\Contracts;

use App\Modules\Organizacion\Exceptions\ParameterNotFoundException;
use App\Modules\Organizacion\Support\ParameterValue;

/**
 * Lectura de parámetros para módulos consumidores (RF-012 / RNF-002).
 *
 * Lo usarán Agenda y turnos (módulo 07), Pagos y caja (módulo 08) y Reportes.
 * La clave inexistente es un error controlado: no se devuelve null ni se
 * inventa un valor.
 */
interface ParameterReaderContract
{
    /**
     * @throws ParameterNotFoundException
     */
    public function obtener(string $clave): ParameterValue;

    /**
     * @param  list<string>  $claves
     * @return array<string, ParameterValue>
     *
     * @throws ParameterNotFoundException
     */
    public function obtenerVarios(array $claves): array;

    /**
     * @return array<string, ParameterValue>
     */
    public function porCategoria(string $categoria): array;
}
