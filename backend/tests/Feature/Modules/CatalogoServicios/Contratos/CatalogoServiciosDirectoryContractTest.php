<?php

namespace Tests\Feature\Modules\CatalogoServicios\Contratos;

use App\Modules\CatalogoServicios\Contracts\CatalogoServiciosDirectoryContract;
use App\Modules\CatalogoServicios\Exceptions\ServicioInactivoException;
use App\Modules\CatalogoServicios\Models\Area;
use App\Modules\CatalogoServicios\Models\Servicio;
use Tests\Feature\Modules\CatalogoServicios\CatalogoServiciosTestCase;

final class CatalogoServiciosDirectoryContractTest extends CatalogoServiciosTestCase
{
    public function test_directory_returns_areas_services_and_colors(): void
    {
        $area = $this->area('Área Directorio');
        $tipo = $this->tipoServicio('Tipo Directorio');
        $servicio = $this->servicio($area, $tipo, 'Servicio Directorio');

        $contract = $this->app->make(CatalogoServiciosDirectoryContract::class);

        $areas = collect($contract->areas())->keyBy('id');
        $this->assertTrue($areas->has($area->id));
        $this->assertSame('#671C61', $areas[$area->id]->color);

        $servicios = collect($contract->servicios($area->id))->keyBy('id');
        $this->assertTrue($servicios->has($servicio->id));
        $this->assertSame('Área Directorio', $servicios[$servicio->id]->areaNombre);

        $this->assertSame($servicio->id, $contract->servicio($servicio->id)?->id);
    }

    public function test_directory_rejects_inactive_service(): void
    {
        $area = $this->area('Área Directo Inactiva');
        $tipo = $this->tipoServicio('Tipo Directo Inactiva');
        $servicio = $this->servicio($area, $tipo, 'Servicio Inactivo Directorio');
        $servicio->update(['activo' => false]);

        $contract = $this->app->make(CatalogoServiciosDirectoryContract::class);

        $this->expectException(ServicioInactivoException::class);
        $contract->asegurarServicioActivo($servicio->id);
    }

    public function test_cache_is_invalidated_after_area_change(): void
    {
        $area = $this->area('Área Cache');
        $contract = $this->app->make(CatalogoServiciosDirectoryContract::class);

        $this->assertSame('Área Cache', collect($contract->areas())->firstWhere('id', $area->id)?->nombre);

        $area->update(['nombre' => 'Área Cache Renombrada']);
        event(new \App\Modules\CatalogoServicios\Events\AreaUpdated(
            areaId: $area->id,
            before: [],
            after: [],
            actorId: null,
            ip: null,
        ));

        $this->assertSame(
            'Área Cache Renombrada',
            collect($contract->areas())->firstWhere('id', $area->id)?->nombre,
        );
    }
}
