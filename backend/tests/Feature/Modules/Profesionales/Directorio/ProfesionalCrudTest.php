<?php

namespace Tests\Feature\Modules\Profesionales\Directorio;

use App\Modules\Profesionales\Enums\TipoEspecialidad;
use App\Modules\Profesionales\Models\Especialidad;
use App\Modules\Profesionales\Models\ProfesionalEspecialidad;
use Tests\Feature\Modules\Profesionales\ProfesionalesTestCase;

final class ProfesionalCrudTest extends ProfesionalesTestCase
{
    public function test_superadmin_creates_professional_with_principal_specialty(): void
    {
        $this->actingAsSuperadmin();
        $principal = $this->especialidad();

        $response = $this->postJson('/api/profesionales/profesionales', $this->profesionalPayload([
            'especialidad_principal_id' => $principal->id,
        ]));

        $response->assertCreated()
            ->assertJsonPath('data.nombre_completo', 'Ana Profesional')
            ->assertJsonPath('data.ci', '12345678')
            ->assertJsonPath('data.estado', 'activo')
            ->assertJsonPath('data.especialidad_principal_id', $principal->id)
            ->assertJsonPath('data.asignable', false)
            ->assertJsonPath('can.create', true);

        $this->assertDatabaseHas('profesional', [
            'ci' => '12345678',
            'activo' => true,
        ]);

        $this->assertDatabaseHas('profesional_especialidad', [
            'profesional_id' => $response->json('data.id'),
            'especialidad_id' => $principal->id,
            'tipo' => 'principal',
            'activo' => true,
        ]);
    }

    public function test_duplicate_document_is_rejected_case_insensitively(): void
    {
        $this->actingAsSuperadmin();

        $this->postJson('/api/profesionales/profesionales', $this->profesionalPayload())
            ->assertCreated();

        $this->postJson('/api/profesionales/profesionales', $this->profesionalPayload([
            'nombre_completo' => 'Otra Profesional',
            'ci' => ' 12345678 ',
        ]))
            ->assertStatus(422)
            ->assertJsonValidationErrors('ci')
            ->assertJsonPath('code', 'PROFESIONAL_DOCUMENT_DUPLICATED');
    }

    public function test_required_fields_and_principal_specialty_are_required(): void
    {
        $this->actingAsSuperadmin();

        $this->postJson('/api/profesionales/profesionales', [
            'nombre_completo' => '',
            'ci' => '',
        ])
            ->assertStatus(422)
            ->assertJsonValidationErrors(['nombre_completo', 'ci', 'especialidad_principal_id']);
    }

    public function test_inactive_specialty_cannot_be_assigned(): void
    {
        $this->actingAsSuperadmin();
        $inactive = Especialidad::factory()->create(['activo' => false]);

        $this->postJson('/api/profesionales/profesionales', $this->profesionalPayload([
            'especialidad_principal_id' => $inactive->id,
        ]))
            ->assertStatus(422)
            ->assertJsonValidationErrors('especialidad_principal_id');
    }

    public function test_secondary_specialties_are_rejected_while_pending_confirmation(): void
    {
        $this->actingAsSuperadmin();
        $secondary = Especialidad::factory()->create();

        $this->postJson('/api/profesionales/profesionales', $this->profesionalPayload([
            'especialidades_secundarias' => [$secondary->id],
        ]))
            ->assertStatus(422)
            ->assertJsonValidationErrors('especialidades_secundarias');
    }

    public function test_professional_can_be_created_inactive(): void
    {
        $this->actingAsSuperadmin();

        $this->postJson('/api/profesionales/profesionales', $this->profesionalPayload([
            'activo' => false,
        ]))
            ->assertCreated()
            ->assertJsonPath('data.activo', false)
            ->assertJsonPath('data.motivo_estado', null);
    }

    public function test_superadmin_updates_data_and_contact_with_version(): void
    {
        $this->actingAsSuperadmin();
        $profesional = $this->profesional(['nombre_completo' => 'Nombre Original', 'version' => 1]);

        $this->patchJson("/api/profesionales/profesionales/{$profesional->id}", [
            'nombre_completo' => 'Nombre Actualizado',
            'telefono' => '70000000',
            'version' => 1,
        ])
            ->assertOk()
            ->assertJsonPath('data.nombre_completo', 'Nombre Actualizado')
            ->assertJsonPath('data.telefono', '70000000')
            ->assertJsonPath('data.version', 2);
    }

