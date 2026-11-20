<?php

namespace Tests\Feature\Modules\Organizacion\SalasAmbientes;

use App\Modules\Organizacion\Contracts\SalaDirectoryContract;
use App\Modules\Organizacion\Exceptions\EspacioInactivoException;
use App\Modules\Organizacion\Exceptions\EspacioNotFoundException;
use App\Modules\Organizacion\Exceptions\EspacioSucursalIncorrectaException;
use App\Modules\Organizacion\Models\Sala;

final class SalaDirectoryContractTest extends SalasAmbientesTestCase
{
    public function test_contract_reports_existence_active_state_and_branch(): void
    {
        $branch = $this->branch();
        $active = Sala::factory()->create(['sucursal_id' => $branch->id, 'activo' => true]);
        $inactive = Sala::factory()->create(['activo' => false]);

        $contract = app(SalaDirectoryContract::class);

        $this->assertTrue($contract->existe($active->id));
        $this->assertFalse($contract->existe(999999));
        $this->assertTrue($contract->estaActiva($active->id));
        $this->assertFalse($contract->estaActiva($inactive->id));
        $this->assertTrue($contract->perteneceASucursal($active->id, $branch->id));
        $this->assertFalse($contract->perteneceASucursal($active->id, 999999));
    }

    public function test_contract_accepts_active_sala_in_correct_branch(): void
    {
        $branch = $this->branch();
        $sala = Sala::factory()->create(['sucursal_id' => $branch->id, 'activo' => true]);

        app(SalaDirectoryContract::class)->asegurarActivaEnSucursal($sala->id, $branch->id);

        $this->assertTrue(true);
    }

    public function test_contract_rejects_inactive_sala(): void
    {
        $branch = $this->branch();
        $sala = Sala::factory()->create(['sucursal_id' => $branch->id, 'activo' => false]);

        $this->expectException(EspacioInactivoException::class);

        app(SalaDirectoryContract::class)->asegurarActivaEnSucursal($sala->id, $branch->id);
    }

    public function test_contract_rejects_wrong_branch(): void
    {
        $sala = Sala::factory()->create(['activo' => true]);

        $this->expectException(EspacioSucursalIncorrectaException::class);

        app(SalaDirectoryContract::class)->asegurarActivaEnSucursal($sala->id, 999999);
    }

    public function test_contract_rejects_missing_sala(): void
    {
        $this->expectException(EspacioNotFoundException::class);

        app(SalaDirectoryContract::class)->asegurarActivaEnSucursal(999999, 1);
    }
}
