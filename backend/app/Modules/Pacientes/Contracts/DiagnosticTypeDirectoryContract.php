<?php

namespace App\Modules\Pacientes\Contracts;

/**
 * Catálogo de tipos de diagnóstico (módulo 05).
 *
 * Contrato pequeño para que 04.01 no dependa del catálogo hasta que exista.
 */
interface DiagnosticTypeDirectoryContract
{
    public function existe(int $tipoDiagnosticoId): bool;

    public function estaActivo(int $tipoDiagnosticoId): bool;

    /**
     * @throws \LogicException
     */
    public function asegurarActivo(int $tipoDiagnosticoId): void;

    /**
     * @throws \LogicException
     */
    public function nombre(int $tipoDiagnosticoId): string;
}
