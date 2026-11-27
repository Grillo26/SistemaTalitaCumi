<?php

namespace App\Modules\InscripcionesAtenciones\Queries;

use App\Models\User;
use App\Modules\InscripcionesAtenciones\Enums\TipoAtencion;
use App\Modules\InscripcionesAtenciones\Exceptions\AtencionDiagnosticoNotFoundException;
use App\Modules\InscripcionesAtenciones\Exceptions\AtencionEntrevistaNotFoundException;
use App\Modules\InscripcionesAtenciones\Models\AtencionDiagnostico;
use App\Modules\InscripcionesAtenciones\Models\AtencionEntrevista;
use App\Modules\InscripcionesAtenciones\Models\BitacoraEntry;
use App\Modules\InscripcionesAtenciones\Support\AtencionDetalle;
use App\Modules\InscripcionesAtenciones\Support\AtencionQueryJoins;
use App\Modules\InscripcionesAtenciones\Support\InscripcionesAtencionesModule;
use App\Modules\Organizacion\Contracts\UserBranchAccessContract;
use App\Modules\Organizacion\Exceptions\BranchAccessDeniedException;
use App\Modules\Pacientes\Contracts\PatientDirectoryContract;
use App\Modules\Pacientes\Contracts\TutorDirectoryContract;

/**
 * Ficha de una entrevista o diagnóstico (RF-009).
 */
final class GetAtencionQuery
{
    public function __construct(
        private readonly UserBranchAccessContract $access,
        private readonly PatientDirectoryContract $patients,
        private readonly TutorDirectoryContract $tutors,
    ) {}

    public function __invoke(TipoAtencion $tipo, int $id, User $actor): AtencionDetalle
    {
        return $tipo === TipoAtencion::ENTREVISTA
            ? $this->entrevista($id, $actor)
            : $this->diagnostico($id, $actor);
    }

    private function entrevista(int $id, User $actor): AtencionDetalle
    {
        $entrevista = AtencionQueryJoins::entrevistas(AtencionEntrevista::query())
            ->where('atencion_entrevista.id', $id)
            ->first();

        if ($entrevista === null) {
            throw new AtencionEntrevistaNotFoundException($id);
        }

        if (! $this->access->puedeAcceder($actor->id, (int) $entrevista->sucursal_id)) {
            throw new BranchAccessDeniedException;
        }

        $retiro = $this->retiro(InscripcionesAtencionesModule::ENTITY_ATENCION_ENTREVISTA, $id);

        return new AtencionDetalle(
            tipo: TipoAtencion::ENTREVISTA,
            atencion: $entrevista,
            paciente: $this->patients->resumen((int) $entrevista->paciente_id),
            tutor: $this->tutors->resumen((int) $entrevista->tutor_id),
            sucursal: [
                'id' => (int) $entrevista->sucursal_id,
                'nombre' => (string) ($entrevista->sucursal_nombre ?? ''),
            ],
            servicio: [
                'id' => (int) $entrevista->servicio_id,
                'nombre' => (string) ($entrevista->servicio_nombre ?? ''),
                'tipo_servicio' => $entrevista->tipo_servicio_nombre !== null
                    ? (string) $entrevista->tipo_servicio_nombre
                    : null,
            ],
            categoria: [
                'id' => (int) $entrevista->categoria_tarifa_id,
                'nombre' => (string) ($entrevista->categoria_tarifa_nombre ?? ''),
            ],
            tipoDiagnostico: null,
            profesionales: [],
            requisitos: [],
            entrevistaPrevia: null,
            retiro: $retiro,
        );
    }

    private function diagnostico(int $id, User $actor): AtencionDetalle
    {
        $diagnostico = AtencionQueryJoins::diagnosticos(AtencionDiagnostico::query())
            ->where('atencion_diagnostico.id', $id)
            ->first();

        if ($diagnostico === null) {
            throw new AtencionDiagnosticoNotFoundException($id);
        }

        if (! $this->access->puedeAcceder($actor->id, (int) $diagnostico->sucursal_id)) {
            throw new BranchAccessDeniedException;
        }

        $diagnostico->load([
            'profesionales.especialidades',
            'requisitos',
            'entrevistaPrevia',
        ]);

        $retiro = $this->retiro(InscripcionesAtencionesModule::ENTITY_ATENCION_DIAGNOSTICO, $id);

        return new AtencionDetalle(
            tipo: TipoAtencion::DIAGNOSTICO,
            atencion: $diagnostico,
            paciente: $this->patients->resumen((int) $diagnostico->paciente_id),
            tutor: null,
            sucursal: [
                'id' => (int) $diagnostico->sucursal_id,
                'nombre' => (string) ($diagnostico->sucursal_nombre ?? ''),
            ],
            servicio: [
                'id' => (int) $diagnostico->servicio_id,
                'nombre' => (string) ($diagnostico->servicio_nombre ?? ''),
                'tipo_servicio' => $diagnostico->tipo_servicio_nombre !== null
                    ? (string) $diagnostico->tipo_servicio_nombre
                    : null,
            ],
            categoria: [
                'id' => (int) $diagnostico->categoria_tarifa_id,
                'nombre' => (string) ($diagnostico->categoria_tarifa_nombre ?? ''),
            ],
            tipoDiagnostico: [
                'id' => (int) $diagnostico->tipo_diagnostico_id,
                'nombre' => (string) ($diagnostico->tipo_diagnostico_nombre ?? ''),
                'dias_estimados' => (int) ($diagnostico->tipo_diagnostico_dias ?? 1),
            ],
            profesionales: $diagnostico->profesionales
                ->map(static fn ($profesional): array => [
                    'id' => (int) $profesional->id,
                    'nombre_completo' => (string) $profesional->nombre_completo,
                    'especialidades' => $profesional->especialidades
                        ->map(static fn ($especialidad): array => [
                            'id' => (int) $especialidad->id,
                            'nombre' => (string) $especialidad->nombre,
                        ])
                        ->values()
                        ->all(),
                ])
                ->values()
                ->all(),
            requisitos: $diagnostico->requisitos
                ->map(static fn ($requisito): array => [
                    'especialidad_id' => (int) $requisito->especialidad_id,
                    'especialidad_nombre' => (string) $requisito->especialidad_nombre,
                    'cantidad_minima' => (int) $requisito->cantidad_minima,
                ])
                ->values()
                ->all(),
            entrevistaPrevia: $diagnostico->entrevistaPrevia === null
                ? null
                : [
                    'id' => (int) $diagnostico->entrevistaPrevia->id,
                    'fecha' => $diagnostico->entrevistaPrevia->fecha->toDateString(),
                    'sucursal_id' => (int) $diagnostico->entrevistaPrevia->sucursal_id,
                ],
            retiro: $retiro,
        );
    }

    /**
     * @return array<string, mixed>|null
     */
    private function retiro(string $entidad, int $id): ?array
    {
        $retiro = BitacoraEntry::query()
            ->with('usuario:id,name')
            ->where('entidad', $entidad)
            ->where('entidad_id', $id)
            ->where('accion', 'RETIRAR')
            ->latest('id')
            ->first();

        if ($retiro === null) {
            return null;
        }

        $data = $retiro->datos_nuevos ?? [];

        return [
            'fecha_baja' => $data['fecha_baja'] ?? null,
            'motivo_codigo' => $data['motivo_codigo'] ?? null,
            'motivo_detalle' => $data['motivo_detalle'] ?? null,
            'registrado_en' => $retiro->created_at?->toIso8601String(),
            'usuario_id' => $retiro->usuario_id,
            'usuario_nombre' => $retiro->usuario?->name,
        ];
    }
}
