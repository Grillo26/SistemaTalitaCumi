<?php

namespace App\Modules\InscripcionesAtenciones\Http\Resources;

use App\Modules\InscripcionesAtenciones\Models\InscripcionSala;
use App\Modules\InscripcionesAtenciones\Support\InscripcionAbilities;
use App\Modules\InscripcionesAtenciones\Support\InscripcionDetalle;
use App\Modules\InscripcionesAtenciones\Support\InscripcionPeriod;
use App\Modules\Pacientes\Support\PacientesPermission;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Ficha de inscripción (RF-008).
 *
 * El teléfono del tutor se enmascara sin `tutor.ver_contacto`. No se envían
 * diagnóstico, alergias, condiciones médicas ni contactos de emergencia.
 */
final class InscripcionResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        /** @var InscripcionDetalle $detalle */
        $detalle = $this->resource;
        $inscripcion = $detalle->inscripcion;
        $user = $request->user();
        $viewContact = $user?->can(PacientesPermission::TUTOR_VIEW_CONTACT) ?? false;
        $telefono = $viewContact ? $detalle->tutor->telefono : null;
        $estadoMostrado = $inscripcion->estado->mostrado(
            $inscripcion->fecha_fin->toDateString(),
            InscripcionPeriod::today(),
        );

        return [
            'id' => (int) $inscripcion->id,
            'paciente' => [
                'id' => $detalle->paciente->id,
                'codigo' => $detalle->paciente->codigo,
                'nombre_completo' => $detalle->paciente->nombreCompleto,
                'fecha_nacimiento' => $detalle->paciente->fechaNacimiento,
                'edad_anios' => $detalle->paciente->edadAnios,
                'estado' => $detalle->paciente->estado->value,
                'estado_label' => $detalle->paciente->estado->label(),
            ],
            'tutor' => [
                'id' => $detalle->tutor->id,
                'nombre_completo' => $detalle->tutor->nombreCompleto,
                'parentesco' => $detalle->tutor->parentesco,
                'es_principal' => $detalle->tutor->esPrincipal,
                'activo' => $detalle->tutor->activo,
                'telefono' => $telefono,
                'telefono_enmascarado' => $viewContact ? null : $this->maskPhone($detalle->tutor->telefono),
                'whatsapp_disponible' => $viewContact && $telefono !== null && $telefono !== '',
                'contacto_protegido' => ! $viewContact,
            ],
            'sucursal' => $detalle->sucursal,
            'sala' => $detalle->sala,
            'servicio' => $detalle->servicio,
            'categoria' => $detalle->categoria,
            'fecha_inicio' => $inscripcion->fecha_inicio->toDateString(),
            'fecha_fin' => $inscripcion->fecha_fin->toDateString(),
            'monto_esperado' => (string) $inscripcion->monto_esperado,
            'tarifa_id' => (int) $inscripcion->tarifa_id,
            'estado' => $inscripcion->estado->value,
            'estado_mostrado' => $estadoMostrado->value,
            'estado_label' => $estadoMostrado->label(),
            'observaciones' => $inscripcion->observaciones,
            'inscripcion_origen_id' => $inscripcion->inscripcion_origen_id,
            'origen' => $this->summary($detalle->origen),
            'renovada_por' => $this->summary($detalle->renovadaPor),
            'retiro' => $detalle->retiro,
            'created_at' => $inscripcion->created_at?->toIso8601String(),
            'updated_at' => $inscripcion->updated_at?->toIso8601String(),
            'can' => $user === null
                ? []
                : array_merge(
                    InscripcionAbilities::for($user),
                    InscripcionAbilities::forRow($user, $inscripcion),
                ),
        ];
    }

    /**
     * @return array<string, mixed>|null
     */
    private function summary(?InscripcionSala $inscripcion): ?array
    {
        if ($inscripcion === null) {
            return null;
        }

        return [
            'id' => (int) $inscripcion->id,
            'fecha_inicio' => $inscripcion->fecha_inicio->toDateString(),
            'fecha_fin' => $inscripcion->fecha_fin->toDateString(),
            'estado' => $inscripcion->estado->value,
            'estado_label' => $inscripcion->estado->label(),
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
