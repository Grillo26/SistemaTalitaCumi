<?php

namespace App\Modules\Seguridad\Exceptions;

/**
 * Edición concurrente de la matriz (02/RF-R13).
 */
final class MatrixConcurrencyException extends SeguridadDomainException
{
    public function __construct()
    {
        parent::__construct('La matriz fue modificada por otro usuario. Recargá los datos antes de guardar.');
    }

    public function status(): int
    {
        return 409;
    }

    public function errorCode(): string
    {
        return 'MATRIX_VERSION_CONFLICT';
    }
}
