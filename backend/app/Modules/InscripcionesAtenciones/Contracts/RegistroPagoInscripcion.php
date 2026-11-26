<?php

namespace App\Modules\InscripcionesAtenciones\Contracts;

/**
 * Inicio del registro de un pago delegado en Pagos y Caja.
 */
final readonly class RegistroPagoInscripcion
{
    public function __construct(
        public int $pagoId,
        public ?string $numeroComprobante = null,
        public ?string $url = null,
    ) {}

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'pago_id' => $this->pagoId,
            'numero_comprobante' => $this->numeroComprobante,
            'url' => $this->url,
        ];
    }
}
