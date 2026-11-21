<?php

namespace Tests\Feature\Modules\Profesionales\Directorio;

use App\Modules\Profesionales\Contracts\ProfessionalDirectoryContract;
use App\Modules\Profesionales\Exceptions\ProfesionalInactivoException;
use Tests\Feature\Modules\Profesionales\ProfesionalesTestCase;

final class ProfesionalStatusTest extends ProfesionalesTestCase
{
    public function test_deactivate_and_activate_require_reason_and_bump_version(): void
    {
        $this->actingAsSuperadmin();
        $profesional = $this->profesional(['version' => 1]);

        $this->postJson("/api/profesionales/profesionales/{$profesional->id}/desactivar", [
            'motivo' => 'Licencia',
            'version' => 1,
        ])
            ->assertOk()
            ->assertJsonPath('data.activo', false)
            ->assertJsonPath('data.motivo_estado', 'Licencia')
            ->assertJsonPath('data.version', 2);

        $this->expectException(ProfesionalInactivoException::class);
        app(ProfessionalDirectoryContract::class)->asegurarActivo($profesional->id);
    }

    public function test_activate_with_reason(): void
    {
        $this->actingAsSuperadmin();
        $profesional = $this->profesional(['activo' => false, 'version' => 1, 'motivo_estado' => 'Licencia']);

        $this->postJson("/api/profesionales/profesionales/{$profesional->id}/activar", [
            'motivo' => 'Retorno',
            'version' => 1,
        ])
            ->assertOk()
            ->assertJsonPath('data.activo', true)
            ->assertJsonPath('data.motivo_estado', null)
            ->assertJsonPath('data.version', 2);
    }

    public function test_missing_reason_is_422(): void
    {
        $this->actingAsSuperadmin();
        $profesional = $this->profesional();

        $this->postJson("/api/profesionales/profesionales/{$profesional->id}/desactivar", [
            'version' => 1,
        ])
            ->assertStatus(422)
            ->assertJsonValidationErrors('motivo');
    }

    public function test_unchanged_status_is_409(): void
    {
        $this->actingAsSuperadmin();
        $profesional = $this->profesional(['activo' => true, 'version' => 1]);

        $this->postJson("/api/profesionales/profesionales/{$profesional->id}/activar", [
            'motivo' => 'Ya activo',
            'version' => 1,
        ])
            ->assertStatus(409)
            ->assertJsonPath('code', 'STATUS_UNCHANGED');
    }

    public function test_status_version_conflict_is_409(): void
    {
        $this->actingAsSuperadmin();
        $profesional = $this->profesional(['version' => 2]);

        $this->postJson("/api/profesionales/profesionales/{$profesional->id}/desactivar", [
            'motivo' => 'Licencia',
            'version' => 1,
        ])
            ->assertStatus(409)
            ->assertJsonPath('code', 'PROFESIONAL_VERSION_CONFLICT');
    }

    public function test_professional_without_branch_can_be_deactivated_but_is_not_assignable(): void
    {
        $this->actingAsSuperadmin();
        $profesional = $this->profesional(['version' => 1]);

        $this->postJson("/api/profesionales/profesionales/{$profesional->id}/desactivar", [
            'motivo' => 'Sin sucursal',
            'version' => 1,
        ])->assertOk();

        $this->assertDatabaseHas('profesional', [
            'id' => $profesional->id,
            'activo' => false,
        ]);
    }
}
