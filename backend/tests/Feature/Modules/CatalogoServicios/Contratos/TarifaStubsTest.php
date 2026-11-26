<?php

namespace Tests\Feature\Modules\CatalogoServicios\Contratos;

use App\Modules\CatalogoServicios\Contracts\CatalogoServiciosExporterContract;
use App\Modules\CatalogoServicios\Contracts\TarifaUsageContract;
use LogicException;
use Tests\Feature\Modules\CatalogoServicios\CatalogoServiciosTestCase;

final class TarifaStubsTest extends CatalogoServiciosTestCase
{
    public function test_tariff_usage_stub_does_not_silently_return_false(): void
    {
        $contract = $this->app->make(TarifaUsageContract::class);

        $this->expectException(LogicException::class);
        $contract->hasInscripcionesOrPagos(1);
    }

    public function test_exporter_stubs_are_pending_infrastructure(): void
    {
        $contract = $this->app->make(CatalogoServiciosExporterContract::class);

        $this->expectException(LogicException::class);
        $contract->exportarTarifas([]);
    }

    public function test_category_exporter_stub_is_pending_infrastructure(): void
    {
        $contract = $this->app->make(CatalogoServiciosExporterContract::class);

        $this->expectException(LogicException::class);
        $contract->exportarCategorias([]);
    }
}
