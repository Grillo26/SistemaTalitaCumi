<?php

namespace Tests\Feature\Modules\CatalogoServicios\Contratos;

use App\Modules\CatalogoServicios\Contracts\CatalogoServiciosExporterContract;
use App\Modules\CatalogoServicios\Contracts\ServicioUsageContract;
use LogicException;
use Tests\Feature\Modules\CatalogoServicios\CatalogoServiciosTestCase;

final class CatalogoServiciosStubsTest extends CatalogoServiciosTestCase
{
    public function test_usage_contract_uses_real_tariffs(): void
    {
        $contract = $this->app->make(ServicioUsageContract::class);

        $this->assertFalse($contract->hasTarifas(999999));
    }

    public function test_usage_contract_keeps_inscriptions_stubbed(): void
    {
        $contract = $this->app->make(ServicioUsageContract::class);

        $this->expectException(LogicException::class);
        $contract->hasInscripciones(1);
    }

    public function test_usage_contract_keeps_turnos_stubbed(): void
    {
        $contract = $this->app->make(ServicioUsageContract::class);

        $this->expectException(LogicException::class);
        $contract->hasTurnos(1);
    }

    public function test_exporter_stub_is_pending_infrastructure(): void
    {
        $contract = $this->app->make(CatalogoServiciosExporterContract::class);

        $this->expectException(LogicException::class);
        $contract->exportarTarifas([]);
    }
}
