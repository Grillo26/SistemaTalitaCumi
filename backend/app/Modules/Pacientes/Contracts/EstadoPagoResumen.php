<?php

namespace App\Modules\Pacientes\Contracts;

/**
 * Estado de pago calculado por el módulo 08 (04.04/RF-007, RF-016).
 *
 * 04.04 no calcula montos ni estados: solo muestra lo que entrega 08.
 */
final readonly class EstadoPagoResumen
{
    public function __construct(
        public string $estado,
        public ?string $fechaCorte,
        public ?string $proximoVencimiento,
        public ?string $saldo = null,
        public string $moneda = 'BOB',
    ) {}

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'estado' => $this->estado,
            'fecha_corte' => $this->fechaCorte,
            'proximo_vencimiento' => $this->proximoVencimiento,
            'saldo' => $this->saldo,
            'moneda' => $this->moneda,
        ];
    }
}
