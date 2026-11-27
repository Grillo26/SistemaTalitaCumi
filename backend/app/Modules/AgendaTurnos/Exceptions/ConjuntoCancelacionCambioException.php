<?php

namespace App\Modules\AgendaTurnos\Exceptions;

final class ConjuntoCancelacionCambioException extends AgendaTurnosDomainException
{
    /**
     * @param  array<string, mixed>  $preview
     */
    public function __construct(public readonly array $preview)
    {
        parent::__construct(
            'El conjunto de turnos cambió desde la previsualización. Revisá la lista actualizada y volvé a confirmar.',
        );
    }

    public function status(): int
    {
        return 409;
    }

    public function errorCode(): string
    {
        return 'CONJUNTO_CANCELACION_CAMBIO';
    }

    public function context(): array
    {
        return ['preview' => $this->preview];
    }
}
