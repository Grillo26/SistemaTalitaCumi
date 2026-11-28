<?php

namespace App\Modules\Organizacion\Contracts;

/**
 * Patrón semanal configurado para una sala grupal (módulo 07, CU-03).
 */
final readonly class SalaPatronResumen
{
    /**
     * @param  list<int>  $diasSemana  lunes (1) a sábado (6)
     */
    public function __construct(
        public array $diasSemana,
        public string $horaInicio,
        public int $duracionMinutos,
        public ?int $profesionalId = null,
    ) {}
}
