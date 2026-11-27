<?php

namespace App\Modules\InscripcionesAtenciones\Actions;

use App\Models\User;
use App\Modules\CatalogoServicios\Contracts\TarifaAplicableResolverContract;
use App\Modules\CatalogoServicios\Exceptions\TipoDiagnosticoEspecialidadInactivaException;
use App\Modules\InscripcionesAtenciones\Enums\EstadoAtencion;
use App\Modules\InscripcionesAtenciones\Events\DiagnosticoCreated;
use App\Modules\InscripcionesAtenciones\Exceptions\EntrevistaNoEncontradaException;
use App\Modules\InscripcionesAtenciones\Exceptions\EntrevistaNoPerteneceAlPacienteException;
use App\Modules\InscripcionesAtenciones\Exceptions\TarifaCambioException;
use App\Modules\InscripcionesAtenciones\Models\AtencionDiagnostico;
use App\Modules\InscripcionesAtenciones\Models\AtencionDiagnosticoRequisito;
use App\Modules\InscripcionesAtenciones\Models\AtencionEntrevista;
use App\Modules\InscripcionesAtenciones\Queries\GetRequisitosDiagnosticoQuery;
use App\Modules\InscripcionesAtenciones\Rules\AtencionDatosObligatoriosRule;
use App\Modules\InscripcionesAtenciones\Rules\AtencionFechasRule;
use App\Modules\InscripcionesAtenciones\Rules\EntrevistaOmitidaRule;
use App\Modules\InscripcionesAtenciones\Rules\NotasAdministrativasRule;
use App\Modules\InscripcionesAtenciones\Rules\ProfesionalesRequeridosRule;
use App\Modules\InscripcionesAtenciones\Rules\TarifaVigenteRule;
use App\Modules\InscripcionesAtenciones\Support\AtencionSnapshot;
use App\Modules\InscripcionesAtenciones\Support\ProfesionalAtencionGuard;
use App\Modules\Organizacion\Contracts\BranchDirectoryContract;
use App\Modules\Organizacion\Contracts\UserBranchAccessContract;
use App\Modules\Organizacion\Exceptions\BranchAccessDeniedException;
use App\Modules\Pacientes\Contracts\PatientDirectoryContract;
use Illuminate\Support\Facades\DB;

/**
 * [Entrevista y Diagnóstico] RF-002, RF-003, RF-004, RF-005, RF-006, RF-007,
 * RF-010, RF-015. RN-002, RN-003, RN-004, RN-005, RN-006, RN-007, RN-009,
 * RN-011. CA-002, CA-003, CA-004, CA-005.
 */
final class CreateDiagnosticoAction
{
    public function __construct(
        private readonly UserBranchAccessContract $access,
        private readonly BranchDirectoryContract $branches,
        private readonly PatientDirectoryContract $patients,
        private readonly TarifaAplicableResolverContract $tarifas,
        private readonly GetRequisitosDiagnosticoQuery $requisitos,
        private readonly ProfesionalAtencionGuard $profesionales,
    ) {}

