<?php

namespace App\Modules\Organizacion\Actions;

use App\Modules\Organizacion\Events\TipoSalaCreated;
use App\Modules\Organizacion\Models\TipoSala;
use App\Modules\Organizacion\Rules\SpaceInitialStatusRule;
use App\Modules\Organizacion\Rules\SpaceNameNormalizationRule;
use App\Modules\Organizacion\Support\SpaceSnapshot;
use App\Modules\Organizacion\Support\SpaceUniquenessGuard;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;

/**
 * Crear tipo de sala (RF-001).
 */
final class CreateTipoSalaAction
{
    public function __construct(private readonly SpaceUniquenessGuard $uniqueness) {}

    /**
     * @param  array<string, mixed>  $attributes
     */
    public function __invoke(array $attributes, ?int $actorId): TipoSala
    {
        return DB::transaction(function () use ($attributes, $actorId): TipoSala {
            $nombre = SpaceNameNormalizationRule::normalize((string) $attributes['nombre']);
            $this->uniqueness->assertTipoSalaNameAvailable($nombre);

            try {
                $tipoSala = TipoSala::query()->create([
                    'nombre' => $nombre,
                    'activo' => SpaceInitialStatusRule::resolve($attributes['activo'] ?? null),
                    'version' => 1,
                ]);
            } catch (QueryException $exception) {
                $this->uniqueness->translateUniqueViolation($exception);

                throw $exception;
            }

            event(new TipoSalaCreated(
                tipoSalaId: $tipoSala->id,
                snapshot: SpaceSnapshot::fromTipoSala($tipoSala),
                actorId: $actorId,
            ));

            return $tipoSala->refresh();
        });
    }
}
