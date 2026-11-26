<?php

namespace App\Modules\InscripcionesAtenciones\Actions;

use App\Models\User;
use App\Modules\InscripcionesAtenciones\Enums\TipoAtencion;
use App\Modules\InscripcionesAtenciones\Events\AtencionUpdated;
use App\Modules\InscripcionesAtenciones\Exceptions\AtencionDiagnosticoNotFoundException;
use App\Modules\InscripcionesAtenciones\Models\AtencionDiagnostico;
use App\Modules\InscripcionesAtenciones\Rules\AtencionFechasRule;
use App\Modules\InscripcionesAtenciones\Rules\AtencionStateTransitionRule;
use App\Modules\InscripcionesAtenciones\Rules\NotasAdministrativasRule;
use App\Modules\InscripcionesAtenciones\Rules\ProfesionalesRequeridosRule;
use App\Modules\InscripcionesAtenciones\Support\AtencionSnapshot;
use App\Modules\InscripcionesAtenciones\Support\ProfesionalAtencionGuard;
use App\Modules\Organizacion\Contracts\UserBranchAccessContract;
use App\Modules\Organizacion\Exceptions\BranchAccessDeniedException;
use Illuminate\Support\Facades\DB;

/**
 * [Entrevista y Diagnóstico] RF-010, RF-004. CA-003, CA-006.
 *
 * Solo permite modificar fechas, profesionales y notas. El tipo, servicio,
 * categoría, tarifa y monto son inmutables.
 */
final class UpdateDiagnosticoAction
{
    public function __construct(
        private readonly UserBranchAccessContract $access,
        private readonly ProfesionalAtencionGuard $profesionales,
    ) {}

    /**
     * @param  array<string, mixed>  $data
     */
    public function __invoke(int $id, array $data, User $actor, ?string $ip = null): AtencionDiagnostico
    {
        return DB::transaction(function () use ($id, $data, $actor, $ip): AtencionDiagnostico {
            $diagnostico = AtencionDiagnostico::query()
                ->lockForUpdate()
                ->with('requisitos')
                ->find($id);

            if ($diagnostico === null) {
                throw new AtencionDiagnosticoNotFoundException($id);
            }

            if (! $this->access->puedeAcceder($actor->id, (int) $diagnostico->sucursal_id)) {
                throw new BranchAccessDeniedException;
            }

            AtencionStateTransitionRule::assertPuedeEditar($diagnostico->estado);

            $before = AtencionSnapshot::diagnostico($diagnostico);

            $fechaInicio = array_key_exists('fecha_inicio', $data)
                ? (string) $data['fecha_inicio']
                : $diagnostico->fecha_inicio->toDateString();
            $fechaFin = array_key_exists('fecha_fin', $data)
                ? (string) $data['fecha_fin']
                : $diagnostico->fecha_fin->toDateString();
            $notas = array_key_exists('notas_admin', $data)
                ? $this->nullableString($data['notas_admin'])
                : $diagnostico->notas_admin;

            AtencionFechasRule::assertDiagnostico($fechaInicio, $fechaFin);
            NotasAdministrativasRule::assertValidas($notas);

            $ids = null;

            if (array_key_exists('profesionales', $data)) {
                $ids = $this->normalizarIds($data['profesionales']);
                $profesionales = $this->profesionales->cargar($ids, (int) $diagnostico->sucursal_id);
                $requisitos = $diagnostico->requisitos
                    ->map(static fn ($requisito): array => [
                        'especialidad_id' => (int) $requisito->especialidad_id,
                        'especialidad_nombre' => (string) $requisito->especialidad_nombre,
                        'cantidad_minima' => (int) $requisito->cantidad_minima,
                    ])
                    ->values()
                    ->all();

                ProfesionalesRequeridosRule::assertCobertura($requisitos, $profesionales);
            }

            $diagnostico->fill([
                'fecha_inicio' => $fechaInicio,
                'fecha_fin' => $fechaFin,
                'notas_admin' => $notas,
            ])->save();

            if ($ids !== null) {
                $diagnostico->profesionales()->sync($ids);
            }

            $diagnostico->refresh()->load(['profesionales:id,nombre_completo', 'requisitos']);
            $after = AtencionSnapshot::diagnostico($diagnostico);
            $changed = $this->changedFields($before, $after);

            if ($changed !== []) {
                event(new AtencionUpdated(
                    tipo: TipoAtencion::DIAGNOSTICO,
                    atencionId: (int) $diagnostico->id,
                    before: $before,
                    after: $after,
                    changedFields: $changed,
                    actorId: $actor->id,
                    ip: $ip,
                ));
            }

            return $diagnostico;
        });
    }

    /**
     * @param  array<string, mixed>  $before
     * @param  array<string, mixed>  $after
     * @return list<string>
     */
    private function changedFields(array $before, array $after): array
    {
        $changed = [];

        foreach ($after as $key => $value) {
            if (($before[$key] ?? null) !== $value) {
                $changed[] = $key;
            }
        }

        return $changed;
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
