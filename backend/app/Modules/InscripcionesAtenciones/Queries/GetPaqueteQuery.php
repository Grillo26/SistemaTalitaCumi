<?php

namespace App\Modules\InscripcionesAtenciones\Queries;

use App\Models\User;
use App\Modules\InscripcionesAtenciones\Exceptions\PaqueteNotFoundException;
use App\Modules\InscripcionesAtenciones\Models\BitacoraEntry;
use App\Modules\InscripcionesAtenciones\Models\PaqueteSesiones;
use App\Modules\InscripcionesAtenciones\Support\PaqueteDetalle;
use App\Modules\InscripcionesAtenciones\Support\PaqueteQueryJoins;
use App\Modules\Organizacion\Contracts\UserBranchAccessContract;
use App\Modules\Organizacion\Exceptions\BranchAccessDeniedException;
use App\Modules\Pacientes\Contracts\PatientDirectoryContract;
use App\Modules\Pacientes\Contracts\TutorDirectoryContract;

/**
 * Ficha de un paquete (RF-008 / RF-009).
 */
final class GetPaqueteQuery
{
    public function __construct(
        private readonly UserBranchAccessContract $access,
        private readonly PatientDirectoryContract $patients,
        private readonly TutorDirectoryContract $tutors,
    ) {}

    public function __invoke(int $id, User $actor): PaqueteDetalle
    {
        $paquete = PaqueteQueryJoins::apply(PaqueteSesiones::query())
            ->where('paquete_sesiones.id', $id)
            ->first();

        if ($paquete === null) {
            throw new PaqueteNotFoundException($id);
        }

        if (! $this->access->puedeAcceder($actor->id, (int) $paquete->sucursal_id)) {
            throw new BranchAccessDeniedException;
        }

        $retiro = BitacoraEntry::query()
            ->with('usuario:id,name')
            ->where('entidad', 'paquete_sesiones')
            ->where('entidad_id', $id)
            ->where('accion', 'RETIRAR')
            ->latest('id')
            ->first();

        $retiroData = $retiro?->datos_nuevos ?? [];

        $origen = $paquete->paquete_origen_id === null
            ? null
            : PaqueteSesiones::query()->find($paquete->paquete_origen_id);

        $renovadaPor = PaqueteSesiones::query()
            ->where('paquete_origen_id', $id)
            ->latest('id')
            ->first();

        $profesional = $paquete->profesional_id === null
            ? null
            : [
                'id' => (int) $paquete->profesional_id,
                'nombre' => (string) ($paquete->profesional_nombre ?? ''),
            ];

        return new PaqueteDetalle(
            paquete: $paquete,
            paciente: $this->patients->resumen((int) $paquete->paciente_id),
            tutor: $this->tutors->resumen((int) $paquete->tutor_responsable_id),
            sucursal: [
                'id' => (int) $paquete->sucursal_id,
                'nombre' => (string) ($paquete->sucursal_nombre ?? ''),
            ],
            servicio: [
                'id' => (int) $paquete->servicio_id,
                'nombre' => (string) ($paquete->servicio_nombre ?? ''),
                'tipo_servicio' => $paquete->tipo_servicio_nombre !== null
                    ? (string) $paquete->tipo_servicio_nombre
                    : null,
            ],
            categoria: [
                'id' => (int) $paquete->categoria_tarifa_id,
                'nombre' => (string) ($paquete->categoria_tarifa_nombre ?? ''),
            ],
            profesional: $profesional,
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
