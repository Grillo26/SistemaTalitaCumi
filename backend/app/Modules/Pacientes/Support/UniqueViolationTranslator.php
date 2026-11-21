<?php

namespace App\Modules\Pacientes\Support;

use App\Modules\Pacientes\Exceptions\DuplicatePacienteDocumentException;
use App\Modules\Pacientes\Exceptions\DuplicateTutorException;
use Illuminate\Database\QueryException;

/**
 * Traducción de violaciones de índices únicos a excepciones de dominio.
 */
final class UniqueViolationTranslator
{
    public static function paciente(QueryException $exception): never
    {
        $message = $exception->getMessage();

        if (str_contains($message, 'paciente_documento_normalizado_uk')
            || str_contains($message, 'paciente.documento_normalizado')) {
            throw new DuplicatePacienteDocumentException;
        }

        throw $exception;
    }

    public static function tutor(QueryException $exception): never
    {
        $message = $exception->getMessage();

        if (str_contains($message, 'tutor_documento_normalizado_uk')
            || str_contains($message, 'tutor.documento_normalizado')) {
            throw new DuplicateTutorException(['ci' => ['Ya existe un tutor con ese documento.']]);
        }

        throw $exception;
    }
}
