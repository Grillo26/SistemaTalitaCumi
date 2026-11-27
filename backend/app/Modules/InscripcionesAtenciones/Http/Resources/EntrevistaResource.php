<?php

namespace App\Modules\InscripcionesAtenciones\Http\Resources;

use App\Modules\InscripcionesAtenciones\Support\AtencionAbilities;
use App\Modules\InscripcionesAtenciones\Support\AtencionDetalle;
use App\Modules\Pacientes\Support\PacientesPermission;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Ficha de entrevista (RF-009). Sin datos clínicos.
 */
final class EntrevistaResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        /** @var AtencionDetalle $detalle */
        $detalle = $this->resource;
        $entrevista = $detalle->atencion;
        $user = $request->user();
        $viewContact = $user?->can(PacientesPermission::TUTOR_VIEW_CONTACT) ?? false;
        $telefono = $viewContact ? $detalle->tutor?->telefono : null;

        return [
            'id' => (int) $entrevista->id,
            'paciente' => [
                'id' => $detalle->paciente->id,
                'codigo' => $detalle->paciente->codigo,
                'nombre_completo' => $detalle->paciente->nombreCompleto,
                'fecha_nacimiento' => $detalle->paciente->fechaNacimiento,
                'edad_anios' => $detalle->paciente->edadAnios,
                'estado' => $detalle->paciente->estado->value,
                'estado_label' => $detalle->paciente->estado->label(),
            ],
            'tutor' => $detalle->tutor === null ? null : [
                'id' => $detalle->tutor->id,
                'nombre_completo' => $detalle->tutor->nombreCompleto,
                'parentesco' => $detalle->tutor->parentesco,
                'es_principal' => $detalle->tutor->esPrincipal,
                'activo' => $detalle->tutor->activo,
                'telefono' => $telefono,
                'telefono_enmascarado' => $viewContact ? null : $this->maskPhone($detalle->tutor->telefono),
                'contacto_protegido' => ! $viewContact,
            ],
            'sucursal' => $detalle->sucursal,
            'servicio' => $detalle->servicio,
            'categoria' => $detalle->categoria,
            'fecha' => $entrevista->fecha->toDateString(),
            'monto_esperado' => (string) $entrevista->monto_esperado,
            'tarifa_id' => (int) $entrevista->tarifa_id,
            'estado' => $entrevista->estado->value,
            'estado_label' => $entrevista->estado->label(),
            'notas_admin' => $entrevista->notas_admin,
            'retiro' => $detalle->retiro,
            'created_at' => $entrevista->created_at?->toIso8601String(),
            'updated_at' => $entrevista->updated_at?->toIso8601String(),
            'can' => $user === null
                ? []
                : array_merge(
                    AtencionAbilities::for($user),
                    AtencionAbilities::forRow($user, $entrevista->estado),
                ),
        ];
    }

    private function maskPhone(string $phone): ?string
    {
        $digits = preg_replace('/\D+/', '', $phone);

        if ($digits === null || $digits === '') {
            return null;
        }

        if (strlen($digits) <= 5) {
            return '***';
        }

        return substr($digits, 0, 3).'***'.substr($digits, -2);
    }
}
