<?php

namespace App\Modules\InscripcionesAtenciones\Http\Resources;

use App\Modules\InscripcionesAtenciones\Enums\DiaSemana;
use App\Modules\InscripcionesAtenciones\Models\PaqueteSesiones;
use App\Modules\InscripcionesAtenciones\Support\PaqueteAbilities;
use App\Modules\InscripcionesAtenciones\Support\PaqueteDetalle;
use App\Modules\InscripcionesAtenciones\Support\PaquetePeriod;
use App\Modules\InscripcionesAtenciones\Support\PaqueteSnapshot;
use App\Modules\Pacientes\Support\PacientesPermission;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Ficha de paquete (RF-008 / RF-009). Sin datos clínicos.
 */
final class PaqueteResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        /** @var PaqueteDetalle $detalle */
        $detalle = $this->resource;
        $paquete = $detalle->paquete;
        $user = $request->user();
        $viewContact = $user?->can(PacientesPermission::TUTOR_VIEW_CONTACT) ?? false;
        $telefono = $viewContact ? $detalle->tutor->telefono : null;
        $estado = $paquete->estado->mostrado(
            $paquete->fecha_fin->toDateString(),
            PaquetePeriod::today(),
        );

        return [
            'id' => (int) $paquete->id,
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
            'servicio' => $detalle->servicio,
            'categoria' => $detalle->categoria,
            'profesional' => $detalle->profesional,
            'cantidad_sesiones' => (int) $paquete->cantidad_sesiones,
            'dias_semana' => array_values($paquete->dias_semana),
            'dias_semana_labels' => array_map(
                static fn (int $dia): string => DiaSemana::labelFor($dia),
                $paquete->dias_semana,
            ),
            'hora' => PaqueteSnapshot::horaCorta((string) $paquete->hora),
            'duracion_minutos' => (int) $paquete->duracion_minutos,
            'fecha_inicio' => $paquete->fecha_inicio->toDateString(),
            'fecha_fin' => $paquete->fecha_fin->toDateString(),
            'monto_esperado' => (string) $paquete->monto_esperado,
            'tarifa_id' => (int) $paquete->tarifa_id,
            'estado' => $paquete->estado->value,
            'estado_mostrado' => $estado->value,
            'estado_label' => $estado->label(),
            'observaciones' => $paquete->observaciones,
            'paquete_origen_id' => $paquete->paquete_origen_id,
            'origen' => $this->summary($detalle->origen),
            'renovada_por' => $this->summary($detalle->renovadaPor),
            'retiro' => $detalle->retiro,
            'created_at' => $paquete->created_at?->toIso8601String(),
            'updated_at' => $paquete->updated_at?->toIso8601String(),
            'can' => $user === null
                ? []
                : array_merge(
                    PaqueteAbilities::for($user),
                    PaqueteAbilities::forRow($user, $paquete),
                ),
        ];
    }

    /**
     * @return array<string, mixed>|null
     */
    private function summary(?PaqueteSesiones $paquete): ?array
    {
        if ($paquete === null) {
            return null;
        }

        return [
            'id' => (int) $paquete->id,
            'fecha_inicio' => $paquete->fecha_inicio->toDateString(),
            'fecha_fin' => $paquete->fecha_fin->toDateString(),
            'cantidad_sesiones' => (int) $paquete->cantidad_sesiones,
            'estado' => $paquete->estado->value,
            'estado_label' => $paquete->estado->label(),
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
