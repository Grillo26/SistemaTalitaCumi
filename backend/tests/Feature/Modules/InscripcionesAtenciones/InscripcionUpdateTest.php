<?php

namespace Tests\Feature\Modules\InscripcionesAtenciones;

use App\Modules\InscripcionesAtenciones\Models\BitacoraEntry;
use App\Modules\InscripcionesAtenciones\Models\InscripcionSala;
use Illuminate\Support\Facades\Cache;

final class InscripcionUpdateTest extends InscripcionesAtencionesTestCase
{
    public function test_updates_dates_and_records_audit_before_after(): void
    {
        $scenario = $this->scenario('administracion');
        $created = $this->crearInscripcion($scenario)->assertCreated()->json('data');

        $this->patchJson('/api/inscripciones-atenciones/salas-grupales/'.$created['id'], [
            'fecha_fin' => '2026-11-15',
        ])->assertOk()->assertJsonPath('data.fecha_fin', '2026-11-15');

        $audit = BitacoraEntry::query()
            ->where('entidad', 'inscripcion_sala')
            ->where('entidad_id', $created['id'])
            ->where('accion', 'ACTUALIZAR')
            ->firstOrFail();

        $this->assertSame('2026-11-30', $audit->datos_anteriores['fecha_fin']);
        $this->assertSame('2026-11-15', $audit->datos_nuevos['fecha_fin']);
    }

    public function test_snapshot_is_immutable_when_catalog_tariff_changes(): void
    {
        $scenario = $this->scenario('administracion');
        $created = $this->crearInscripcion($scenario)->assertCreated()->json('data');

        $scenario['tarifa']->update(['monto' => '900.00']);
        Cache::flush();

        $this->getJson('/api/inscripciones-atenciones/salas-grupales/'.$created['id'])
            ->assertOk()
            ->assertJsonPath('data.monto_esperado', '500.00');
    }

    public function test_editing_only_observations_keeps_snapshot_after_catalog_change(): void
    {
        $scenario = $this->scenario('administracion');
        $created = $this->crearInscripcion($scenario)->assertCreated()->json('data');

        $scenario['tarifa']->update(['monto' => '900.00']);
        Cache::flush();

        $this->patchJson('/api/inscripciones-atenciones/salas-grupales/'.$created['id'], [
            'observaciones' => 'Corrección administrativa',
        ])
            ->assertOk()
            ->assertJsonPath('data.monto_esperado', '500.00')
            ->assertJsonPath('data.observaciones', 'Corrección administrativa');
    }

    public function test_tariff_change_requires_confirmation_and_resnapshots(): void
    {
        $scenario = $this->scenario('administracion');
        $created = $this->crearInscripcion($scenario)->assertCreated()->json('data');

        $newCategory = $this->categoriaTarifa(['nombre' => 'Categoría especial']);
        $newTariff = $this->tarifa(
            $scenario['servicio'],
            $scenario['branch'],
            $newCategory,
            '650.00',
        );
        Cache::flush();

        $payload = [
            'categoria_tarifa_id' => $newCategory->id,
            'tarifa_id_esperada' => $created['tarifa_id'],
        ];

        $this->patchJson('/api/inscripciones-atenciones/salas-grupales/'.$created['id'], $payload)
            ->assertStatus(409)
            ->assertJsonPath('code', 'TARIFA_CAMBIO')
            ->assertJsonPath('tarifa_aplicable.tarifa_id', $newTariff->id);

        $payload['confirmar_tarifa_cambio'] = true;

        $this->patchJson('/api/inscripciones-atenciones/salas-grupales/'.$created['id'], $payload)
            ->assertOk()
            ->assertJsonPath('data.categoria.id', $newCategory->id)
            ->assertJsonPath('data.monto_esperado', '650.00');
    }

    public function test_edit_excludes_its_own_inscription_from_overlap_check(): void
    {
        $scenario = $this->scenario('administracion');
        $created = $this->crearInscripcion($scenario, [
            'fecha_inicio' => '2026-11-01',
            'fecha_fin' => '2026-11-15',
        ])->assertCreated()->json('data');

        $this->patchJson('/api/inscripciones-atenciones/salas-grupales/'.$created['id'], [
            'fecha_fin' => '2026-11-30',
        ])->assertOk();
    }

    public function test_blocks_update_overlapping_another_inscription(): void
    {
        $scenario = $this->scenario('administracion');
        $first = $this->crearInscripcion($scenario, [
            'fecha_inicio' => '2026-10-01',
            'fecha_fin' => '2026-10-31',
        ])->assertCreated()->json('data');

        $this->crearInscripcion($scenario, [
            'fecha_inicio' => '2026-12-01',
            'fecha_fin' => '2026-12-31',
        ])->assertCreated();

        $this->patchJson('/api/inscripciones-atenciones/salas-grupales/'.$first['id'], [
            'fecha_inicio' => '2026-12-01',
            'fecha_fin' => '2026-12-31',
        ])
            ->assertStatus(409)
            ->assertJsonPath('code', 'INSCRIPCION_DUPLICADA');
    }

    public function test_blocks_update_after_withdraw(): void
    {
        $scenario = $this->scenario('administracion');
        $created = $this->crearInscripcion($scenario)->assertCreated()->json('data');

        $this->postJson('/api/inscripciones-atenciones/salas-grupales/'.$created['id'].'/retirar', [
            'motivo' => 'retiro_paciente',
            'fecha_baja' => '2026-11-25',
        ])->assertOk();

        $this->patchJson('/api/inscripciones-atenciones/salas-grupales/'.$created['id'], [
            'observaciones' => 'Intento posterior',
        ])
            ->assertStatus(409)
            ->assertJsonPath('code', 'INSCRIPCION_RETIRADA');
    }

    public function test_rejects_observations_over_500_characters(): void
    {
        $scenario = $this->scenario('administracion');
        $created = $this->crearInscripcion($scenario)->assertCreated()->json('data');

        $this->patchJson('/api/inscripciones-atenciones/salas-grupales/'.$created['id'], [
            'observaciones' => str_repeat('a', 501),
        ])->assertStatus(422)->assertJsonValidationErrors('observaciones');
    }

    public function test_does_not_allow_changing_patient_branch_or_sala(): void
    {
        $scenario = $this->scenario('administracion');
        $created = $this->crearInscripcion($scenario)->assertCreated()->json('data');

        $this->patchJson('/api/inscripciones-atenciones/salas-grupales/'.$created['id'], [
            'sala_id' => $scenario['sala']->id,
        ])->assertStatus(422)->assertJsonValidationErrors('sala_id');

        $this->assertSame(
            1,
            InscripcionSala::query()->whereKey($created['id'])->count(),
        );
    }
}
