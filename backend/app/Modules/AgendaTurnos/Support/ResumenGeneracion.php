<?php

namespace App\Modules\AgendaTurnos\Support;

final readonly class ResumenGeneracion
{
    public function __construct(
        public int $generables,
        public int $bloqueados,
        public int $advertencias,
        public int $feriados,
        public int $pasadas,
        public int $omitidas,
        public int $faltantes,
        public bool $requiereConfirmarAdvertencias,
        public bool $puedeConfirmar,
    ) {}

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'generables' => $this->generables,
            'bloqueados' => $this->bloqueados,
            'advertencias' => $this->advertencias,
            'feriados' => $this->feriados,
            'pasadas' => $this->pasadas,
            'omitidas' => $this->omitidas,
            'faltantes' => $this->faltantes,
            'requiere_confirmar_advertencias' => $this->requiereConfirmarAdvertencias,
            'puede_confirmar' => $this->puedeConfirmar,
        ];
    }
}
