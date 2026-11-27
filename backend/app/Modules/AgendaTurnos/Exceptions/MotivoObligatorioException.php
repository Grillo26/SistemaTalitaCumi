<?php

namespace App\Modules\AgendaTurnos\Exceptions;

final class MotivoObligatorioException extends AgendaTurnosDomainException
{
    /**
     * @param  list<string>  $errores
     */
    public function __construct(public readonly array $errores)
    {
        parent::__construct($errores[0] ?? 'El motivo es obligatorio.');
    }

    public function status(): int
    {
        return 422;
    }

    public function errorCode(): string
    {
        return 'MOTIVO_OBLIGATORIO';
    }

    public function context(): array
    {
        return ['errores' => $this->errores];
    }
}
