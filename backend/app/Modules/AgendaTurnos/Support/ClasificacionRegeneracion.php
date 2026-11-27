<?php

namespace App\Modules\AgendaTurnos\Support;

/**
 * Clasificación de los turnos existentes de un origen al regenerar.
 *
 * - cancelar: turnos futuros en estado Programado.
 * - preservar: Asistió, Faltó, Cancelado, Reprogramado y Programados pasados.
 */
final readonly class ClasificacionRegeneracion
{
    /**
     * @param  list<TurnoExistente>  $cancelar
     * @param  list<TurnoExistente>  $preservar
     * @param  list<TurnoExistente>  $asistidos
     * @param  list<TurnoExistente>  $faltados
     * @param  list<TurnoExistente>  $pasadosSinMarcar
     */
    public function __construct(
        public array $cancelar,
        public array $preservar,
        public array $asistidos,
        public array $faltados,
        public array $pasadosSinMarcar,
    ) {}

    /**
     * @return list<int>
     */
    public function idsCancelar(): array
    {
        return array_map(
            static fn (TurnoExistente $turno): int => $turno->id,
            $this->cancelar,
        );
    }

    public function cantidadCancelar(): int
    {
        return count($this->cancelar);
    }

    public function cantidadPreservar(): int
    {
        return count($this->preservar);
    }

    public function cantidadAsistidos(): int
    {
        return count($this->asistidos);
    }

    public function cantidadFaltados(): int
    {
        return count($this->faltados);
    }

    public function cantidadPasadosSinMarcar(): int
    {
        return count($this->pasadosSinMarcar);
    }
}
