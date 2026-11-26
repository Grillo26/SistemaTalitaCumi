<?php

namespace App\Modules\InscripcionesAtenciones\Queries;

use App\Models\User;
use App\Modules\InscripcionesAtenciones\Exceptions\InscripcionNotFoundException;
use App\Modules\InscripcionesAtenciones\Models\BitacoraEntry;
use App\Modules\InscripcionesAtenciones\Models\InscripcionSala;
use App\Modules\InscripcionesAtenciones\Support\InscripcionDetalle;
use App\Modules\InscripcionesAtenciones\Support\InscripcionQueryJoins;
use App\Modules\Organizacion\Contracts\UserBranchAccessContract;
use App\Modules\Organizacion\Exceptions\BranchAccessDeniedException;
use App\Modules\Pacientes\Contracts\PatientDirectoryContract;
use App\Modules\Pacientes\Contracts\TutorDirectoryContract;

/**
 * Ficha de una inscripción (RF-008).
 */
final class GetInscripcionQuery
{
    public function __construct(
        private readonly UserBranchAccessContract $access,
        private readonly PatientDirectoryContract $patients,
        private readonly TutorDirectoryContract $tutors,
    ) {}

    public function __invoke(int $id, User $actor): InscripcionDetalle
    {
        $inscripcion = InscripcionQueryJoins::apply(InscripcionSala::query())
            ->where('inscripcion_sala.id', $id)
            ->first();

        if ($inscripcion === null) {
            throw new InscripcionNotFoundException($id);
        }

        if (! $this->access->puedeAcceder($actor->id, (int) $inscripcion->sucursal_id)) {
            throw new BranchAccessDeniedException;
        }

        $retiro = BitacoraEntry::query()
            ->with('usuario:id,name')
            ->where('entidad', 'inscripcion_sala')
            ->where('entidad_id', $id)
            ->where('accion', 'RETIRAR')
            ->latest('id')
            ->first();

        $origen = $inscripcion->inscripcion_origen_id === null
            ? null
            : InscripcionSala::query()->find($inscripcion->inscripcion_origen_id);

        $renovadaPor = InscripcionSala::query()
            ->where('inscripcion_origen_id', $id)
            ->latest('id')
            ->first();

        $retiroData = $retiro?->datos_nuevos ?? [];

        return new InscripcionDetalle(
            inscripcion: $inscripcion,
            paciente: $this->patients->resumen((int) $inscripcion->paciente_id),
            tutor: $this->tutors->resumen((int) $inscripcion->tutor_responsable_id),
            sala: [
                'id' => (int) $inscripcion->sala_id,
                'nombre' => (string) ($inscripcion->sala_nombre ?? ''),
                'tipo_sala' => $inscripcion->tipo_sala_nombre !== null
                    ? (string) $inscripcion->tipo_sala_nombre
                    : null,
                'turno' => $inscripcion->turno_nombre !== null
                    ? (string) $inscripcion->turno_nombre
                    : null,
            ],
            servicio: [
                'id' => (int) $inscripcion->servicio_id,
                'nombre' => (string) ($inscripcion->servicio_nombre ?? ''),
                'tipo_servicio' => $inscripcion->tipo_servicio_nombre !== null
                    ? (string) $inscripcion->tipo_servicio_nombre
                    : null,
            ],
            categoria: [
                'id' => (int) $inscripcion->categoria_tarifa_id,
                'nombre' => (string) ($inscripcion->categoria_tarifa_nombre ?? ''),
            ],
            sucursal: [
                'id' => (int) $inscripcion->sucursal_id,
                'nombre' => (string) ($inscripcion->sucursal_nombre ?? ''),
            ],
            retiro: $retiro === null ? null : [
                'fecha_baja' => $retiroData['fecha_baja'] ?? null,
                'motivo_codigo' => $retiroData['motivo_codigo'] ?? null,
                'motivo_detalle' => $retiroData['motivo_detalle'] ?? null,
                'registrado_en' => $retiro->created_at?->toIso8601String(),
                'usuario_id' => $retiro->usuario_id,
                'usuario_nombre' => $retiro->usuario?->name,
            ],
            origen: $origen,
            renovadaPor: $renovadaPor,
        );
    }
}
