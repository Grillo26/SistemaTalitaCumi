<?php

namespace App\Modules\AgendaTurnos\Support;

use App\Modules\AgendaTurnos\Models\Turno;

/**
 * Resultado puro de una reprogramación (preview o confirmación).
 */
final readonly class ReprogramacionCalculada
{
    /**
     * @param  list<Hallazgo>  $hallazgos
     * @param  array<string, mixed>  $original
     * @param  array<string, mixed>  $nuevo
     * @param  array<string, mixed>|null  $origen
     */
    public function __construct(
        public Turno $turno,
        public MotivoTurnoData $motivo,
        public array $original,
        public array $nuevo,
        public array $hallazgos,
        public bool $puedeConfirmar,
        public bool $requiereConfirmarAdvertencias,
        public ?array $origen = null,
    ) {}

    /**
     * @return list<string>
     */
    public function advertencias(): array
    {
        return array_values(array_unique(array_map(
            static fn (Hallazgo $hallazgo): string => $hallazgo->mensaje,
            array_filter(
                $this->hallazgos,
                static fn (Hallazgo $hallazgo): bool => $hallazgo->severidad->value === 'advertencia',
            ),
        )));
    }
}
