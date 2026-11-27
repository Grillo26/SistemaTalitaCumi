<?php

namespace App\Modules\InscripcionesAtenciones\Actions;

use App\Models\User;
use App\Modules\InscripcionesAtenciones\Events\PaqueteUpdated;
use App\Modules\InscripcionesAtenciones\Exceptions\PaqueteNotFoundException;
use App\Modules\InscripcionesAtenciones\Models\PaqueteSesiones;
use App\Modules\InscripcionesAtenciones\Rules\DiasHoraRule;
use App\Modules\InscripcionesAtenciones\Rules\PaqueteObservacionesRule;
use App\Modules\InscripcionesAtenciones\Rules\PaqueteStateTransitionRule;
use App\Modules\InscripcionesAtenciones\Support\PaqueteSnapshot;
use App\Modules\InscripcionesAtenciones\Support\ProfesionalPaqueteGuard;
use App\Modules\Organizacion\Contracts\UserBranchAccessContract;
use App\Modules\Organizacion\Exceptions\BranchAccessDeniedException;
use Illuminate\Support\Facades\DB;

/**
 * [Paquetes Sesiones Individuales] RF-012.
 *
 * Solo profesional, días, hora y observaciones. No cambio de tarifa,
 * categoría, servicio, cantidad, duración ni período.
 */
final class UpdatePaqueteAction
{
    public function __construct(
        private readonly UserBranchAccessContract $access,
        private readonly ProfesionalPaqueteGuard $profesionales,
    ) {}

    /**
     * @param  array<string, mixed>  $data
     */
    public function __invoke(int $id, array $data, User $actor, ?string $ip = null): PaqueteSesiones
    {
        return DB::transaction(function () use ($id, $data, $actor, $ip): PaqueteSesiones {
            $paquete = PaqueteSesiones::query()->lockForUpdate()->find($id);

            if ($paquete === null) {
                throw new PaqueteNotFoundException($id);
            }

            if (! $this->access->puedeAcceder($actor->id, (int) $paquete->sucursal_id)) {
                throw new BranchAccessDeniedException;
            }

            PaqueteStateTransitionRule::assertPuedeEditar($paquete->estado);

            $before = PaqueteSnapshot::from($paquete);

            $profesionalId = array_key_exists('profesional_id', $data)
                ? ($data['profesional_id'] === null || $data['profesional_id'] === ''
                    ? null
                    : (int) $data['profesional_id'])
                : ($paquete->profesional_id === null ? null : (int) $paquete->profesional_id);

            $this->profesionales->assertDisponible($profesionalId, (int) $paquete->sucursal_id);

            $dias = DiasHoraRule::assertDiasValidos((array) ($data['dias_semana'] ?? $paquete->dias_semana));
            $hora = DiasHoraRule::assertHoraValida(
                (string) ($data['hora'] ?? PaqueteSnapshot::horaCorta((string) $paquete->hora)),
            );
            $observaciones = array_key_exists('observaciones', $data)
                ? $data['observaciones']
                : $paquete->observaciones;

            PaqueteObservacionesRule::assertValidas(
                is_string($observaciones) ? $observaciones : null,
            );

            $paquete->fill([
                'profesional_id' => $profesionalId,
                'dias_semana' => $dias,
                'hora' => $hora,
                'observaciones' => is_string($observaciones) && $observaciones !== ''
                    ? $observaciones
                    : null,
            ])->save();

            $paquete->refresh();
            $after = PaqueteSnapshot::from($paquete);
            $changed = [];

            foreach ($after as $key => $value) {
                if (($before[$key] ?? null) !== $value) {
                    $changed[] = $key;
                }
            }

            if ($changed !== []) {
                event(new PaqueteUpdated(
                    paqueteId: (int) $paquete->id,
                    before: $before,
                    after: $after,
                    changedFields: $changed,
                    actorId: $actor->id,
                    ip: $ip,
                ));
            }

            return $paquete;
        });
    }
}
