<?php

namespace App\Modules\Organizacion\Actions;

use App\Modules\Organizacion\Events\BranchCreated;
use App\Modules\Organizacion\Models\Branch;
use App\Modules\Organizacion\Rules\BranchIdentityNormalizationRule;
use App\Modules\Organizacion\Rules\BranchInitialStatusRule;
use App\Modules\Organizacion\Support\BranchSnapshot;
use App\Modules\Organizacion\Support\BranchUniquenessGuard;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;

/**
 * Crear sucursal (RF-001, RF-002, RF-003, RF-013 / RN-001, RN-002).
 */
final class CreateBranchAction
{
    public function __construct(private readonly BranchUniquenessGuard $uniqueness) {}

    /**
     * @param  array<string, mixed>  $attributes
     */
    public function __invoke(array $attributes, ?int $actorId): Branch
    {
        return DB::transaction(function () use ($attributes, $actorId): Branch {
            $codigo = BranchIdentityNormalizationRule::normalizeCode((string) $attributes['codigo']);
            $nombre = BranchIdentityNormalizationRule::normalizeName((string) $attributes['nombre']);

            $this->uniqueness->assertAvailable('codigo', $codigo);
            $this->uniqueness->assertAvailable('nombre', $nombre);

            try {
                $branch = Branch::query()->create([
                    'codigo' => $codigo,
                    'nombre' => $nombre,
                    'direccion' => $attributes['direccion'] ?? null,
                    'telefono' => $attributes['telefono'] ?? null,
                    'email' => $attributes['email'] ?? null,
                    'activo' => BranchInitialStatusRule::resolve($attributes['activo'] ?? null),
                    'version' => 1,
                ]);
            } catch (QueryException $exception) {
                $this->uniqueness->translateUniqueViolation($exception);

                throw $exception;
            }

            event(new BranchCreated(
                branchId: $branch->id,
                snapshot: BranchSnapshot::from($branch),
                actorId: $actorId,
            ));

            return $branch->refresh();
        });
    }
}
