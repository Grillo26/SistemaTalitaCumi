<?php

namespace Tests\Feature\Modules\InscripcionesAtenciones;

use App\Modules\InscripcionesAtenciones\Contracts\VigenciaEntidadContract;
use App\Modules\InscripcionesAtenciones\Enums\TipoEntidadVigencia;
use App\Modules\InscripcionesAtenciones\Models\PaqueteSesiones;

final class EstadosAsistenciaTest extends VigenciasTestCase
{
    public function test_contract_allows_only_effective_active_inscriptions(): void
    {
        $escenario = $this->escenarioPaquete('administracion');
        $paquete = $this->crearPaquete($escenario, [
            'fecha_inicio' => '2026-11-01',
            'fecha_fin' => '2026-11-30',
        ])->assertCreated()->json('data');

        $contract = app(VigenciaEntidadContract::class);
        $tipo = TipoEntidadVigencia::PAQUETE_SESIONES;

        $this->assertTrue($contract->puedeRegistrarAsistencia($tipo, $paquete['id'], '2026-11-10'));

        PaqueteSesiones::query()->whereKey($paquete['id'])->update(['estado' => 'en_pausa']);
        $this->assertFalse($contract->puedeRegistrarAsistencia($tipo, $paquete['id'], '2026-11-10'));

        PaqueteSesiones::query()->whereKey($paquete['id'])->update(['estado' => 'activo']);
        $this->postJson('/api/inscripciones-atenciones/paquetes-sesiones/'.$paquete['id'].'/renovar', [])
            ->assertCreated();

        $this->assertFalse($contract->puedeRegistrarAsistencia($tipo, $paquete['id'], '2026-11-10'));
        $this->assertFalse($contract->puedeRegistrarAsistencia($tipo, $paquete['id'], '2026-12-01'));
        $this->assertFalse($contract->puedeRegistrarAsistencia($tipo, 999999, '2026-11-10'));
    }
}
