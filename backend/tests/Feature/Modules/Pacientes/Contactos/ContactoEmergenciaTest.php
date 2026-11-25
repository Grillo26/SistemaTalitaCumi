<?php

namespace Tests\Feature\Modules\Pacientes\Contactos;

use App\Models\User;
use App\Modules\Organizacion\Models\Branch;
use App\Modules\Pacientes\Models\BitacoraEntry;
use App\Modules\Pacientes\Models\Parentesco;
use Laravel\Sanctum\Sanctum;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;
use Tests\Feature\Modules\Pacientes\PacientesTestCase;

final class ContactoEmergenciaTest extends PacientesTestCase
{
    public function test_recepcion_creates_lists_updates_and_deletes_contact(): void
    {
        $branch = $this->branch();
        $user = $this->actingAsRole('recepcion');
        $this->withBranch($user, $branch);

        $paciente = $this->createPaciente($branch->id);

        $contacto = $this->postJson($this->baseUrl($paciente['id']), [
            'nombre_completo' => ' Rosa Quispe ',
            'parentesco_id' => $this->parentescoId('Tía'),
            'telefono' => '+591 712-345-67',
        ])->assertCreated()
            ->assertJsonPath('data.nombre_completo', 'Rosa Quispe')
            ->assertJsonPath('data.parentesco', 'Tía')
            ->assertJsonPath('data.telefono', '71234567')
            ->assertJsonPath('data.version', 1)
            ->json('data');

        $this->getJson($this->baseUrl($paciente['id']))
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.id', $contacto['id']);

        $this->getJson('/api/pacientes/pacientes/'.$paciente['id'])
            ->assertOk()
            ->assertJsonPath('data.contactos_emergencia.0.id', $contacto['id'])
            ->assertJsonPath('data.can.view_emergency_contacts', true)
            ->assertJsonPath('data.can.manage_emergency_contacts', true);

        $this->patchJson($this->baseUrl($paciente['id']).'/'.$contacto['id'], [
            'nombre_completo' => 'Rosa Mamani',
            'telefono' => '21234567',
            'parentesco_id' => $this->parentescoId('Vecino/a'),
            'version' => 1,
        ])->assertOk()
            ->assertJsonPath('data.nombre_completo', 'Rosa Mamani')
            ->assertJsonPath('data.telefono', '21234567')
            ->assertJsonPath('data.parentesco', 'Vecino/a')
            ->assertJsonPath('data.version', 2);

        $this->deleteJson($this->baseUrl($paciente['id']).'/'.$contacto['id'])
            ->assertOk();

        $this->assertDatabaseMissing('contacto_emergencia', ['id' => $contacto['id']]);
    }

    public function test_contact_requires_active_parentesco_and_valid_phone(): void
    {
        $branch = $this->branch();
        $user = $this->actingAsRole('recepcion');
        $this->withBranch($user, $branch);

        $paciente = $this->createPaciente($branch->id);
        $inactive = Parentesco::factory()->create([
            'nombre' => 'Primo',
            'activo' => false,
        ]);

        $this->postJson($this->baseUrl($paciente['id']), [
            'nombre_completo' => 'Contacto Inválido',
            'parentesco_id' => $inactive->id,
            'telefono' => '71234567',
        ])->assertStatus(422)
            ->assertJsonValidationErrors(['parentesco_id']);

        $this->postJson($this->baseUrl($paciente['id']), [
            'nombre_completo' => 'Contacto Inválido',
            'parentesco_id' => $this->parentescoId('Tía'),
            'telefono' => '11234567',
        ])->assertStatus(422)
            ->assertJsonValidationErrors(['telefono']);
    }

    public function test_administracion_can_manage_contacts(): void
    {
        $branch = $this->branch();
        $this->actingAsSuperadmin();
        $paciente = $this->createPaciente($branch->id);

        $admin = $this->actingAsRole('administracion');
        $this->withBranch($admin, $branch);

        $contacto = $this->postJson($this->baseUrl($paciente['id']), [
            'nombre_completo' => 'Contacto Admin',
            'parentesco_id' => $this->parentescoId('Madrina'),
            'telefono' => '70001111',
        ])->assertCreated()->json('data');

        $this->patchJson($this->baseUrl($paciente['id']).'/'.$contacto['id'], [
            'telefono' => '70002222',
            'version' => 1,
        ])->assertOk()
            ->assertJsonPath('data.telefono', '70002222');

        $this->deleteJson($this->baseUrl($paciente['id']).'/'.$contacto['id'])
            ->assertOk();
    }

    public function test_role_without_contact_permission_cannot_see_or_manage_contacts(): void
    {
        $branch = $this->branch();
        $this->actingAsSuperadmin();
        $paciente = $this->createPaciente($branch->id);

        $contacto = $this->postJson($this->baseUrl($paciente['id']), [
            'nombre_completo' => 'Contacto Protegido',
            'parentesco_id' => $this->parentescoId('Tía'),
            'telefono' => '71234567',
        ])->assertCreated()->json('data');

        $this->actAsPatientViewer($branch->id);

        $this->getJson('/api/pacientes/pacientes/'.$paciente['id'])
            ->assertOk()
            ->assertJsonPath('data.contactos_emergencia', [])
            ->assertJsonPath('data.can.view_emergency_contacts', false)
            ->assertJsonPath('data.can.manage_emergency_contacts', false);

        $this->getJson($this->baseUrl($paciente['id']))->assertStatus(403);

        $this->postJson($this->baseUrl($paciente['id']), [
            'nombre_completo' => 'No permitido',
            'parentesco_id' => $this->parentescoId('Tía'),
            'telefono' => '71234567',
        ])->assertStatus(403);

        $this->deleteJson($this->baseUrl($paciente['id']).'/'.$contacto['id'])
            ->assertStatus(403);
    }

