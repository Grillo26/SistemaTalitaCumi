<?php

namespace App\Modules\Profesionales\Actions\Directorio;

use App\Models\User;
use App\Modules\Organizacion\Contracts\BranchDirectoryContract;
use App\Modules\Organizacion\Contracts\UserBranchAccessContract;
use App\Modules\Profesionales\Actions\Directorio\Concerns\AssertsBranchScope;
use App\Modules\Profesionales\Events\Directorio\ProfesionalSucursalChanged;
use App\Modules\Profesionales\Models\Profesional;
use App\Modules\Profesionales\Models\ProfesionalSucursal;
use App\Modules\Profesionales\Rules\ProfesionalSucursalUniquenessRule;
use App\Modules\Profesionales\Support\ProfesionalSucursalSnapshot;
use Illuminate\Support\Facades\DB;

/**
 * Asignar una sucursal de trabajo al profesional (03/RF-E06, 03/RF-E07 / 03/RN-07).
 */
final class AssignProfesionalSucursalAction
{
    use AssertsBranchScope;

    public function __construct(
        private readonly UserBranchAccessContract $access,
        private readonly BranchDirectoryContract $branches,
        private readonly ProfesionalSucursalUniquenessRule $uniqueness,
    ) {}

    public function __invoke(
        Profesional $profesional,
        int $sucursalId,
        User $actor,
        ?string $ip = null,
    ): ProfesionalSucursal {
        $this->assertBranchInScope($this->access, $actor, $sucursalId);

        return DB::transaction(function () use ($profesional, $sucursalId, $actor, $ip): ProfesionalSucursal {
            $this->branches->asegurarActiva($sucursalId);

            $existing = ProfesionalSucursal::query()
                ->where('profesional_id', $profesional->id)
                ->where('sucursal_id', $sucursalId)
                ->lockForUpdate()
                ->first();

            $this->uniqueness->assertCanAssign(
                activeRelationExists: $existing?->activo === true,
                inactiveRelationExists: $existing !== null && ! $existing->activo,
            );

            $relation = ProfesionalSucursal::query()->create([
                'profesional_id' => $profesional->id,
                'sucursal_id' => $sucursalId,
                'activo' => true,
                'motivo' => null,
                'version' => 1,
            ]);

            $relation->load('sucursal');

            event(new ProfesionalSucursalChanged(
                profesionalId: $profesional->id,
                sucursalId: $sucursalId,
                before: null,
                after: ProfesionalSucursalSnapshot::from($relation),
                action: 'CREAR',
                reason: null,
                actorId: $actor->id,
                ip: $ip,
            ));

            return $relation;
        });
    }
}
