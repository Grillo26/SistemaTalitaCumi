<?php

namespace App\Modules\AgendaTurnos\Support;

/**
 * Resultado de la regla de cancelación masiva.
 */
final readonly class ClasificacionCancelacionMasiva
{
    /**
     * @param  list<TurnoExistente>  $elegibles
     * @param  list<TurnoExcluido>  $excluidos
     */
    public function __construct(
        public array $elegibles,
        public array $excluidos,
    ) {}

    /**
     * @return list<int>
     */
    public function idsElegibles(): array
    {
        return array_map(
            static fn (TurnoExistente $turno): int => $turno->id,
            $this->elegibles,
        );
    }

    public function cantidadElegibles(): int
    {
        return count($this->elegibles);
    }

    public function cantidadExcluidos(): int
    {
        return count($this->excluidos);
    }
}
