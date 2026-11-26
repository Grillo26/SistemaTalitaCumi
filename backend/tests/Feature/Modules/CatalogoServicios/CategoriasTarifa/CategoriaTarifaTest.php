<?php

namespace Tests\Feature\Modules\CatalogoServicios\CategoriasTarifa;

use App\Modules\CatalogoServicios\Models\BitacoraEntry;
use App\Modules\CatalogoServicios\Models\CategoriaTarifa;
use Tests\Feature\Modules\CatalogoServicios\Tarifas\TarifasTestCase;

final class CategoriaTarifaTest extends TarifasTestCase
{
    public function test_superadmin_creates_category_and_audits(): void
    {
        $this->actingAsSuperadmin();

        $this->postJson('/api/catalogo-servicios/categorias-tarifa', [
            'nombre' => '  Hermanos   menores ',
            'descripcion' => 'Categoría para hermanos',
        ])
            ->assertCreated()
            ->assertJsonPath('data.nombre', 'Hermanos menores')
            ->assertJsonPath('data.estado', 'activo')
            ->assertJsonPath('data.version', 1)
            ->assertJsonPath('can.categorias.create', true);

        $this->assertDatabaseHas('bitacora', [
            'entidad' => 'categoria_tarifa',
            'accion' => 'CREAR',
        ]);
    }

    public function test_duplicate_category_name_is_rejected_case_and_space_insensitive(): void
    {
        $this->actingAsSuperadmin();

        $this->postJson('/api/catalogo-servicios/categorias-tarifa', [
            'nombre' => '  general ',
        ])
            ->assertStatus(422)
            ->assertJsonValidationErrors('nombre')
            ->assertJsonPath('code', 'CATEGORIA_TARIFA_NOMBRE_DUPLICADO');
    }

    public function test_category_update_is_audited_and_versioned(): void
    {
        $categoria = $this->categoria('Categoría Editable');
        $this->actingAsSuperadmin();

        $this->patchJson("/api/catalogo-servicios/categorias-tarifa/{$categoria->id}", [
            'nombre' => 'Categoría Renombrada',
            'descripcion' => 'Nueva descripción',
            'version' => 1,
        ])
            ->assertOk()
            ->assertJsonPath('data.nombre', 'Categoría Renombrada')
            ->assertJsonPath('data.version', 2);

        $audit = BitacoraEntry::query()
            ->where('entidad', 'categoria_tarifa')
            ->where('accion', 'ACTUALIZAR')
            ->sole();

        $this->assertSame('Categoría Editable', $audit->datos_anteriores['nombre']);
    }

    public function test_category_with_current_tariff_cannot_be_deactivated(): void
    {
        $categoria = $this->categoria('Categoría Con Tarifa');
        $servicio = $this->servicioActivo();
        $this->tarifa($servicio, $categoria, [
            'vigente_desde' => '2026-01-01',
            'vigente_hasta' => null,
        ]);

        $this->actingAsSuperadmin();

        $this->postJson("/api/catalogo-servicios/categorias-tarifa/{$categoria->id}/desactivar", [
            'motivo' => 'Cierre',
            'version' => 1,
        ])
            ->assertStatus(409)
            ->assertJsonPath('code', 'CATEGORIA_CON_TARIFAS_VIGENTES')
            ->assertJsonPath('active_tariffs_count', 1);
    }

    public function test_category_with_future_tariff_can_be_deactivated(): void
    {
        $categoria = $this->categoria('Categoría Futura');
        $servicio = $this->servicioActivo();
        $this->tarifa($servicio, $categoria, [
            'vigente_desde' => '2027-01-01',
            'vigente_hasta' => null,
        ]);

        $this->actingAsSuperadmin();

        $this->postJson("/api/catalogo-servicios/categorias-tarifa/{$categoria->id}/desactivar", [
            'motivo' => 'Cierre',
            'version' => 1,
        ])
            ->assertOk()
            ->assertJsonPath('data.activo', false);
    }

    public function test_deactivation_requires_reason_and_activation_does_not(): void
    {
        $categoria = $this->categoria('Categoría Motivo');
        $this->actingAsSuperadmin();

        $this->postJson("/api/catalogo-servicios/categorias-tarifa/{$categoria->id}/desactivar", [
            'version' => 1,
        ])->assertStatus(422)->assertJsonValidationErrors('motivo');

        $this->postJson("/api/catalogo-servicios/categorias-tarifa/{$categoria->id}/desactivar", [
            'motivo' => 'Cierre',
            'version' => 1,
        ])->assertOk();

        $this->postJson("/api/catalogo-servicios/categorias-tarifa/{$categoria->id}/activar", [
            'version' => 2,
        ])->assertOk()->assertJsonPath('data.activo', true);
    }

    public function test_delete_is_always_rejected(): void
    {
        $categoria = $this->categoria('Categoría Sin Borrado');
        $this->actingAsSuperadmin();

        $this->deleteJson("/api/catalogo-servicios/categorias-tarifa/{$categoria->id}")
            ->assertStatus(409)
            ->assertJsonPath('code', 'CATEGORIA_TARIFA_DELETE_NOT_ALLOWED');
    }

    public function test_administracion_and_recepcion_can_read_but_not_write(): void
    {
        foreach (['administracion', 'recepcion'] as $role) {
            $this->actingAsRole($role);

            $this->getJson('/api/catalogo-servicios/categorias-tarifa')->assertOk();
            $this->postJson('/api/catalogo-servicios/categorias-tarifa', [
                'nombre' => 'No permitido',
            ])->assertForbidden();
        }
    }
}
