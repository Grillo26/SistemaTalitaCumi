<?php

namespace App\Modules\AgendaTurnos\Exceptions;

use App\Modules\AgendaTurnos\Support\Hallazgo;

final class GeneracionBloqueadaException extends AgendaTurnosDomainException
{
    /**
     * @param  list<Hallazgo>  $hallazgos
     */
    public function __construct(
        public readonly array $hallazgos,
    ) {
        parent::__construct('La generación tiene bloqueos que impiden confirmar.');
    }

    public function status(): int
    {
        return 409;
    }

    public function errorCode(): string
    {
        return 'GENERACION_BLOQUEADA';
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
