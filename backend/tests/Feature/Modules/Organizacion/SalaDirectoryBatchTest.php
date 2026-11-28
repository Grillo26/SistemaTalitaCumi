<?php

namespace Tests\Feature\Modules\Organizacion;

use App\Modules\Organizacion\Contracts\SalaDirectoryContract;
use App\Modules\Organizacion\Models\Branch;
use App\Modules\Organizacion\Models\Sala;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

final class SalaDirectoryBatchTest extends TestCase
{
    use RefreshDatabase;

    public function test_returns_names_and_active_rooms_by_branch(): void
    {
        $branch = Branch::factory()->create();
        $otherBranch = Branch::factory()->create();
        $active = Sala::factory()->create([
            'sucursal_id' => $branch->id,
            'nombre' => 'Sala Activa',
            'activo' => true,
        ]);
        $inactive = Sala::factory()->create([
            'sucursal_id' => $branch->id,
            'nombre' => 'Sala Inactiva',
            'activo' => false,
        ]);
        $other = Sala::factory()->create([
            'sucursal_id' => $otherBranch->id,
            'nombre' => 'Sala Otra',
            'activo' => true,
        ]);

        $directory = app(SalaDirectoryContract::class);

        $names = $directory->nombres([$active->id, $inactive->id, $other->id]);
        $byBranch = $directory->nombresPorSucursal($branch->id);

        $this->assertSame('Sala Activa', $names[$active->id]);
        $this->assertSame('Sala Inactiva', $names[$inactive->id]);
        $this->assertSame([$active->id => 'Sala Activa'], $byBranch);
    }
}
