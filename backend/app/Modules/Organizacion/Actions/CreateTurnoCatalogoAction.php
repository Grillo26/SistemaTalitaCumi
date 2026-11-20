<?php

namespace App\Modules\Organizacion\Actions;

use App\Modules\Organizacion\Events\TurnoCatalogoCreated;
use App\Modules\Organizacion\Models\TurnoCatalogo;
use App\Modules\Organizacion\Rules\SpaceInitialStatusRule;
use App\Modules\Organizacion\Rules\SpaceNameNormalizationRule;
use App\Modules\Organizacion\Support\SpaceSnapshot;
use App\Modules\Organizacion\Support\SpaceUniquenessGuard;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;

/**
 * Crear turno operativo (RF-003).
 */
final class CreateTurnoCatalogoAction
{
    public function __construct(private readonly SpaceUniquenessGuard $uniqueness) {}

    /**
     * @param  array<string, mixed>  $attributes
     */
    public function __invoke(array $attributes, ?int $actorId): TurnoCatalogo
    {
        return DB::transaction(function () use ($attributes, $actorId): TurnoCatalogo {
            $nombre = SpaceNameNormalizationRule::normalize((string) $attributes['nombre']);
            $this->uniqueness->assertTurnoNameAvailable($nombre);

            try {
                $turno = TurnoCatalogo::query()->create([
                    'nombre' => $nombre,
                    'orden' => (int) ($attributes['orden'] ?? 0),
                    'activo' => SpaceInitialStatusRule::resolve($attributes['activo'] ?? null),
                    'version' => 1,
                ]);
            } catch (QueryException $exception) {
                $this->uniqueness->translateUniqueViolation($exception);

                throw $exception;
            }

            event(new TurnoCatalogoCreated(
                turnoCatalogoId: $turno->id,
                snapshot: SpaceSnapshot::fromTurno($turno),
                actorId: $actorId,
            ));

            return $turno->refresh();
        });
    }
}
