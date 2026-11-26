<?php

namespace Tests\Feature\Modules\InscripcionesAtenciones;

use App\Modules\InscripcionesAtenciones\Support\VigenciaSettings;
use App\Modules\Organizacion\Models\Parametro;
use Illuminate\Support\Facades\Event;

abstract class VigenciasTestCase extends PaquetesSesionesTestCase
{
    protected function seedVentana(int $dias = 7): void
    {
        $fila = Parametro::query()
            ->where('clave', VigenciaSettings::VENTANA_ALERTA_DIAS)
            ->firstOrFail();

        $fila->update(['valor' => (string) $dias]);
    }

    protected function crearPaqueteVencido(array $escenario): array
    {
        return $this->crearPaquete($escenario, [
            'fecha_inicio' => '2026-11-01',
            'fecha_fin' => '2026-11-20',
        ])->assertCreated()->json('data');
    }

    protected function crearPaquetePorVencer(array $escenario): array
    {
        return $this->crearPaquete($escenario, [
            'fecha_inicio' => '2026-11-01',
            'fecha_fin' => '2026-11-28',
        ])->assertCreated()->json('data');
    }

    protected function crearInscripcionVencida(array $escenario): array
    {
        return $this->crearInscripcion($escenario, [
            'fecha_inicio' => '2026-11-01',
            'fecha_fin' => '2026-11-20',
        ])->assertCreated()->json('data');
    }

    protected function crearInscripcionPorVencer(array $escenario): array
    {
        return $this->crearInscripcion($escenario, [
            'fecha_inicio' => '2026-11-01',
            'fecha_fin' => '2026-11-28',
        ])->assertCreated()->json('data');
    }

    protected function flushEvents(): void
    {
        Event::fake();
    }
}
