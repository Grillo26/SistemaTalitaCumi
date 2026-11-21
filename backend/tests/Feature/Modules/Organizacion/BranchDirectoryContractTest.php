<?php

namespace Tests\Feature\Modules\Organizacion;

use App\Modules\Organizacion\Contracts\BranchDirectoryContract;
use App\Modules\Organizacion\Exceptions\BranchNotFoundException;
use App\Modules\Organizacion\Exceptions\InactiveBranchException;
use App\Modules\Organizacion\Models\Branch;
use App\Modules\Organizacion\Rules\BranchOperationalContextRule;

/**
 * Contrato proveedor para módulos consumidores (FP-04 / RN-003).
 */
final class BranchDirectoryContractTest extends OrganizacionTestCase
{
    public function test_directory_reports_existence_and_active_state(): void
    {
        $active = Branch::factory()->create(['activo' => true]);
        $inactive = Branch::factory()->create(['activo' => false]);

        $directory = app(BranchDirectoryContract::class);

        $this->assertTrue($directory->existe($active->id));
        $this->assertTrue($directory->existe($inactive->id));
        $this->assertFalse($directory->existe(999999));

        $this->assertTrue($directory->estaActiva($active->id));
        $this->assertFalse($directory->estaActiva($inactive->id));
        $this->assertFalse($directory->estaActiva(999999));
    }

    public function test_directory_accepts_active_branch(): void
    {
        $branch = Branch::factory()->create(['activo' => true]);

        app(BranchDirectoryContract::class)->asegurarActiva($branch->id);

        $this->assertTrue(true);
    }

    public function test_directory_rejects_inactive_branch(): void
    {
        $branch = Branch::factory()->create(['activo' => false]);

        $this->expectException(InactiveBranchException::class);

        app(BranchDirectoryContract::class)->asegurarActiva($branch->id);
    }

    public function test_directory_rejects_missing_branch(): void
    {
        $this->expectException(BranchNotFoundException::class);

        app(BranchDirectoryContract::class)->asegurarActiva(999999);
    }

    public function test_application_uses_single_currency_and_timezone(): void
    {
        $this->assertTrue(BranchOperationalContextRule::matches(
            (string) config('app.currency'),
            (string) config('app.timezone'),
        ));
    }
}
