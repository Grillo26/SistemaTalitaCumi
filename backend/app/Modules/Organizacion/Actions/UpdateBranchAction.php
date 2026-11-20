<?php

namespace App\Modules\Organizacion\Actions;

use App\Modules\Organizacion\Contracts\UserBranchAccessContract;
use App\Modules\Organizacion\Events\BranchUpdated;
use App\Modules\Organizacion\Exceptions\BranchAccessDeniedException;
use App\Modules\Organizacion\Exceptions\BranchConcurrencyException;
use App\Modules\Organizacion\Exceptions\BranchNotFoundException;
use App\Modules\Organizacion\Models\Branch;
use App\Modules\Organizacion\Rules\BranchIdentityNormalizationRule;
use App\Modules\Organizacion\Support\BranchSnapshot;
use App\Modules\Organizacion\Support\BranchUniquenessGuard;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;

/**
 * Editar datos de sucursal (RF-006, RF-002, RF-003, RF-013 / RN-001).
 *
 * Los permisos por campo se validan en UpdateBranchRequest. Esta Action
 * valida el alcance por sucursal y la concurrencia optimista.
 */
final class UpdateBranchAction
{
    public function __construct(
        private readonly UserBranchAccessContract $access,
        private readonly BranchUniquenessGuard $uniqueness,
    ) {}

    /**
     * @param  array<string, mixed>  $attributes
     */
    public function __invoke(
        int $branchId,
        array $attributes,
        int $expectedVersion,
        ?int $actorId,
        bool $hasGlobalScope,
    ): Branch {
        if (! $hasGlobalScope && ($actorId === null || ! $this->access->puedeAcceder($actorId, $branchId))) {
            throw new BranchAccessDeniedException;
        }

        return DB::transaction(function () use ($branchId, $attributes, $expectedVersion, $actorId): Branch {
            $branch = Branch::query()
                ->whereKey($branchId)
                ->lockForUpdate()
                ->first();

            if ($branch === null) {
                throw new BranchNotFoundException($branchId);
            }

            if ($branch->version !== $expectedVersion) {
                throw new BranchConcurrencyException;
            }

            $changes = $this->resolveChanges($branch, $attributes);

            if ($changes === []) {
                return $branch;
            }

            $before = BranchSnapshot::from($branch);

            $branch->fill($changes);
            $branch->version = $branch->version + 1;

            try {
                $branch->save();
            } catch (QueryException $exception) {
                $this->uniqueness->translateUniqueViolation($exception);

                throw $exception;
            }

            $branch->refresh();

            event(new BranchUpdated(
                branchId: $branch->id,
                before: $before,
                after: BranchSnapshot::from($branch),
                changedFields: array_keys($changes),
                actorId: $actorId,
            ));

            return $branch;
        });
    }

    /**
     * @param  array<string, mixed>  $attributes
     * @return array<string, mixed>
     */
    private function resolveChanges(Branch $branch, array $attributes): array
    {
        $changes = [];

        if (array_key_exists('codigo', $attributes)) {
            $codigo = BranchIdentityNormalizationRule::normalizeCode((string) $attributes['codigo']);

            if ($codigo !== $branch->codigo) {
                $this->uniqueness->assertAvailable('codigo', $codigo, $branch->id);
                $changes['codigo'] = $codigo;
            }
        }

        if (array_key_exists('nombre', $attributes)) {
            $nombre = BranchIdentityNormalizationRule::normalizeName((string) $attributes['nombre']);

            if ($nombre !== $branch->nombre) {
                $this->uniqueness->assertAvailable('nombre', $nombre, $branch->id);
                $changes['nombre'] = $nombre;
            }
        }

        foreach (['direccion', 'telefono', 'email'] as $field) {
            if (! array_key_exists($field, $attributes)) {
                continue;
            }

            $value = $attributes[$field] === null ? null : trim((string) $attributes[$field]);

            if ($value !== $branch->{$field}) {
                $changes[$field] = $value;
            }
        }

        return $changes;
    }
}
