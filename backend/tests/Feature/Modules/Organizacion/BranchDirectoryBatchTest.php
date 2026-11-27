<?php

namespace Tests\Feature\Modules\Organizacion;

use App\Modules\Organizacion\Contracts\BranchDirectoryContract;
use App\Modules\Organizacion\Models\Branch;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

final class BranchDirectoryBatchTest extends TestCase
{
    use RefreshDatabase;

    public function test_returns_branch_summaries_by_id(): void
    {
        $branch = Branch::factory()->create([
            'codigo' => 'SUC-100',
            'nombre' => 'Sucursal Cien',
            'activo' => true,
        ]);
        $other = Branch::factory()->create();
        $directory = app(BranchDirectoryContract::class);

        $resumenes = $directory->resumenes([$branch->id, 999999]);

        $this->assertArrayHasKey($branch->id, $resumenes);
        $this->assertSame('SUC-100', $resumenes[$branch->id]->codigo);
        $this->assertSame('Sucursal Cien', $resumenes[$branch->id]->nombre);
        $this->assertArrayNotHasKey(999999, $resumenes);
        $this->assertArrayNotHasKey($other->id, $resumenes);
        $this->assertSame([], $directory->resumenes([]));
    }
}
