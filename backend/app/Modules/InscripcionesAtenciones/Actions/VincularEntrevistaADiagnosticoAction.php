<?php

namespace App\Modules\InscripcionesAtenciones\Actions;

use App\Models\User;
use App\Modules\InscripcionesAtenciones\Events\EntrevistaVinculada;
use App\Modules\InscripcionesAtenciones\Exceptions\AtencionDiagnosticoNotFoundException;
use App\Modules\InscripcionesAtenciones\Exceptions\EntrevistaNoEncontradaException;
use App\Modules\InscripcionesAtenciones\Exceptions\EntrevistaNoPerteneceAlPacienteException;
use App\Modules\InscripcionesAtenciones\Exceptions\EntrevistaYaVinculadaException;
use App\Modules\InscripcionesAtenciones\Models\AtencionDiagnostico;
use App\Modules\InscripcionesAtenciones\Models\AtencionEntrevista;
use App\Modules\InscripcionesAtenciones\Rules\AtencionStateTransitionRule;
use App\Modules\InscripcionesAtenciones\Support\AtencionSnapshot;
use App\Modules\Organizacion\Contracts\UserBranchAccessContract;
use App\Modules\Organizacion\Exceptions\BranchAccessDeniedException;
use Illuminate\Support\Facades\DB;

/**
 * CU-03 FA-01: vincular una entrevista registrada después al diagnóstico.
 */
final class VincularEntrevistaADiagnosticoAction
{
    public function __construct(private readonly UserBranchAccessContract $access) {}

    public function __invoke(
        int $diagnosticoId,
        int $entrevistaId,
        User $actor,
        ?string $ip = null,
    ): AtencionDiagnostico {
        return DB::transaction(function () use ($diagnosticoId, $entrevistaId, $actor, $ip): AtencionDiagnostico {
            $diagnostico = AtencionDiagnostico::query()
                ->lockForUpdate()
                ->with(['profesionales:id,nombre_completo', 'requisitos'])
                ->find($diagnosticoId);

            if ($diagnostico === null) {
                throw new AtencionDiagnosticoNotFoundException($diagnosticoId);
            }

            if (! $this->access->puedeAcceder($actor->id, (int) $diagnostico->sucursal_id)) {
                throw new BranchAccessDeniedException;
            }

            AtencionStateTransitionRule::assertPuedeEditar($diagnostico->estado);

            if ($diagnostico->entrevista_previa_id !== null) {
                throw new EntrevistaYaVinculadaException($diagnosticoId);
            }

            $entrevista = AtencionEntrevista::query()->find($entrevistaId);

            if ($entrevista === null) {
                throw new EntrevistaNoEncontradaException($entrevistaId);
            }

            if ((int) $entrevista->paciente_id !== (int) $diagnostico->paciente_id) {
                throw new EntrevistaNoPerteneceAlPacienteException(
                    $entrevistaId,
                    (int) $diagnostico->paciente_id,
                );
            }

            if (! $entrevista->estado->permiteEdicion()) {
                throw new EntrevistaNoEncontradaException($entrevistaId);
            }

            if (! $this->access->puedeAcceder($actor->id, (int) $entrevista->sucursal_id)) {
                throw new BranchAccessDeniedException;
            }

            $before = AtencionSnapshot::diagnostico($diagnostico);

            $diagnostico->fill([
                'entrevista_previa_id' => $entrevistaId,
                'omitio_entrevista' => false,
                'motivo_omision_entrevista' => null,
            ])->save();

            $diagnostico->refresh()->load(['profesionales:id,nombre_completo', 'requisitos']);
            $after = AtencionSnapshot::diagnostico($diagnostico);

            event(new EntrevistaVinculada(
                diagnosticoId: (int) $diagnostico->id,
                entrevistaId: $entrevistaId,
                actorId: $actor->id,
                ip: $ip,
                before: $before,
                after: $after,
            ));

            return $diagnostico;
        });
    }
}
