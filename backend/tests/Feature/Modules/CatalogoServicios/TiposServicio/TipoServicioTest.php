<?php

namespace Tests\Feature\Modules\CatalogoServicios\TiposServicio;

use App\Modules\CatalogoServicios\Models\BitacoraEntry;
use App\Modules\CatalogoServicios\Models\TipoServicio;
use Tests\Feature\Modules\CatalogoServicios\CatalogoServiciosTestCase;

final class TipoServicioTest extends CatalogoServiciosTestCase
{
    public function test_superadmin_creates_type_and_audits(): void
    {
        $this->actingAsSuperadmin();

        $this->postJson('/api/catalogo-servicios/tipos-servicio', [
            'nombre' => '  Taller   grupal ',
        ])
            ->assertCreated()
            ->assertJsonPath('data.nombre', 'Taller grupal')
            ->assertJsonPath('data.estado', 'activo')
            ->assertJsonPath('data.version', 1);

        $this->assertDatabaseHas('bitacora', [
            'entidad' => 'tipo_servicio',
            'accion' => 'CREAR',
        ]);
    }

    public function test_duplicate_type_is_rejected_case_and_space_insensitive(): void
    {
        $this->actingAsSuperadmin();

        $this->postJson('/api/catalogo-servicios/tipos-servicio', [
            'nombre' => '  terapia   individual ',
        ])
            ->assertStatus(422)
            ->assertJsonValidationErrors('nombre')
            ->assertJsonPath('code', 'TIPO_SERVICIO_NOMBRE_DUPLICADO');
    }

    public function test_type_can_be_updated_and_status_changed_with_reason(): void
    {
        $tipo = $this->tipoServicio('Tipo Editable');
        $this->actingAsSuperadmin();

        $this->patchJson("/api/catalogo-servicios/tipos-servicio/{$tipo->id}", [
            'nombre' => 'Tipo Renombrado',
            'version' => 1,
        ])
            ->assertOk()
            ->assertJsonPath('data.nombre', 'Tipo Renombrado')
            ->assertJsonPath('data.version', 2);

        $this->postJson("/api/catalogo-servicios/tipos-servicio/{$tipo->id}/desactivar", [
            'motivo' => 'Ya no se ofrece',
            'version' => 2,
        ])
            ->assertOk()
            ->assertJsonPath('data.activo', false)
            ->assertJsonPath('data.version', 3);

        $this->assertDatabaseHas('bitacora', [
            'entidad' => 'tipo_servicio',
            'entidad_id' => $tipo->id,
            'accion' => 'DESACTIVAR',
            'motivo' => 'Ya no se ofrece',
        ]);
    }

    public function test_stale_version_returns_conflict(): void
    {
        $tipo = $this->tipoServicio('Tipo Versión');
        $this->actingAsSuperadmin();

        $this->patchJson("/api/catalogo-servicios/tipos-servicio/{$tipo->id}", [
            'nombre' => 'Otro',
            'version' => 99,
        ])
            ->assertStatus(409)
            ->assertJsonPath('code', 'VERSION_CONFLICT');
    }

    public function test_delete_is_always_rejected(): void
    {
        $tipo = $this->tipoServicio('Tipo Sin Borrado');
        $this->actingAsSuperadmin();

        $this->deleteJson("/api/catalogo-servicios/tipos-servicio/{$tipo->id}")
            ->assertStatus(409)
            ->assertJsonPath('code', 'TIPO_SERVICIO_DELETE_NOT_ALLOWED');
    }

    public function test_reception_can_read_but_not_write(): void
    {
        $this->actingAsRole('recepcion');

        $this->getJson('/api/catalogo-servicios/tipos-servicio')->assertOk();
        $this->postJson('/api/catalogo-servicios/tipos-servicio', ['nombre' => 'Nuevo'])
            ->assertForbidden();
    }

    public function test_seed_contains_the_four_base_types(): void
    {
        $names = TipoServicio::query()->orderBy('nombre')->pluck('nombre')->all();

        $this->assertContains('Entrevista', $names);
        $this->assertContains('Diagnóstico / Evaluación', $names);
        $this->assertContains('Terapia individual', $names);
        $this->assertContains('Sala grupal', $names);
    }

    public function test_type_seeder_is_idempotent(): void
    {
        $before = TipoServicio::query()->count();

        $this->seed(\Database\Seeders\TipoServicioSeeder::class);

        $this->assertSame($before, TipoServicio::query()->count());
    }
}