    public function test_duplicate_document_on_update_is_rejected(): void
    {
        $this->actingAsSuperadmin();
        $this->profesional(['ci' => '1111111']);
        $target = $this->profesional(['ci' => '2222222']);

        $this->patchJson("/api/profesionales/profesionales/{$target->id}", [
            'ci' => '1111111',
            'version' => 1,
        ])
            ->assertStatus(422)
            ->assertJsonValidationErrors('ci');
    }

    public function test_version_conflict_returns_409(): void
    {
        $this->actingAsSuperadmin();
        $profesional = $this->profesional(['version' => 3]);

        $this->patchJson("/api/profesionales/profesionales/{$profesional->id}", [
            'telefono' => '70000001',
            'version' => 2,
        ])
            ->assertStatus(409)
            ->assertJsonPath('code', 'PROFESIONAL_VERSION_CONFLICT');
    }

    public function test_changing_principal_specialty_keeps_single_principal(): void
    {
        $this->actingAsSuperadmin();
        $original = $this->especialidad('Psicología');
        $new = Especialidad::factory()->create(['nombre' => 'Fisioterapia Test']);
        $profesional = $this->profesional([], $original);

        $this->patchJson("/api/profesionales/profesionales/{$profesional->id}", [
            'especialidad_principal_id' => $new->id,
            'especialidades_secundarias' => [],
            'version' => 1,
        ])
            ->assertOk()
            ->assertJsonPath('data.especialidad_principal_id', $new->id);

        $this->assertSame(1, ProfesionalEspecialidad::query()
            ->where('profesional_id', $profesional->id)
            ->where('tipo', TipoEspecialidad::PRINCIPAL->value)
            ->where('activo', true)
            ->count());

        $this->assertDatabaseHas('profesional_especialidad', [
            'profesional_id' => $profesional->id,
            'especialidad_id' => $original->id,
            'activo' => false,
        ]);
    }

    public function test_administracion_edits_contact_but_not_structural_data(): void
    {
        $branch = $this->branch();
        $profesional = $this->profesional();
        $this->assignBranch($profesional, $branch);

        $user = $this->actingAsRole('administracion');
        $this->withBranch($user, $branch);

        $this->patchJson("/api/profesionales/profesionales/{$profesional->id}", [
            'telefono' => '71111111',
            'version' => 1,
        ])
            ->assertOk()
            ->assertJsonPath('data.telefono', '71111111');

        $this->patchJson("/api/profesionales/profesionales/{$profesional->id}", [
            'nombre_completo' => 'Nombre prohibido',
            'version' => 2,
        ])->assertForbidden();
    }

    public function test_recepcion_cannot_create_or_edit(): void
    {
        $branch = $this->branch();
        $profesional = $this->profesional();
        $this->assignBranch($profesional, $branch);

        $user = $this->actingAsRole('recepcion');
        $this->withBranch($user, $branch);

        $this->postJson('/api/profesionales/profesionales', $this->profesionalPayload())
            ->assertForbidden();

        $this->patchJson("/api/profesionales/profesionales/{$profesional->id}", [
            'telefono' => '71111111',
            'version' => 1,
        ])->assertForbidden();
    }

    public function test_delete_is_always_rejected_with_409(): void
    {
        $this->actingAsSuperadmin();
        $profesional = $this->profesional();

        $this->deleteJson("/api/profesionales/profesionales/{$profesional->id}")
            ->assertStatus(409)
            ->assertJsonPath('code', 'PROFESIONAL_DELETE_NOT_ALLOWED');

        $this->assertDatabaseHas('profesional', ['id' => $profesional->id]);
    }

    public function test_out_of_scope_professional_cannot_be_read_or_updated(): void
    {
        $branchA = $this->branch();
        $branchB = $this->branch();
        $profesional = $this->profesional();
        $this->assignBranch($profesional, $branchB);

        $user = $this->actingAsRole('administracion');
        $this->withBranch($user, $branchA);

        $this->getJson("/api/profesionales/profesionales/{$profesional->id}")
            ->assertForbidden();

        $this->patchJson("/api/profesionales/profesionales/{$profesional->id}", [
            'telefono' => '71111111',
            'version' => 1,
        ])->assertForbidden();
    }
}
