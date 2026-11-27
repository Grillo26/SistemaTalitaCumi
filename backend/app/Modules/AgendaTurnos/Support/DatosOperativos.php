<?php

namespace App\Modules\AgendaTurnos\Support;

use App\Modules\CatalogoServicios\Contracts\AreaResumen;
use App\Modules\CatalogoServicios\Contracts\ServicioResumen;

/**
 * Datos operativos resueltos en batch para pintar la agenda.
 *
 * Los mapas pueden estar incompletos si un contrato devolvió menos filas o
 * falló; los flags indican si el dominio completo está disponible.
 */
final readonly class DatosOperativos
{
    /**
     * @param  array<int, string>  $pacientes
     * @param  array<int, string>  $profesionales
     * @param  array<int, string>  $salas
     * @param  array<int, ServicioResumen>  $servicios
     * @param  array<int, AreaResumen>  $areas
     */
    public function __construct(
        public array $pacientes,
        public array $profesionales,
        public array $salas,
        public array $servicios,
        public array $areas,
        public bool $pacientesDisponibles,
        public bool $profesionalesDisponibles,
        public bool $salasDisponibles,
        public bool $serviciosDisponibles,
        public bool $areasDisponibles,
    ) {}

    /**
     * @return array<string, bool>
     */
    public function disponibilidad(): array
    {
        return [
            'pacientes' => $this->pacientesDisponibles,
            'profesionales' => $this->profesionalesDisponibles,
            'salas' => $this->salasDisponibles,
            'servicios' => $this->serviciosDisponibles,
            'areas' => $this->areasDisponibles,
        ];
    }
}
