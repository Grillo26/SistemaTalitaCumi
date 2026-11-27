<?php

namespace Tests\Feature\Modules\AgendaTurnos;

use App\Models\User;
use App\Modules\AgendaTurnos\Models\Feriado;
use App\Modules\InscripcionesAtenciones\Models\PaqueteSesiones;
use Laravel\Sanctum\Sanctum;

/**
 * [Programación de Turnos] RF-016 / RN-008 / RT-003.
 */
final class FeriadosTest extends ProgramacionTurnosTestCase
{
    public function test_crud_of_holidays_and_unique_per_scope(): void
    {
        $escenario = $this->escenario();

        $creado = $this->postJson('/api/agenda-turnos/programacion-turnos/feriados', [
            'fecha' => '2025-12-09',
            'descripcion' => 'Feriado global',
        ])->assertCreated()->assertJsonPath('data.activo', true);

        $id = (int) $creado->json('data.id');

        $this->postJson('/api/agenda-turnos/programacion-turnos/feriados', [
            'fecha' => '2025-12-09',
            'descripcion' => 'Duplicado',
        ])->assertStatus(409)->assertJsonPath('code', 'FERIADO_INVALIDO');

        $this->postJson('/api/agenda-turnos/programacion-turnos/feriados', [
            'fecha' => '2025-12-09',
            'descripcion' => 'Feriado de sucursal',
            'sucursal_id' => $escenario['branch']->id,
        ])->assertCreated();

        $this->patchJson("/api/agenda-turnos/programacion-turnos/feriados/{$id}", [
            'descripcion' => 'Feriado global editado',
        ])->assertOk()->assertJsonPath('data.descripcion', 'Feriado global editado');

        $this->postJson("/api/agenda-turnos/programacion-turnos/feriados/{$id}/desactivar")
            ->assertOk()
            ->assertJsonPath('data.activo', false);
    }

    public function test_index_returns_global_and_accessible_branch_holidays_only(): void
    {
        $escenario = $this->escenario();
        $otraSucursal = $this->branch();

        Feriado::query()->create([
            'fecha' => '2025-12-09',
            'descripcion' => 'Global',
            'sucursal_id' => null,
            'activo' => true,
        ]);
        Feriado::query()->create([
            'fecha' => '2025-12-10',
            'descripcion' => 'De mi sucursal',
            'sucursal_id' => $escenario['branch']->id,
            'activo' => true,
        ]);
        Feriado::query()->create([
            'fecha' => '2025-12-11',
            'descripcion' => 'De otra sucursal',
            'sucursal_id' => $otraSucursal->id,
            'activo' => true,
        ]);

        $response = $this->getJson('/api/agenda-turnos/programacion-turnos/feriados');

        $response->assertOk()->assertJsonCount(2, 'data');
        $this->assertSame(
            ['Global', 'De mi sucursal'],
            array_column($response->json('data'), 'descripcion'),
        );
    }

    public function test_deactivated_holiday_no_longer_excludes_dates(): void
    {
        $escenario = $this->escenario();
        $paquete = $this->crearPaquete($escenario);

        $feriado = Feriado::query()->create([
            'fecha' => '2025-12-09',
            'descripcion' => 'Feriado temporal',
            'sucursal_id' => null,
            'activo' => true,
        ]);

        $this->getJson($this->url('paquete_sesiones', $paquete->id, '/preview-generacion'))
            ->assertOk()
            ->assertJsonPath('data.resumen.feriados', 1);

        $this->postJson("/api/agenda-turnos/programacion-turnos/feriados/{$feriado->id}/desactivar")
            ->assertOk();

        $this->getJson($this->url('paquete_sesiones', $paquete->id, '/preview-generacion'))
            ->assertOk()
            ->assertJsonPath('data.resumen.feriados', 0);
    }

    public function test_reception_cannot_manage_holidays_nor_regenerate(): void
    {
        $escenario = $this->escenario();
        $paquete = $this->crearPaquete($escenario);

        $recepcion = User::factory()->create();
        $recepcion->assignRole('recepcion');
        $recepcion->branches()->sync([$escenario['branch']->id]);
        Sanctum::actingAs($recepcion);

        $this->getJson('/api/agenda-turnos/programacion-turnos/feriados')
            ->assertStatus(403);

        $this->getJson($this->url('paquete_sesiones', $paquete->id, '/preview-regeneracion'))
            ->assertStatus(403);
    }

    public function test_creating_a_package_does_not_generate_turns_automatically(): void
    {
        $escenario = $this->escenario();

        $this->postJson('/api/inscripciones-atenciones/paquetes-sesiones', [
            'paciente_id' => $escenario['paciente']->id,
            'tutor_responsable_id' => $escenario['tutor']->id,
            'sucursal_id' => $escenario['branch']->id,
            'servicio_id' => $escenario['servicio']->id,
            'profesional_id' => null,
            'categoria_tarifa_id' => $escenario['categoria']->id,
            'cantidad_sesiones' => 10,
            'dias_semana' => [2, 4],
            'hora' => '09:00',
            'fecha_inicio' => '2025-12-01',
            'fecha_fin' => '2025-12-31',
        ])->assertCreated();

        $this->assertDatabaseCount('turno', 0);
        $this->assertDatabaseCount('turno_lote_generacion', 0);
        $this->assertSame(1, PaqueteSesiones::query()->count());
    }
}
