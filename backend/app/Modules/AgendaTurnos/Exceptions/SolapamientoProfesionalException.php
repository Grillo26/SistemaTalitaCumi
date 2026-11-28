<?php

namespace App\Modules\AgendaTurnos\Exceptions;

use App\Modules\AgendaTurnos\Support\Hallazgo;

final class SolapamientoProfesionalException extends AgendaTurnosDomainException
{
    /**
     * @param  list<Hallazgo>  $hallazgos
     */
    public function __construct(
        public readonly array $hallazgos,
    ) {
        parent::__construct('El profesional tiene conflictos de horario en uno o más turnos del lote.');
    }

    public function status(): int
    {
        return 409;
    }

    public function errorCode(): string
    {
        return 'SOLAPAMIENTO_PROFESIONAL';
    }

    public function context(): array
    {
        return [
            'hallazgos' => array_map(
                static fn (Hallazgo $hallazgo): array => $hallazgo->toArray(),
                $this->hallazgos,
            ),
        ];
    }
}
