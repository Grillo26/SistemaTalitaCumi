<?php

namespace App\Modules\Pacientes\Support;

use App\Modules\Pacientes\Models\Consentimiento;

/**
 * Snapshot no sensible de un consentimiento para bitácora (04.03 Fase B).
 *
 * No incluye el motivo libre ni datos de contacto del tutor.
 */
final class ConsentimientoSnapshot
{
    /**
     * @return array<string, mixed>
     */
    public static function from(Consentimiento $consentimiento): array
    {
        $consentimiento->loadMissing('tipo');

        return [
            'id' => (int) $consentimiento->id,
            'ficha_ingreso_id' => (int) $consentimiento->ficha_ingreso_id,
            'consentimiento_tipo_id' => (int) $consentimiento->consentimiento_tipo_id,
            'tipo_codigo' => $consentimiento->tipo?->codigo,
            'tutor_id' => (int) $consentimiento->tutor_id,
            'resultado' => $consentimiento->resultado->value,
            'fecha' => $consentimiento->fecha->toDateString(),
            'revoca_a_id' => $consentimiento->revoca_a_id === null
                ? null
                : (int) $consentimiento->revoca_a_id,
            'usuario_id' => (int) $consentimiento->usuario_id,
            'created_at' => $consentimiento->created_at?->toIso8601String(),
        ];
    }
}
