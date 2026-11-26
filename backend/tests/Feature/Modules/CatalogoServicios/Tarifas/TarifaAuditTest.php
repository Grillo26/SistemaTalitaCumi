<?php

namespace Tests\Feature\Modules\CatalogoServicios\Tarifas;

use App\Modules\CatalogoServicios\Models\BitacoraEntry;

final class TarifaAuditTest extends TarifasTestCase
{
    public function test_create_update_and_close_are_audited_with_values(): void
    {
        $servicio = $this->servicioActivo('Servicio Auditoría Tarifa');
        $categoria = $this->categoria('Categoría Auditoría Tarifa');
        $this->actingAsSuperadmin();

        $created = $this->postJson('/api/catalogo-servicios/tarifas', $this->tarifaPayload($servicio, $categoria, [
            'monto' => '150.00',
            'vigente_desde' => '2027-01-01',
            'vigente_hasta' => null,
        ]))->assertCreated()->json('data');

        $id = $created['id'];

        $this->patchJson("/api/catalogo-servicios/tarifas/{$id}", [
            'monto' => '175.00',
            'version' => 1,
        ])->assertOk();

        $this->postJson("/api/catalogo-servicios/tarifas/{$id}/cerrar-vigencia", [
            'vigente_hasta' => '2027-12-31',
            'motivo' => 'Cierre auditado',
            'version' => 2,
        ])->assertOk();

        $entries = BitacoraEntry::query()
            ->where('entidad', 'tarifa')
            ->where('entidad_id', $id)
            ->orderBy('id')
            ->get();

        $this->assertSame(['CREAR', 'ACTUALIZAR', 'CERRAR_VIGENCIA'], $entries->pluck('accion')->all());
        $this->assertSame('150.00', $entries[0]->datos_nuevos['monto']);
        $this->assertSame('175.00', $entries[1]->datos_nuevos['monto']);
        $this->assertSame('Cierre auditado', $entries[2]->motivo);
        $this->assertSame('2027-12-31', $entries[2]->datos_nuevos['vigente_hasta']);
    }

    public function test_category_status_change_is_audited_with_reason(): void
    {
        $categoria = $this->categoria('Categoría Auditoría Estado');
        $this->actingAsSuperadmin();

        $this->postJson("/api/catalogo-servicios/categorias-tarifa/{$categoria->id}/desactivar", [
            'motivo' => 'Depuración de categorías',
            'version' => 1,
        ])->assertOk();

        $this->assertDatabaseHas('bitacora', [
            'entidad' => 'categoria_tarifa',
            'entidad_id' => $categoria->id,
            'accion' => 'DESACTIVAR',
            'motivo' => 'Depuración de categorías',
        ]);
    }
}