    /**
     * @param  array<string, mixed>  $data
     */
    public function __invoke(array $data, User $actor, ?string $ip = null): AtencionDiagnostico
    {
        $pacienteId = (int) $data['paciente_id'];
        $sucursalId = (int) $data['sucursal_id'];
        $tipoId = (int) $data['tipo_diagnostico_id'];
        $categoriaId = (int) $data['categoria_tarifa_id'];
        $fechaInicio = (string) $data['fecha_inicio'];
        $fechaFin = (string) $data['fecha_fin'];
        $notas = $this->nullableString($data['notas_admin'] ?? null);
        $omitio = (bool) ($data['omitio_entrevista'] ?? false);
        $motivo = $this->nullableString($data['motivo_omision_entrevista'] ?? null);
        $entrevistaId = isset($data['entrevista_previa_id'])
            ? (int) $data['entrevista_previa_id']
            : null;

        if (! $this->access->puedeAcceder($actor->id, $sucursalId)) {
            throw new BranchAccessDeniedException;
        }

        $this->branches->asegurarActiva($sucursalId);
        $this->patients->asegurarPermiteInscripcion($pacienteId);

        $catalogo = ($this->requisitos)($tipoId);
        $tipo = $catalogo['tipo'];
        $requisitos = $catalogo['especialidades'];

        foreach ($requisitos as $requisito) {
            if (! $requisito['activo']) {
                throw new TipoDiagnosticoEspecialidadInactivaException(
                    (int) $requisito['especialidad_id'],
                );
            }
        }

        $entrevista = $this->resolveEntrevista($entrevistaId, $pacienteId, $actor->id);
        EntrevistaOmitidaRule::assertValida(
            $entrevista?->id,
            $omitio,
            $motivo,
        );

        AtencionFechasRule::assertDiagnostico($fechaInicio, $fechaFin);
        NotasAdministrativasRule::assertValidas($notas);
        AtencionDatosObligatoriosRule::assertCompletos([
            'paciente_id' => $pacienteId,
            'sucursal_id' => $sucursalId,
            'tipo_diagnostico_id' => $tipoId,
            'servicio_id' => $tipo['servicio_id'],
            'categoria_tarifa_id' => $categoriaId,
            'fecha_inicio' => $fechaInicio,
            'fecha_fin' => $fechaFin,
        ]);

        $profesionalesIds = $this->normalizarIds($data['profesionales'] ?? []);
        $profesionales = $this->profesionales->cargar($profesionalesIds, $sucursalId);
        ProfesionalesRequeridosRule::assertCobertura(
            $requisitos,
            $profesionales,
        );

        $tarifa = $this->tarifas->resolver(
            servicioId: (int) $tipo['servicio_id'],
            sucursalId: $sucursalId,
            categoriaTarifaId: $categoriaId,
            fecha: $fechaInicio,
        );
        TarifaVigenteRule::assertVigente($tarifa, $fechaInicio);

        $tarifaEsperada = isset($data['tarifa_id_esperada']) ? (int) $data['tarifa_id_esperada'] : null;
        $confirmar = (bool) ($data['confirmar_tarifa_cambio'] ?? false);

        if ($tarifaEsperada !== null && $tarifaEsperada !== $tarifa->tarifaId && ! $confirmar) {
            throw new TarifaCambioException($tarifa->toArray());
        }

        return DB::transaction(function () use (
            $pacienteId,
            $sucursalId,
            $tipo,
            $categoriaId,
            $fechaInicio,
            $fechaFin,
            $notas,
            $entrevista,
            $omitio,
            $motivo,
            $requisitos,
            $profesionalesIds,
            $tarifa,
            $actor,
            $ip,
        ): AtencionDiagnostico {
            $diagnostico = AtencionDiagnostico::query()->create([
                'paciente_id' => $pacienteId,
                'sucursal_id' => $sucursalId,
                'servicio_id' => (int) $tipo['servicio_id'],
                'tipo_diagnostico_id' => (int) $tipo['id'],
                'tarifa_id' => $tarifa->tarifaId,
                'categoria_tarifa_id' => $categoriaId,
                'fecha_inicio' => $fechaInicio,
                'fecha_fin' => $fechaFin,
                'monto_esperado' => $tarifa->monto,
                'entrevista_previa_id' => $entrevista?->id,
                'omitio_entrevista' => $entrevista === null ? $omitio : false,
                'motivo_omision_entrevista' => $entrevista === null ? $motivo : null,
                'estado' => EstadoAtencion::ACTIVA->value,
                'notas_admin' => $notas,
            ]);

            $diagnostico->profesionales()->sync($profesionalesIds);

            AtencionDiagnosticoRequisito::query()->insert(
                array_map(static fn (array $requisito): array => [
                    'atencion_diagnostico_id' => $diagnostico->id,
                    'especialidad_id' => (int) $requisito['especialidad_id'],
                    'especialidad_nombre' => (string) $requisito['especialidad_nombre'],
                    'cantidad_minima' => (int) $requisito['cantidad_minima'],
                    'created_at' => now(),
                ], $requisitos),
            );

            $diagnostico->refresh()->load(['profesionales:id,nombre_completo', 'requisitos']);
            $snapshot = AtencionSnapshot::diagnostico($diagnostico);

            event(new DiagnosticoCreated(
                diagnosticoId: (int) $diagnostico->id,
                pacienteId: $pacienteId,
                sucursalId: $sucursalId,
                servicioId: (int) $tipo['servicio_id'],
                tipoDiagnosticoId: (int) $tipo['id'],
                tarifaId: $tarifa->tarifaId,
                categoriaTarifaId: $categoriaId,
                fechaInicio: $fechaInicio,
                fechaFin: $fechaFin,
                montoEsperado: $tarifa->monto,
                profesionalesIds: $profesionalesIds,
                requisitos: array_map(static fn (array $requisito): array => [
                    'especialidad_id' => (int) $requisito['especialidad_id'],
                    'especialidad_nombre' => (string) $requisito['especialidad_nombre'],
                    'cantidad_minima' => (int) $requisito['cantidad_minima'],
                ], $requisitos),
                entrevistaPreviaId: $entrevista?->id,
                omitioEntrevista: $entrevista === null ? $omitio : false,
                motivoOmisionEntrevista: $entrevista === null ? $motivo : null,
                actorId: $actor->id,
                ip: $ip,
                snapshot: $snapshot,
            ));

            return $diagnostico;
        });
    }

    private function resolveEntrevista(
        ?int $entrevistaId,
        int $pacienteId,
        int $actorId,
    ): ?AtencionEntrevista {
        if ($entrevistaId === null) {
            return null;
        }

        $entrevista = AtencionEntrevista::query()->find($entrevistaId);

        if ($entrevista === null) {
            throw new EntrevistaNoEncontradaException($entrevistaId);
        }

        if ((int) $entrevista->paciente_id !== $pacienteId) {
            throw new EntrevistaNoPerteneceAlPacienteException($entrevistaId, $pacienteId);
        }

        if (! $entrevista->estado->permiteEdicion()) {
            throw new EntrevistaNoEncontradaException($entrevistaId);
        }

        if (! $this->access->puedeAcceder($actorId, (int) $entrevista->sucursal_id)) {
            throw new BranchAccessDeniedException;
        }

        return $entrevista;
    }

    /**
     * @return list<int>
     */
    private function normalizarIds(mixed $value): array
    {
        if (! is_array($value)) {
            return [];
        }

        return array_values(array_unique(array_map('intval', $value)));
    }

    private function nullableString(mixed $value): ?string
    {
        $normalized = trim((string) $value);

        return $normalized === '' ? null : $normalized;
    }
}
