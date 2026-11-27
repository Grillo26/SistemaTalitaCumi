<?php

namespace App\Modules\AgendaTurnos\Support;

/**
 * Conteo de turnos por estado sobre el conjunto filtrado y visible.
 */
final readonly class ResumenSemana
{
    /**
     * @param  array<string, int>  $porEstado
     */
    public function __construct(
        public int $total,
        public array $porEstado,
    ) {}

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'total' => $this->total,
            'base' => 'turnos_filtrados_visibles',
            'por_estado' => $this->porEstado,
        ];
    }
}
