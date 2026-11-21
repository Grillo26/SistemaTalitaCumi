<?php

namespace App\Modules\Pacientes\Queries\Tutores;

use App\Models\User;
use App\Modules\Organizacion\Contracts\UserBranchAccessContract;
use App\Modules\Organizacion\Models\Branch;
use App\Modules\Pacientes\Models\Parentesco;
use App\Modules\Pacientes\Support\TutorAbilities;

/**
 * Opciones para formularios y filtros de tutores (04.02/RF-007).
 */
final class GetTutorFormOptionsQuery
{
    public function __construct(private readonly UserBranchAccessContract $access) {}

    /**
     * @return array<string, mixed>
     */
    public function __invoke(User $actor): array
    {
        $branchIds = $this->access->accesibles($actor->id);

        $sucursales = $branchIds === []
            ? []
            : Branch::query()
                ->whereIn('id', $branchIds)
                ->where('activo', true)
                ->orderBy('nombre')
                ->get(['id', 'codigo', 'nombre'])
                ->map(static fn (Branch $branch): array => [
                    'id' => (int) $branch->id,
                    'codigo' => $branch->codigo,
                    'nombre' => $branch->nombre,
                ])
                ->values()
                ->all();

        $parentescos = Parentesco::query()
            ->activo()
            ->ordenado()
            ->get(['id', 'nombre'])
            ->map(static fn (Parentesco $parentesco): array => [
                'id' => (int) $parentesco->id,
                'nombre' => $parentesco->nombre,
            ])
            ->values()
            ->all();

        return [
            'parentescos' => $parentescos,
            'sucursales' => $sucursales,
            'estados' => [
                ['value' => 'activo', 'label' => 'Activo'],
                ['value' => 'inactivo', 'label' => 'Inactivo'],
            ],
            'can' => TutorAbilities::collection($actor),
        ];
    }
}
