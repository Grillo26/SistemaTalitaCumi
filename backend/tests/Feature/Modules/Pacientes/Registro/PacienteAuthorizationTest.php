<?php

namespace Tests\Feature\Modules\Pacientes\Registro;

use Laravel\Sanctum\Sanctum;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;
use Tests\Feature\Modules\Pacientes\PacientesTestCase;

final class PacienteAuthorizationTest extends PacientesTestCase
{
    public function test_administracion_can_edit_and_change_status_but_cannot_create(): void
    {
        $branch = $this->branch();
        $user = $this->actingAsRole('administracion');
        $this->withBranch($user, $branch);

        $this->postJson('/api/pacientes/pacientes', $this->pacientePayload([], $branch))
            ->assertStatus(403);

        $paciente = $this->paciente(['sucursal_registro_id' => $branch->id]);

        $this->patchJson('/api/pacientes/pacientes/'.$paciente->id, [
            'nombre' => 'Nombre editado',
            'version' => 1,
        ])->assertOk();

        $this->postJson('/api/pacientes/pacientes/'.$paciente->id.'/estado', [
            'estado' => 'en_pausa',
            'motivo' => 'Viaje',
            'version' => 2,
        ])->assertOk()->assertJsonPath('data.estado', 'en_pausa');
    }

    public function test_recepcion_can_create_but_not_change_status(): void
    {
        $branch = $this->branch();
        $user = $this->actingAsRole('recepcion');
        $this->withBranch($user, $branch);

        $created = $this->postJson('/api/pacientes/pacientes', $this->pacientePayload([], $branch))
            ->assertCreated()
            ->json('data');

        $this->postJson('/api/pacientes/pacientes/'.$created['id'].'/estado', [
            'estado' => 'en_pausa',
            'motivo' => 'Viaje familiar',
            'version' => $created['version'],
        ])->assertForbidden();
    }

    public function test_sensitive_fields_require_permission(): void
    {
        $branch = $this->branch();
        $superadmin = $this->actingAsSuperadmin();
        $this->withBranch($superadmin, $branch);

        $created = $this->postJson('/api/pacientes/pacientes', $this->pacientePayload([
            'alergias' => 'Penicilina',
            'condiciones_medicas' => 'Asma',
        ], $branch))->assertCreated()->json('data');

        $recepcion = $this->userWithRole('recepcion');
        $this->withBranch($recepcion, $branch);
        Role::findByName('recepcion', 'web')->revokePermissionTo('paciente.ver_sensibles');
        app(PermissionRegistrar::class)->forgetCachedPermissions();
        Sanctum::actingAs($recepcion);

        $this->getJson('/api/pacientes/pacientes/'.$created['id'])
            ->assertOk()
            ->assertJsonMissingPath('data.alergias')
            ->assertJsonMissingPath('data.condiciones_medicas')
            ->assertJsonPath('data.sensibles_protegidos', true);

        $this->patchJson('/api/pacientes/pacientes/'.$created['id'], [
            'alergias' => 'Otra alergia',
            'version' => $created['version'],
        ])->assertStatus(403)
            ->assertJsonPath('code', 'SENSITIVE_FIELDS_FORBIDDEN');
    }

    public function test_required_fields_are_validated(): void
    {
        $branch = $this->branch();
        $user = $this->actingAsRole('superadmin');
        $this->withBranch($user, $branch);

        $this->postJson('/api/pacientes/pacientes', [
            'sucursal_registro_id' => $branch->id,
        ])->assertStatus(422)
            ->assertJsonValidationErrors(['nombre', 'apellido_paterno', 'fecha_nacimiento', 'sexo', 'tutor_principal']);
    }
}
