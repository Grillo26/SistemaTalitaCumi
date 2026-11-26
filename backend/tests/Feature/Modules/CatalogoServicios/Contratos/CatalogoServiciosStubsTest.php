<?php

namespace Tests\Feature\Modules\CatalogoServicios\Contratos;

use App\Modules\CatalogoServicios\Contracts\CatalogoServiciosExporterContract;
use App\Modules\CatalogoServicios\Contracts\ServicioUsageContract;
use LogicException;
use Tests\Feature\Modules\CatalogoServicios\CatalogoServiciosTestCase;

final class CatalogoServiciosStubsTest extends CatalogoServiciosTestCase
{
    public function test_usage_stub_does_not_silently_return_false(): void
    {
        $contract = $this->app->make(ServicioUsageContract::class);

        $this->expectException(LogicException::class);
        $contract->hasTarifas(1);
    }

    public function test_exporter_stub_is_pending_infrastructure(): void
    {
        $contract = $this->app->make(CatalogoServiciosExporterContract::class);

        $this->expectException(LogicException::class);
        $contract->exportarAreas([]);
    }
}
