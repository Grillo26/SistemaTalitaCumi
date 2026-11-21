<?php

namespace App\Modules\Profesionales\Exceptions;

final class DeletionNotAllowedException extends ProfesionalesDomainException
{
    public function __construct(
        private readonly string $entityLabel,
        string $name,
        private readonly string $deletionCode,
    ) {
        parent::__construct(sprintf(
            'No se puede eliminar %s "%s". Usá la acción Desactivar para conservar el historial.',
            $entityLabel,
            $name,
        ));
    }

    public function status(): int
    {
        return 409;
    }

    public function errorCode(): string
    {
        return $this->deletionCode;
    }
}
