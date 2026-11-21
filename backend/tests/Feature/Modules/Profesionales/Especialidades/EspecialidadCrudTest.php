<?php

namespace Tests\Feature\Modules\Profesionales\Especialidades;

use App\Modules\Profesionales\Models\Especialidad;
use Tests\Feature\Modules\Profesionales\ProfesionalesTestCase;

final class EspecialidadCrudTest extends ProfesionalesTestCase
{
    public function test_superadmin_creates_and_updates_specialty(): void
    {
        $this->actingAsSuperadmin();

        $created = $this->postJson('/api/profesionales/especialidades', [
            'nombre' => 'Terapia del Lenguaje Test',
            'descripcion' => 'Descripción inicial',
            'activo' => true,
        ])
            ->assertCreated()
            ->assertJsonPath('data.nombre', 'Terapia del Lenguaje Test')
            ->assertJsonPath('data.estado', 'activo')
            ->json('data');

        $this->patchJson("/api/profesionales/especialidades/{$created['id']}", [
            'nombre' => 'Terapia del Lenguaje Actualizada',
            'descripcion' => 'Descripción nueva',
            'version' => 1,
        ])
            ->assertOk()
            ->assertJsonPath('data.nombre', 'Terapia del Lenguaje Actualizada')
            ->assertJsonPath('data.version', 2);
    }

    public function test_duplicate_name_is_case_insensitive(): void
    {
        $this->actingAsSuperadmin();

        $this->postJson('/api/profesionales/especialidades', [
            'nombre' => 'Especialidad Única',
        ])->assertCreated();

        $this->postJson('/api/profesionales/especialidades', [
            'nombre' => '  especialidad única  ',
        ])
            ->assertStatus(422)
            ->assertJsonValidationErrors('nombre')
            ->assertJsonPath('code', 'ESPECIALIDAD_NAME_DUPLICATED');
    }

    public function test_name_is_required(): void
    {
        $this->actingAsSuperadmin();

        $this->postJson('/api/profesionales/especialidades', [
            'nombre' => '',
        ])
            ->assertStatus(422)
            ->assertJsonValidationErrors('nombre');
    }

    public function test_version_conflict_returns_409(): void
    {
        $this->actingAsSuperadmin();
        $especialidad = Especialidad::factory()->create(['version' => 3]);

        $this->patchJson("/api/profesionales/especialidades/{$especialidad->id}", [
            'nombre' => 'Otro nombre',
            'version' => 2,
        ])
            ->assertStatus(409)
            ->assertJsonPath('code', 'ESPECIALIDAD_VERSION_CONFLICT');
    }

    public function test_delete_is_always_rejected_with_409(): void
    {
        $this->actingAsSuperadmin();
        $especialidad = Especialidad::factory()->create();

        $this->deleteJson("/api/profesionales/especialidades/{$especialidad->id}")
            ->assertStatus(409)
            ->assertJsonPath('code', 'ESPECIALIDAD_DELETE_NOT_ALLOWED');

        $this->assertDatabaseHas('especialidad', ['id' => $especialidad->id]);
    }

    public function test_administracion_and_recepcion_can_read_but_not_write(): void
    {
        $especialidad = Especialidad::factory()->create();

        foreach (['administracion', 'recepcion'] as $role) {
            $this->actingAsRole($role);

            $this->getJson('/api/profesionales/especialidades')->assertOk();
            $this->getJson("/api/profesionales/especialidades/{$especialidad->id}")->assertOk();

            $this->postJson('/api/profesionales/especialidades', ['nombre' => 'Prohibida'])
                ->assertForbidden();

            $this->patchJson("/api/profesionales/especialidades/{$especialidad->id}", [
                'nombre' => 'Prohibida',
                'version' => 1,
            ])->assertForbidden();
        }
    }
}
