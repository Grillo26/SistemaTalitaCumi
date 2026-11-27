<?php

namespace App\Modules\AgendaTurnos\Exceptions;

final class TurnoFueraDeVigenciaException extends AgendaTurnosDomainException
{
    /**
     * @param  list<string>  $errores
     */
    public function __construct(public readonly array $errores)
    {
        parent::__construct($errores[0] ?? 'La nueva fecha está fuera de la vigencia del turno.');
    }

    public function status(): int
    {
        return 422;
    }

    public function errorCode(): string
    {
        return 'TURNO_FUERA_DE_VIGENCIA';
    }

    public function context(): array
    {
        return ['errores' => $this->errores];
    }
}
