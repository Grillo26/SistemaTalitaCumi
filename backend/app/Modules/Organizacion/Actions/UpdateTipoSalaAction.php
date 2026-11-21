<?php

namespace App\Modules\Organizacion\Actions;

use App\Modules\Organizacion\Events\TipoSalaUpdated;
use App\Modules\Organizacion\Exceptions\SpaceConcurrencyException;
use App\Modules\Organizacion\Exceptions\TipoSalaNotFoundException;
use App\Modules\Organizacion\Models\TipoSala;
use App\Modules\Organizacion\Rules\SpaceNameNormalizationRule;
use App\Modules\Organizacion\Support\SpaceSnapshot;
use App\Modules\Organizacion\Support\SpaceUniquenessGuard;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;

/**
 * Editar tipo de sala (RF-002).
 */
final class UpdateTipoSalaAction
{
    public function __construct(private readonly SpaceUniquenessGuard $uniqueness) {}

    /**
     * @param  array<string, mixed>  $attributes
     */
    public function __invoke(int $id, array $attributes, int $expectedVersion, ?int $actorId): TipoSala
    {
        return DB::transaction(function () use ($id, $attributes, $expectedVersion, $actorId): TipoSala {
            $tipoSala = TipoSala::query()->whereKey($id)->lockForUpdate()->first();

            if ($tipoSala === null) {
                throw new TipoSalaNotFoundException($id);
            }

            if ($tipoSala->version !== $expectedVersion) {
                throw new SpaceConcurrencyException('tipo de sala');
            }

            $changes = [];

            if (array_key_exists('nombre', $attributes)) {
                $nombre = SpaceNameNormalizationRule::normalize((string) $attributes['nombre']);

                if ($nombre !== $tipoSala->nombre) {
                    $this->uniqueness->assertTipoSalaNameAvailable($nombre, $tipoSala->id);
                    $changes['nombre'] = $nombre;
                }
            }

            if ($changes === []) {
                return $tipoSala;
            }

            $before = SpaceSnapshot::fromTipoSala($tipoSala);
            $tipoSala->fill($changes);
            $tipoSala->version = $tipoSala->version + 1;

            try {
                $tipoSala->save();
            } catch (QueryException $exception) {
                $this->uniqueness->translateUniqueViolation($exception);

                throw $exception;
            }

            $tipoSala->refresh();

            event(new TipoSalaUpdated(
                tipoSalaId: $tipoSala->id,
                before: $before,
                after: SpaceSnapshot::fromTipoSala($tipoSala),
                changedFields: array_keys($changes),
                actorId: $actorId,
            ));

            return $tipoSala;
        });
    }
}