    public function test_scope_blocks_contacts_of_patient_from_another_branch(): void
    {
        $branchA = $this->branch();
        $branchB = $this->branch();

        $this->actingAsSuperadmin();
        $pacienteB = $this->createPaciente($branchB->id);

        $recepcion = $this->actingAsRole('recepcion');
        $this->withBranch($recepcion, $branchA);

        $this->getJson($this->baseUrl($pacienteB['id']))->assertStatus(404);

        $this->postJson($this->baseUrl($pacienteB['id']), [
            'nombre_completo' => 'Contacto Ajeno',
            'parentesco_id' => $this->parentescoId('Tía'),
            'telefono' => '71234567',
        ])->assertStatus(404);
    }

    public function test_contact_changes_are_audited_with_full_values_and_patient_branch(): void
    {
        $branch = $this->branch();
        $this->actingAsSuperadmin();
        $paciente = $this->createPaciente($branch->id);

        $contacto = $this->postJson($this->baseUrl($paciente['id']), [
            'nombre_completo' => 'Rosa Quispe',
            'parentesco_id' => $this->parentescoId('Tía'),
            'telefono' => '71234567',
        ])->assertCreated()->json('data');

        $created = BitacoraEntry::query()
            ->where('entidad', 'contacto_emergencia')
            ->where('entidad_id', $contacto['id'])
            ->where('accion', 'CREAR')
            ->firstOrFail();

        $this->assertSame($branch->id, $created->sucursal_id);
        $this->assertSame('Rosa Quispe', $created->datos_nuevos['nombre_completo']);
        $this->assertSame('71234567', $created->datos_nuevos['telefono']);
        $this->assertSame('Tía', $created->datos_nuevos['parentesco']);

        $this->patchJson($this->baseUrl($paciente['id']).'/'.$contacto['id'], [
            'nombre_completo' => 'Rosa Mamani',
            'telefono' => '70001111',
            'version' => 1,
        ])->assertOk();

        $updated = BitacoraEntry::query()
            ->where('entidad', 'contacto_emergencia')
            ->where('entidad_id', $contacto['id'])
            ->where('accion', 'ACTUALIZAR')
            ->firstOrFail();

        $this->assertSame('Rosa Quispe', $updated->datos_anteriores['nombre_completo']);
        $this->assertSame('Rosa Mamani', $updated->datos_nuevos['nombre_completo']);
        $this->assertSame('70001111', $updated->datos_nuevos['telefono']);
        $this->assertContains('telefono', $updated->datos_nuevos['campos_modificados']);

        $this->deleteJson($this->baseUrl($paciente['id']).'/'.$contacto['id'])
            ->assertOk();

        $deleted = BitacoraEntry::query()
            ->where('entidad', 'contacto_emergencia')
            ->where('entidad_id', $contacto['id'])
            ->where('accion', 'ELIMINAR')
            ->firstOrFail();

        $this->assertSame('Rosa Mamani', $deleted->datos_anteriores['nombre_completo']);
        $this->assertSame('70001111', $deleted->datos_anteriores['telefono']);
        $this->assertNull($deleted->datos_nuevos);
    }

    public function test_update_requires_current_version_and_contact_belongs_to_patient(): void
    {
        $branch = $this->branch();
        $this->actingAsSuperadmin();
        $paciente = $this->createPaciente($branch->id);
        $otroPaciente = $this->paciente(['sucursal_registro_id' => $branch->id]);

        $contacto = $this->postJson($this->baseUrl($paciente['id']), [
            'nombre_completo' => 'Rosa Quispe',
            'parentesco_id' => $this->parentescoId('Tía'),
            'telefono' => '71234567',
        ])->assertCreated()->json('data');

        $this->patchJson($this->baseUrl($paciente['id']).'/'.$contacto['id'], [
            'telefono' => '70001111',
            'version' => 99,
        ])->assertStatus(409)
            ->assertJsonPath('code', 'CONTACT_EMERGENCY_VERSION_CONFLICT');

        $this->patchJson($this->baseUrl($otroPaciente->id).'/'.$contacto['id'], [
            'telefono' => '70001111',
            'version' => 1,
        ])->assertStatus(404)
            ->assertJsonPath('code', 'CONTACT_EMERGENCY_NOT_FOUND');
    }

    private function baseUrl(int $pacienteId): string
    {
        return '/api/pacientes/pacientes/'.$pacienteId.'/contactos-emergencia';
    }

    /**
     * @return array<string, mixed>
     */
    private function createPaciente(int $branchId): array
    {
        $branch = Branch::query()->findOrFail($branchId);

        return $this->postJson('/api/pacientes/pacientes', $this->pacientePayload([], $branch))
            ->assertCreated()
            ->json('data');
    }

    private function parentescoId(string $name): int
    {
        return (int) Parentesco::query()->where('nombre', $name)->value('id');
    }

    private function actAsPatientViewer(int $branchId): User
    {
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        Permission::findOrCreate('paciente.ver', 'web');
        $role = Role::findOrCreate('consulta_pacientes', 'web');
        $role->syncPermissions(['paciente.ver']);

        $user = User::factory()->create();
        $user->assignRole($role);
        $user->branches()->syncWithoutDetaching([$branchId]);

        Sanctum::actingAs($user);

        return $user;
    }
}
