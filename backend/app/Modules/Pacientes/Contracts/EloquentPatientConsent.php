<?php

namespace App\Modules\Pacientes\Contracts;

use App\Modules\Pacientes\Models\Consentimiento;
use App\Modules\Pacientes\Models\FichaIngreso;

/**
 * Implementación Eloquent del contrato de consentimientos.
 *
 * Consulta la ficha más reciente del paciente. Si hay un reingreso con una
 * ficha nueva, se exige el consentimiento de esa ficha; si la última ficha
 * está archivada y no hay otra, se conserva el consentimiento histórico.
 * Esta semántica queda pendiente de validar con el cliente.
 */
final class EloquentPatientConsent implements PatientConsentContract
{
    public function tieneConsentimientoVigente(int $pacienteId, string $tipoCodigo = 'atencion'): bool
    {
        return $this->consentimientoVigente($pacienteId, $tipoCodigo) !== null;
    }

    public function consentimientoVigente(int $pacienteId, string $tipoCodigo = 'atencion'): ?ConsentimientoResumen
    {
        $ficha = FichaIngreso::query()
            ->where('paciente_id', $pacienteId)
            ->latest('fecha_ingreso')
            ->latest('id')
            ->first();

        if ($ficha === null) {
            return null;
        }

        $consentimiento = Consentimiento::query()
            ->where('ficha_ingreso_id', $ficha->id)
            ->whereHas('tipo', static fn ($query) => $query->where('codigo', $tipoCodigo))
            ->vigentes()
            ->with('tipo')
            ->latest('fecha')
            ->latest('id')
            ->first();

        if ($consentimiento === null || $consentimiento->tipo === null) {
            return null;
        }

        return new ConsentimientoResumen(
            consentimientoId: (int) $consentimiento->id,
            pacienteId: (int) $ficha->paciente_id,
            fichaIngresoId: (int) $ficha->id,
            tipoCodigo: $consentimiento->tipo->codigo,
            tutorId: (int) $consentimiento->tutor_id,
            fecha: $consentimiento->fecha->toDateString(),
            resultado: $consentimiento->resultado->value,
        );
    }
}
