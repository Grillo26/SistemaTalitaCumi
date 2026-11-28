<?php

namespace App\Modules\AgendaTurnos\Exceptions;

use App\Modules\AgendaTurnos\Support\Hallazgo;

final class ConflictoDisponibilidadException extends AgendaTurnosDomainException
{
    /**
     * @param  list<Hallazgo>  $hallazgos
     */
    public function __construct(public readonly array $hallazgos)
    {
        parent::__construct('La nueva fecha y hora tienen conflictos de disponibilidad.');
    }

    public function status(): int
    {
        return 409;
    }

    public function errorCode(): string
    {
        return 'CONFLICTO_DISPONIBILIDAD';
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
