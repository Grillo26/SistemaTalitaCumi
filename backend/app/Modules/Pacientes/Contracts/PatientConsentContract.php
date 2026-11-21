<?php

namespace App\Modules\Pacientes\Contracts;

/**
 * Consulta de consentimientos vigentes para consumidores internos.
 *
 * No expone motivos, observaciones ni datos de contacto del tutor.
 */
interface PatientConsentContract
{
    public function tieneConsentimientoVigente(int $pacienteId, string $tipoCodigo = 'atencion'): bool;

    public function consentimientoVigente(int $pacienteId, string $tipoCodigo = 'atencion'): ?ConsentimientoResumen;
}
