<?php

namespace Tests\Feature\Modules\InscripcionesAtenciones;

use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;

final class PaqueteConstraintTest extends PaquetesSesionesTestCase
{
    public function test_postgresql_rejects_overlapping_active_packages(): void
    {
        if (DB::getDriverName() !== 'pgsql') {
            $this->markTestSkipped('La constraint EXCLUDE solo aplica a PostgreSQL.');
        }

        $escenario = $this->escenarioPaquete('administracion');
        $this->crearPaquete($escenario)->assertCreated();

        $this->expectException(QueryException::class);

        DB::table('paquete_sesiones')->insert([
            'paciente_id' => $escenario['paciente']->id,
            'tutor_responsable_id' => $escenario['tutor']->id,
            'sucursal_id' => $escenario['branch']->id,
            'servicio_id' => $escenario['servicio']->id,
            'profesional_id' => null,
            'tarifa_id' => $escenario['tarifa']->id,
            'categoria_tarifa_id' => $escenario['categoria']->id,
            'cantidad_sesiones' => 10,
            'dias_semana' => '1,3',
            'hora' => '08:00',
            'duracion_minutos' => 50,
            'fecha_inicio' => '2026-12-01',
            'fecha_fin' => '2026-12-31',
            'monto_esperado' => '500.00',
            'estado' => 'activo',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    public function test_postgresql_allows_same_period_for_another_service(): void
    {
        if (DB::getDriverName() !== 'pgsql') {
            $this->markTestSkipped('La constraint EXCLUDE solo aplica a PostgreSQL.');
        }

        $escenario = $this->escenarioPaquete('administracion');
        $this->crearPaquete($escenario)->assertCreated();

        $otroServicio = $this->servicioTerapia(['nombre' => 'Fonoaudiología individual']);
        $otraCategoria = $this->categoriaTarifa(['nombre' => 'General 2']);
        $otraTarifa = $this->tarifa($otroServicio, $escenario['branch'], $otraCategoria, '40.00');

        DB::table('paquete_sesiones')->insert([
            'paciente_id' => $escenario['paciente']->id,
            'tutor_responsable_id' => $escenario['tutor']->id,
            'sucursal_id' => $escenario['branch']->id,
            'servicio_id' => $otroServicio->id,
            'profesional_id' => null,
            'tarifa_id' => $otraTarifa->id,
            'categoria_tarifa_id' => $otraCategoria->id,
            'cantidad_sesiones' => 10,
            'dias_semana' => '1,3',
            'hora' => '08:00',
            'duracion_minutos' => 50,
            'fecha_inicio' => '2026-11-26',
            'fecha_fin' => '2026-12-25',
            'monto_esperado' => '500.00',
            'estado' => 'activo',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $this->assertSame(2, DB::table('paquete_sesiones')->count());
    }
}
