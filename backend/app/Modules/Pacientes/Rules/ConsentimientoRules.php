<?php

namespace App\Modules\Pacientes\Rules;

use App\Modules\Pacientes\Exceptions\ConsentimientoTutorNotAssociatedException;
use App\Modules\Pacientes\Exceptions\ConsentimientoTutorRequiredException;
use App\Modules\Pacientes\Exceptions\FichaIngresoArchivedException;
use App\Modules\Pacientes\Models\FichaIngreso;
use App\Modules\Pacientes\Models\Paciente;

/**
 * Reglas de registro de consentimientos (04.03 Fase B).
 *
 * FE-01: el tutor debe estar asociado.
 * FE-02 / CA-005: sin tutores activos no se registran consentimientos.
 * La ficha archivada bloquea registrar y revocar.
 */
final class ConsentimientoRules
{
    public function assertFichaEditable(FichaIngreso $ficha): void
    {
        if ($ficha->estaArchivada()) {
            throw new FichaIngresoArchivedException;
        }
    }

    public function assertTieneTutores(Paciente $paciente): void
    {
        $tiene = $paciente->relacionesTutor()
            ->whereHas('tutor', static fn ($query) => $query->where('activo', true))
            ->exists();

        if (! $tiene) {
            throw new ConsentimientoTutorRequiredException;
        }
    }

    public function assertTutorAsociado(Paciente $paciente, int $tutorId): void
    {
        $asociado = $paciente->relacionesTutor()
            ->where('tutor_id', $tutorId)
            ->whereHas('tutor', static fn ($query) => $query->where('activo', true))
            ->exists();

        if (! $asociado) {
            throw new ConsentimientoTutorNotAssociatedException;
        }
    }
}
