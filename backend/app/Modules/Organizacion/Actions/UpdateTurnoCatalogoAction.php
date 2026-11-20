<?php

namespace App\Modules\Organizacion\Actions;

use App\Modules\Organizacion\Events\TurnoCatalogoUpdated;
use App\Modules\Organizacion\Exceptions\SpaceConcurrencyException;
use App\Modules\Organizacion\Exceptions\TurnoCatalogoNotFoundException;
use App\Modules\Organizacion\Models\TurnoCatalogo;
use App\Modules\Organizacion\Rules\SpaceNameNormalizationRule;
use App\Modules\Organizacion\Support\SpaceSnapshot;
use App\Modules\Organizacion\Support\SpaceUniquenessGuard;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;

/**
 * Editar turno operativo (RF-003).
 */
final class UpdateTurnoCatalogoAction
{
    public function __construct(private readonly SpaceUniquenessGuard $uniqueness) {}

    /**
     * @param  array<string, mixed>  $attributes
     */
    public function __invoke(int $id, array $attributes, int $expectedVersion, ?int $actorId): TurnoCatalogo
    {
        return DB::transaction(function () use ($id, $attributes, $expectedVersion, $actorId): TurnoCatalogo {
            $turno = TurnoCatalogo::query()->whereKey($id)->lockForUpdate()->first();

            if ($turno === null) {
                throw new TurnoCatalogoNotFoundException($id);
            }

            if ($turno->version !== $expectedVersion) {
                throw new SpaceConcurrencyException('turno');
            }

            $changes = [];

            if (array_key_exists('nombre', $attributes)) {
                $nombre = SpaceNameNormalizationRule::normalize((string) $attributes['nombre']);

                if ($nombre !== $turno->nombre) {
                    $this->uniqueness->assertTurnoNameAvailable($nombre, $turno->id);
                    $changes['nombre'] = $nombre;
                }
            }

            if (array_key_exists('orden', $attributes)) {
                $orden = (int) $attributes['orden'];

                if ($orden !== $turno->orden) {
                    $changes['orden'] = $orden;
                }
            }

            if ($changes === []) {
                return $turno;
            }

            $before = SpaceSnapshot::fromTurno($turno);
            $turno->fill($changes);
            $turno->version = $turno->version + 1;

            try {
                $turno->save();
            } catch (QueryException $exception) {
                $this->uniqueness->translateUniqueViolation($exception);

                throw $exception;
            }

            $turno->refresh();

            event(new TurnoCatalogoUpdated(
                turnoCatalogoId: $turno->id,
                before: $before,
                after: SpaceSnapshot::fromTurno($turno),
                changedFields: array_keys($changes),
                actorId: $actorId,
            ));

            return $turno;
        });
    }
}
