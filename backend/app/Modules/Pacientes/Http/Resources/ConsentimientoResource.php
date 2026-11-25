<?php

namespace App\Modules\Pacientes\Http\Resources;

use App\Models\User;
use App\Modules\Pacientes\Models\Consentimiento;
use App\Modules\Pacientes\Models\Parentesco;
use App\Modules\Pacientes\Support\ConsentimientoPermission;
use App\Modules\Pacientes\Support\FichaIngresoPermission;

/**
 * Consentimiento (04.03 Fase B).
 *
 * El motivo de negativa o revocación es texto libre y se oculta salvo que el
 * actor pueda revocar o ver datos sensibles. El nombre del tutor se muestra
 * sin teléfono ni documento.
 */
final class ConsentimientoResource
{
    /**
     * @return array<string, mixed>
     */
    public static function for(
        User $actor,
        Consentimiento $consentimiento,
        bool $vigente,
        ?Parentesco $parentesco = null,
    ): array {
        $consentimiento->loadMissing(['tipo', 'tutor', 'usuario']);
        $motivoVisible = $actor->can(ConsentimientoPermission::REVOKE)
            || $actor->can(FichaIngresoPermission::VIEW_SENSITIVE);

        return [
            'id' => (int) $consentimiento->id,
            'ficha_ingreso_id' => (int) $consentimiento->ficha_ingreso_id,
            'tipo' => ConsentimientoTipoResource::for($consentimiento->tipo),
            'tutor' => [
                'id' => (int) $consentimiento->tutor_id,
                'nombre_completo' => $consentimiento->tutor?->nombre_completo,
                'parentesco' => $parentesco?->nombre ?? $consentimiento->tutor?->parentesco,
            ],
            'resultado' => $consentimiento->resultado->value,
            'resultado_label' => $consentimiento->resultado->label(),
            'fecha' => $consentimiento->fecha->toDateString(),
            'vigente' => $vigente,
            'revoca_a_id' => $consentimiento->revoca_a_id === null
                ? null
                : (int) $consentimiento->revoca_a_id,
            'usuario_id' => (int) $consentimiento->usuario_id,
            'usuario_nombre' => $consentimiento->usuario?->name,
            'motivo' => $motivoVisible ? $consentimiento->motivo : null,
            'motivo_protegido' => ! $motivoVisible && $consentimiento->motivo !== null,
            'created_at' => $consentimiento->created_at?->toIso8601String(),
        ];
    }
}
