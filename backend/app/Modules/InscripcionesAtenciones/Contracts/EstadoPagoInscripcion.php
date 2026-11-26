<?php

namespace App\Modules\InscripcionesAtenciones\Contracts;

/**
 * Estado de pago de una inscripción (RF-013, delegado en Pagos y Caja).
 */
final readonly class EstadoPagoInscripcion
{
    public function __construct(
        public string $estado,
        public string $montoEsperado,
        public string $montoPagado,
        public string $saldo,
        public int $pagosRegistrados = 0,
        public ?string $actualizadoEn = null,
    ) {}

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'estado' => $this->estado,
            'monto_esperado' => $this->montoEsperado,
            'monto_pagado' => $this->montoPagado,
            'saldo' => $this->saldo,
            'pagos_registrados' => $this->pagosRegistrados,
            'actualizado_en' => $this->actualizadoEn,
        ];
    }
}
