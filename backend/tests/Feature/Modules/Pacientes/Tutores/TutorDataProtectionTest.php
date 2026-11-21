<?php

namespace Tests\Feature\Modules\Pacientes\Tutores;

use App\Models\User;
use App\Modules\Pacientes\Models\Parentesco;
use Laravel\Sanctum\Sanctum;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;
use Tests\Feature\Modules\Pacientes\PacientesTestCase;

final class TutorDataProtectionTest extends PacientesTestCase
{
    public function test_list_masks_contact_without_the_specific_permission(): void
    {
        $branch = $this->branch();
        $superadmin = $this->actingAsSuperadmin();
        $this->withBranch($superadmin, $branch);

        $tutor = $this->createTutor($branch->id, [
            'telefono' => '71234567',
            'telefono_alternativo' => '21234567',
            'email' => 'ana@example.test',
            'direccion' => 'Av. Siempre Viva 123',
        ]);

        $this->actAsTutorViewer($branch->id);

        $this->getJson('/api/pacientes/tutores')
            ->assertOk()
            ->assertJsonPath('data.0.telefono', '712***67')
            ->assertJsonPath('data.0.telefono_alternativo', '212***67')
            ->assertJsonPath('data.0.email', null)
            ->assertJsonPath('data.0.direccion', null)
            ->assertJsonPath('data.0.can.view_contact', false);

        $this->getJson('/api/pacientes/tutores/'.$tutor['id'])
            ->assertOk()
            ->assertJsonPath('data.telefono', '712***67')
            ->assertJsonPath('data.email', null)
            ->assertJsonPath('data.direccion', null)
            ->assertJsonPath('data.can.view_contact', false);
    }

    public function test_authorized_roles_see_full_contact_in_list_and_ficha(): void
    {
        $branch = $this->branch();
        $superadmin = $this->actingAsSuperadmin();
        $this->withBranch($superadmin, $branch);

        $tutor = $this->createTutor($branch->id, [
            'telefono' => '71234567',
            'telefono_alternativo' => '21234567',
            'email' => 'ana@example.test',
            'direccion' => 'Av. Siempre Viva 123',
        ]);

        $admin = $this->actingAsRole('administracion');
        $this->withBranch($admin, $branch);

        $this->getJson('/api/pacientes/tutores')
            ->assertOk()
            ->assertJsonPath('data.0.telefono', '71234567')
            ->assertJsonPath('data.0.can.view_contact', true);

        $this->getJson('/api/pacientes/tutores/'.$tutor['id'])
            ->assertOk()
            ->assertJsonPath('data.telefono', '71234567')
            ->assertJsonPath('data.telefono_alternativo', '21234567')
            ->assertJsonPath('data.email', 'ana@example.test')
            ->assertJsonPath('data.direccion', 'Av. Siempre Viva 123');
    }

    public function test_duplicate_document_and_name_phone_are_rejected(): void
    {
        $branch = $this->branch();
        $this->actingAsSuperadmin();

        $this->createTutor($branch->id, [
            'nombre_completo' => 'Ana López',
            'ci' => 'ABC-1',
            'telefono' => '71234567',
        ]);

        $this->postJson('/api/pacientes/tutores', [
            'nombre_completo' => 'Otra Persona',
            'ci' => ' abc-1 ',
            'telefono' => '70000000',
            'parentesco_id' => $this->parentescoId('Tía'),
            'sucursal_registro_id' => $branch->id,
        ])->assertStatus(409)
            ->assertJsonPath('code', 'TUTOR_DUPLICATE')
            ->assertJsonPath('data.coincide_documento', true)
            ->assertJsonPath('data.puede_asociar', true)
            ->assertJsonPath('data.tutores_en_alcance.0.nombre_completo', 'Ana López');

        $this->postJson('/api/pacientes/tutores', [
            'nombre_completo' => 'ANA   LOPEZ',
            'ci' => '99999999',
            'telefono' => '591 712-345-67',
            'parentesco_id' => $this->parentescoId('Madre'),
            'sucursal_registro_id' => $branch->id,
        ])->assertStatus(409)
            ->assertJsonPath('code', 'TUTOR_DUPLICATE')
            ->assertJsonPath('data.coincide_nombre_telefono', true);
    }

    public function test_duplicate_outside_scope_does_not_leak_personal_data(): void
    {
        $branchA = $this->branch();
        $branchB = $this->branch();

        $superadmin = $this->actingAsSuperadmin();
        $this->withBranch($superadmin, $branchA);
        $this->createTutor($branchB->id, [
            'nombre_completo' => 'Tutor Secreto',
            'ci' => '12345678',
            'telefono' => '70009999',
        ]);

        $recepcion = $this->actingAsRole('recepcion');
        $this->withBranch($recepcion, $branchA);

        $this->postJson('/api/pacientes/tutores', [
            'nombre_completo' => 'Otro Tutor',
            'ci' => '12345678',
            'telefono' => '71234567',
            'parentesco_id' => $this->parentescoId('Madre'),
            'sucursal_registro_id' => $branchA->id,
        ])->assertStatus(409)
            ->assertJsonPath('code', 'TUTOR_DUPLICATE')
            ->assertJsonPath('data.fuera_de_alcance', true)
            ->assertJsonPath('data.puede_asociar', false)
            ->assertJsonCount(0, 'data.tutores_en_alcance')
            ->assertJsonMissing(['nombre_completo' => 'Tutor Secreto']);
    }

    public function test_update_does_not_conflict_with_itself_and_keeps_version(): void
    {
        $branch = $this->branch();
        $this->actingAsSuperadmin();

        $tutor = $this->createTutor($branch->id);

        $this->patchJson('/api/pacientes/tutores/'.$tutor['id'], [
            'email' => 'nuevo@example.test',
            'version' => 1,
        ])->assertOk()
            ->assertJsonPath('data.email', 'nuevo@example.test')
            ->assertJsonPath('data.version', 2);

        $this->patchJson('/api/pacientes/tutores/'.$tutor['id'], [
            'email' => 'nuevo@example.test',
            'version' => 2,
        ])->assertOk()
            ->assertJsonPath('data.version', 2);
    }

    private function actAsTutorViewer(int $branchId): User
    {
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        Permission::findOrCreate('tutor.ver', 'web');
        $role = Role::findOrCreate('consulta_tutores', 'web');
        $role->syncPermissions(['tutor.ver']);

        $user = User::factory()->create();
        $user->assignRole($role);
        $user->branches()->syncWithoutDetaching([$branchId]);

        Sanctum::actingAs($user);

        return $user;
    }

    private function parentescoId(string $name): int
    {
        return (int) Parentesco::query()->where('nombre', $name)->value('id');
    }

    /**
     * @param  array<string, mixed>  $overrides
     * @return array<string, mixed>
     */
    private function createTutor(int $branchId, array $overrides = []): array
    {
        return $this->postJson('/api/pacientes/tutores', array_merge([
            'nombre_completo' => 'Ana López',
            'ci' => '55555555',
            'telefono' => '71234567',
            'parentesco_id' => $this->parentescoId('Madre'),
            'sucursal_registro_id' => $branchId,
        ], $overrides))->assertCreated()->json('data');
    }
}
