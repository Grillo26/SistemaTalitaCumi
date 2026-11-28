<?php

namespace App\Modules\AgendaTurnos\Support;

/**
 * Entrada de una generación o regeneración confirmada.
 */
final readonly class EntradaGeneracion
{
    public function __construct(
        public string $claveIdempotencia,
        public bool $confirmarAdvertencias = false,
        public bool $usarProfesionalOrigen = true,
        public ?int $profesionalId = null,
        public ?string $ip = null,
    ) {}
}
