<?php

namespace Tests\Feature\Modules\Organizacion\SalasAmbientes;

use App\Modules\Organizacion\Contracts\AmbienteDirectoryContract;
use App\Modules\Organizacion\Exceptions\EspacioInactivoException;
use App\Modules\Organizacion\Exceptions\EspacioNotFoundException;
use App\Modules\Organizacion\Exceptions\EspacioSucursalIncorrectaException;
use App\Modules\Organizacion\Models\Ambiente;

final class AmbienteDirectoryContractTest extends SalasAmbientesTestCase
{
    public function test_contract_reports_existence_active_state_and_branch(): void
    {
        $branch = $this->branch();
        $active = Ambiente::factory()->create(['sucursal_id' => $branch->id, 'activo' => true]);
        $inactive = Ambiente::factory()->create(['activo' => false]);

        $contract = app(AmbienteDirectoryContract::class);

        $this->assertTrue($contract->existe($active->id));
        $this->assertFalse($contract->existe(999999));
        $this->assertTrue($contract->estaActiva($active->id));
        $this->assertFalse($contract->estaActiva($inactive->id));
        $this->assertTrue($contract->perteneceASucursal($active->id, $branch->id));
        $this->assertFalse($contract->perteneceASucursal($active->id, 999999));
    }

    public function test_contract_accepts_active_ambiente_in_correct_branch(): void
    {
        $branch = $this->branch();
        $ambiente = Ambiente::factory()->create(['sucursal_id' => $branch->id, 'activo' => true]);

        app(AmbienteDirectoryContract::class)->asegurarActivaEnSucursal($ambiente->id, $branch->id);

        $this->assertTrue(true);
    }

    public function test_contract_rejects_inactive_ambiente(): void
    {
        $branch = $this->branch();
        $ambiente = Ambiente::factory()->create(['sucursal_id' => $branch->id, 'activo' => false]);

        $this->expectException(EspacioInactivoException::class);

        app(AmbienteDirectoryContract::class)->asegurarActivaEnSucursal($ambiente->id, $branch->id);
    }

    public function test_contract_rejects_wrong_branch(): void
    {
        $ambiente = Ambiente::factory()->create(['activo' => true]);

        $this->expectException(EspacioSucursalIncorrectaException::class);

        app(AmbienteDirectoryContract::class)->asegurarActivaEnSucursal($ambiente->id, 999999);
    }

    public function test_contract_rejects_missing_ambiente(): void
    {
        $this->expectException(EspacioNotFoundException::class);

        app(AmbienteDirectoryContract::class)->asegurarActivaEnSucursal(999999, 1);
    }
}
