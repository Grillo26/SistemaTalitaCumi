<?php

namespace Tests\Feature\Modules\Organizacion\SalasAmbientes;

use App\Models\User;
use App\Modules\Organizacion\Contracts\UserBranchAccessContract;
use App\Modules\Organizacion\Models\Area;
use App\Modules\Organizacion\Models\Branch;
use App\Modules\Organizacion\Models\TipoSala;
use App\Modules\Organizacion\Models\TurnoCatalogo;
use Tests\Feature\Modules\Organizacion\OrganizacionTestCase;
use Tests\Support\FakeUserBranchAccessContract;

abstract class SalasAmbientesTestCase extends OrganizacionTestCase
{
    protected function area(string $name = 'Salas Grupales'): Area
    {
        return Area::query()->where('nombre', $name)->firstOrFail();
    }

    protected function tipoSala(string $name = 'Preescolar'): TipoSala
    {
        return TipoSala::query()->where('nombre', $name)->firstOrFail();
    }

    protected function turno(string $name = 'Mañana'): TurnoCatalogo
    {
        return TurnoCatalogo::query()->where('nombre', $name)->firstOrFail();
    }

    protected function branch(bool $active = true): Branch
    {
        return Branch::factory()->create(['activo' => $active]);
    }

    protected function fakeAccessFor(User $user, array $branchIds): FakeUserBranchAccessContract
    {
        $access = new FakeUserBranchAccessContract;
        $access->accessibleByUser[$user->id] = $branchIds;
        $this->app->instance(UserBranchAccessContract::class, $access);

        return $access;
    }

    /**
     * @param  array<string, mixed>  $overrides
     * @return array<string, mixed>
     */
    protected function salaPayload(Branch $branch, array $overrides = []): array
    {
        return array_merge([
            'sucursal_id' => $branch->id,
            'area_id' => $this->area()->id,
            'tipo_sala_id' => $this->tipoSala()->id,
            'turno_catalogo_id' => $this->turno()->id,
            'nombre' => 'Sala Ositos',
            'observaciones' => 'Sala de prueba',
            'activo' => true,
        ], $overrides);
    }

    /**
     * @param  array<string, mixed>  $overrides
     * @return array<string, mixed>
     */
    protected function ambientePayload(Branch $branch, array $overrides = []): array
    {
        return array_merge([
            'sucursal_id' => $branch->id,
            'nombre' => 'Consultorio 1',
            'uso' => 'terapia_individual',
            'observaciones' => 'Ambiente de prueba',
            'activo' => true,
        ], $overrides);
    }
}
