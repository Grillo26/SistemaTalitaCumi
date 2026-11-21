<?php

namespace App\Modules\Profesionales\Support;

use App\Modules\Profesionales\Exceptions\DuplicateEspecialidadNameException;
use App\Modules\Profesionales\Exceptions\DuplicateProfesionalDocumentException;
use Illuminate\Database\QueryException;

/**
 * Traducción de violaciones de índices únicos a excepciones de dominio.
 */
final class UniqueViolationTranslator
{
    public static function profesional(QueryException $exception): never
    {
        $message = $exception->getMessage();

        if (str_contains($message, 'profesional_ci_normalizado_uk')
            || str_contains($message, 'profesional.ci')
            || str_contains($message, 'lower(trim(ci))')) {
            throw new DuplicateProfesionalDocumentException;
        }

        throw $exception;
    }

    public static function especialidad(QueryException $exception): never
    {
        $message = $exception->getMessage();

        if (str_contains($message, 'especialidad_nombre_normalizado_uk')
            || str_contains($message, 'especialidad.nombre')
            || str_contains($message, 'lower(trim(nombre))')) {
            throw new DuplicateEspecialidadNameException;
        }

        throw $exception;
    }
}
