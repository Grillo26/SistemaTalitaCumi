<?php

namespace App\Modules\AgendaTurnos\Support;

use App\Modules\AgendaTurnos\Enums\AccionLoteGeneracion;
use App\Modules\AgendaTurnos\Enums\TipoOrigenTurno;

final readonly class LoteResultado
{
    /**
     * @param  list<int>  $turnoIds
     */
    public function __construct(
        public int $loteId,
        public TipoOrigenTurno $tipoOrigen,
        public int $origenId,
        public AccionLoteGeneracion $accion,
        public int $cantidadCreados,
        public int $cantidadCancelados,
        public int $cantidadPreservados,
        public array $turnoIds = [],
        public bool $reutilizado = false,
    ) {}

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'lote_id' => $this->loteId,
            'tipo_origen' => $this->tipoOrigen->value,
            'origen_id' => $this->origenId,
            'accion' => $this->accion->value,
            'cantidad_creados' => $this->cantidadCreados,
            'cantidad_cancelados' => $this->cantidadCancelados,
            'cantidad_preservados' => $this->cantidadPreservados,
            'turno_ids' => $this->turnoIds,
            'reutilizado' => $this->reutilizado,
        ];
    }
}
