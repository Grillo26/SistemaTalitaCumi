<?php

namespace App\Modules\AgendaTurnos\Exceptions;

final class ProfesionalNoAsignableException extends AgendaTurnosDomainException
{
    public function __construct(
        public readonly int $profesionalId,
        public readonly string $detalle,
    ) {
        parent::__construct($detalle);
    }

    public function status(): int
    {
        return 422;
    }

    public function errorCode(): string
    {
        return 'PROFESIONAL_NO_ASIGNABLE';
    }

    public function context(): array
    {
        return ['profesional_id' => $this->profesionalId];
    }
}
