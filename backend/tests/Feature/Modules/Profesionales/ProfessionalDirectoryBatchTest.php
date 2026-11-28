<?php

namespace Tests\Feature\Modules\Profesionales;

use App\Modules\Organizacion\Models\Branch;
use App\Modules\Profesionales\Contracts\ProfessionalDirectoryContract;
use App\Modules\Profesionales\Models\Profesional;
use App\Modules\Profesionales\Models\ProfesionalSucursal;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

final class ProfessionalDirectoryBatchTest extends TestCase
{
    use RefreshDatabase;

    public function test_returns_names_and_filters_by_active_branch_relation(): void
    {
        $branch = Branch::factory()->create();
        $otherBranch = Branch::factory()->create();
        $active = Profesional::factory()->create([
            'nombre_completo' => 'Ana Activa',
            'activo' => true,
        ]);
        $inactive = Profesional::factory()->create([
            'nombre_completo' => 'Bruno Inactivo',
            'activo' => false,
        ]);

        ProfesionalSucursal::factory()->create([
            'profesional_id' => $active->id,
            'sucursal_id' => $branch->id,
            'activo' => true,
        ]);
        ProfesionalSucursal::factory()->create([
            'profesional_id' => $inactive->id,
            'sucursal_id' => $branch->id,
            'activo' => true,
        ]);
        ProfesionalSucursal::factory()->create([
            'profesional_id' => $active->id,
            'sucursal_id' => $otherBranch->id,
            'activo' => true,
        ]);

        $directory = app(ProfessionalDirectoryContract::class);

        $names = $directory->nombres([$active->id, $inactive->id]);
        $byBranch = $directory->nombresPorSucursal($branch->id);

        $this->assertSame('Ana Activa', $names[$active->id]);
        $this->assertSame('Bruno Inactivo', $names[$inactive->id]);
        $this->assertSame([$active->id => 'Ana Activa'], $byBranch);
    }
}
