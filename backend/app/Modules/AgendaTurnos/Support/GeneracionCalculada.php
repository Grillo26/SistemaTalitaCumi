<?php

namespace App\Modules\AgendaTurnos\Support;

/**
 * Resultado del cálculo de una generación (previsualización o confirmación).
 */
final readonly class GeneracionCalculada
{
    /**
     * @param  list<FilaPreview>  $filas
     * @param  list<Hallazgo>  $hallazgos  hallazgos globales (faltantes, preservados)
     */
    public function __construct(
        public array $filas,
        public ResumenGeneracion $resumen,
        public array $hallazgos = [],
        public ?int $cantidadObjetivo = null,
        public ?int $cantidadGenerable = null,
    ) {}

    /**
     * @return list<FilaPreview>
     */
    public function filasGenerables(): array
    {
        return array_values(array_filter(
            $this->filas,
            static fn (FilaPreview $fila): bool => $fila->generable,
        ));
    }

    /**
     * @return list<array<string, mixed>>
     */
    public function filasParaRespuesta(): array
    {
        return array_map(
            static fn (FilaPreview $fila): array => $fila->toArray(),
            $this->filas,
        );
    }

    /**
     * @return list<array<string, mixed>>
     */
    public function hallazgosParaRespuesta(): array
    {
        return array_map(
            static fn (Hallazgo $hallazgo): array => $hallazgo->toArray(),
            $this->hallazgos,
        );
    }
}
