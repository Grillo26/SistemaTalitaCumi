<?php

namespace Tests\Feature\Modules\Profesionales\Especialidades;

use App\Modules\Profesionales\Models\Especialidad;
use Tests\Feature\Modules\Profesionales\ProfesionalesTestCase;

final class EspecialidadStatusTest extends ProfesionalesTestCase
{
    public function test_cannot_deactivate_specialty_with_active_professionals(): void
    {
        $this->actingAsSuperadmin();
        $especialidad = $this->especialidad('Psicología');
        $this->profesional([], $especialidad);

        $this->postJson("/api/profesionales/especialidades/{$especialidad->id}/desactivar", [
            'motivo' => 'Sin demanda',
            'version' => 1,
        ])
            ->assertStatus(409)
            ->assertJsonPath('code', 'ESPECIALIDAD_IN_USE')
            ->assertJsonPath('profesionales_activos_count', 1);
    }

    public function test_can_deactivate_when_only_inactive_professionals_are_associated(): void
    {
        $this->actingAsSuperadmin();
        $especialidad = $this->especialidad('Psicología');
        $this->profesional(['activo' => false], $especialidad);

        $this->postJson("/api/profesionales/especialidades/{$especialidad->id}/desactivar", [
            'motivo' => 'Sin demanda',
            'version' => 1,
        ])
            ->assertOk()
            ->assertJsonPath('data.activo', false)
            ->assertJsonPath('data.motivo_estado', 'Sin demanda')
            ->assertJsonPath('data.version', 2);
    }

    public function test_activate_requires_reason_and_detects_unchanged_state(): void
    {
        $this->actingAsSuperadmin();
        $especialidad = Especialidad::factory()->create(['activo' => false, 'version' => 1]);

        $this->postJson("/api/profesionales/especialidades/{$especialidad->id}/activar", [
            'motivo' => 'Rehabilitar',
            'version' => 1,
        ])
            ->assertOk()
            ->assertJsonPath('data.activo', true)
            ->assertJsonPath('data.version', 2);

        $this->postJson("/api/profesionales/especialidades/{$especialidad->id}/activar", [
            'motivo' => 'Ya activa',
            'version' => 2,
        ])
            ->assertStatus(409)
            ->assertJsonPath('code', 'STATUS_UNCHANGED');
    }

    public function test_status_change_requires_reason(): void
    {
        $this->actingAsSuperadmin();
        $especialidad = Especialidad::factory()->create();

        $this->postJson("/api/profesionales/especialidades/{$especialidad->id}/desactivar", [
            'version' => 1,
        ])
            ->assertStatus(422)
            ->assertJsonValidationErrors('motivo');
    }
}
