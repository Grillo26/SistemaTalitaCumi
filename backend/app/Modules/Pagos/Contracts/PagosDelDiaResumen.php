<?php

namespace App\Modules\Pagos\Contracts;

/**
 * Totales de pagos vigentes por medio para una sucursal y fecha.
 */
final readonly class PagosDelDiaResumen
{
    /**
     * @param  array<string, array{cantidad: int, total: string}>  $porMedio  clave = código del medio
     */
    public function __construct(
        public int $sucursalId,
        public string $fecha,
        public array $porMedio,
        public string $total,
        public int $cantidad,
    ) {}

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'sucursal_id' => $this->sucursalId,
            'fecha' => $this->fecha,
            'por_medio' => $this->porMedio,
            'total' => $this->total,
            'cantidad' => $this->cantidad,
        ];
    }
}
