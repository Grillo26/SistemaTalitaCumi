<?php

namespace App\Modules\InscripcionesAtenciones\Contracts;

/**
 * Conteos de turnos de un paquete (RF-009 / RN-004 / RN-011).
 *
 * El contrato no calcula el saldo: solo devuelve los conteos. La Query aplica
 * SaldoSesionesRule para que exista una sola fórmula.
 */
final readonly class ConsumoPaqueteResumen
{
    public function __construct(
        public int $pactadas,
        public int $realizadas,
        public int $faltadas,
        public int $canceladas,
    ) {}

    /**
     * @return array<string, int>
     */
    public function toArray(): array
    {
        return [
            'pactadas' => $this->pactadas,
            'realizadas' => $this->realizadas,
            'faltadas' => $this->faltadas,
            'canceladas' => $this->canceladas,
        ];
    }
}
