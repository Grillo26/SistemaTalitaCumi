<?php

namespace App\Modules\AgendaTurnos\Exceptions;

use App\Modules\AgendaTurnos\Support\Hallazgo;

final class AdvertenciasNoConfirmadasException extends AgendaTurnosDomainException
{
    /**
     * @param  list<Hallazgo>  $hallazgos
     */
    public function __construct(
        public readonly array $hallazgos,
    ) {
        parent::__construct('La generación tiene advertencias que deben confirmarse explícitamente.');
    }

    public function status(): int
    {
        return 422;
    }

    public function errorCode(): string
    {
        return 'ADVERTENCIAS_NO_CONFIRMADAS';
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
