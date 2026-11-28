<?php

namespace App\Modules\Pagos\Events;

/**
 * Evento de dominio: un grupo de pagos quedó registrado (RF-015, RNF-012).
 *
 * Lleva snapshots en lista blanca; no contiene datos clínicos.
 */
final readonly class PagoRegistrado
{
    /**
     * @param  list<array<string, mixed>>  $pagos  snapshots whitelist de cada línea
     */
    public function __construct(
        public string $grupoPagoId,
        public array $pagos,
        public bool $fechaFutura,
        public int $actorId,
        public ?string $ip = null,
    ) {}
}
