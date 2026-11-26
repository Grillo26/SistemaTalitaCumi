<?php

namespace App\Modules\InscripcionesAtenciones\Actions;

use App\Models\User;
use App\Modules\InscripcionesAtenciones\Enums\TipoAtencion;
use App\Modules\InscripcionesAtenciones\Events\AtencionUpdated;
use App\Modules\InscripcionesAtenciones\Exceptions\AtencionEntrevistaNotFoundException;
use App\Modules\InscripcionesAtenciones\Models\AtencionEntrevista;
use App\Modules\InscripcionesAtenciones\Rules\AtencionFechasRule;
use App\Modules\InscripcionesAtenciones\Rules\AtencionStateTransitionRule;
use App\Modules\InscripcionesAtenciones\Rules\NotasAdministrativasRule;
use App\Modules\InscripcionesAtenciones\Support\AtencionSnapshot;
use App\Modules\Organizacion\Contracts\UserBranchAccessContract;
use App\Modules\Organizacion\Exceptions\BranchAccessDeniedException;
use Illuminate\Support\Facades\DB;

/**
 * [Entrevista y Diagnóstico] RF-010. CA-007.
 *
 * La entrevista solo permite modificar fecha y notas administrativas.
 */
final class UpdateEntrevistaAction
{
    public function __construct(private readonly UserBranchAccessContract $access) {}

    /**
     * @param  array<string, mixed>  $data
     */
    public function __invoke(int $id, array $data, User $actor, ?string $ip = null): AtencionEntrevista
    {
        return DB::transaction(function () use ($id, $data, $actor, $ip): AtencionEntrevista {
            $entrevista = AtencionEntrevista::query()->lockForUpdate()->find($id);

            if ($entrevista === null) {
                throw new AtencionEntrevistaNotFoundException($id);
            }

            if (! $this->access->puedeAcceder($actor->id, (int) $entrevista->sucursal_id)) {
                throw new BranchAccessDeniedException;
            }

            AtencionStateTransitionRule::assertPuedeEditar($entrevista->estado);

            $before = AtencionSnapshot::entrevista($entrevista);

            $fecha = array_key_exists('fecha', $data)
                ? (string) $data['fecha']
                : $entrevista->fecha->toDateString();
            $notas = array_key_exists('notas_admin', $data)
                ? $this->nullableString($data['notas_admin'])
                : $entrevista->notas_admin;

            AtencionFechasRule::assertEntrevista($fecha);
            NotasAdministrativasRule::assertValidas($notas);

            $entrevista->fill([
                'fecha' => $fecha,
                'notas_admin' => $notas,
            ])->save();
            $entrevista->refresh();

            $after = AtencionSnapshot::entrevista($entrevista);
            $changed = $this->changedFields($before, $after);

            if ($changed !== []) {
                event(new AtencionUpdated(
                    tipo: TipoAtencion::ENTREVISTA,
                    atencionId: (int) $entrevista->id,
                    before: $before,
                    after: $after,
                    changedFields: $changed,
                    actorId: $actor->id,
                    ip: $ip,
                ));
            }

            return $entrevista;
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

    private function nullableString(mixed $value): ?string
    {
        $normalized = trim((string) $value);

        return $normalized === '' ? null : $normalized;
    }
}
