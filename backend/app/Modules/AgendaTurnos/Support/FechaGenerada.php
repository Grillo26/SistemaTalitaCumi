<?php

namespace App\Modules\AgendaTurnos\Support;

/**
 * Fecha del patrón con su clasificación previa a la previsualización.
 */
final readonly class FechaGenerada
{
    public const GENERABLE = 'generable';

    public const FERIADO = 'feriado';

    public const PASADA = 'pasada';

    public const OMITIDA = 'omitida';

    public function __construct(
        public string $fecha,
        public int $diaSemana,
        public string $estado,
        public ?string $motivo = null,
    ) {}

    public function esGenerable(): bool
    {
        return $this->estado === self::GENERABLE;
    }
}
