<?php

namespace Tests\Feature\Modules\Pacientes\Registro;

use App\Models\User;
use App\Modules\Organizacion\Models\Branch;
use App\Modules\Pacientes\Models\Paciente;
use App\Modules\Pacientes\Models\Parentesco;
use App\Modules\Pacientes\Models\Tutor;
use Laravel\Sanctum\Sanctum;
use PHPUnit\Framework\Attributes\Test;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;
use Tests\Feature\Modules\Pacientes\PacientesTestCase;

final class PacienteSearchTest extends PacientesTestCase
{
    #[Test]
    public function search_finds_by_name_apellido_and_document_without_accents_or_case(): void
    {
        $branch = $this->branch();
        $recepcion = $this->actingAsRole('recepcion');
        $this->withBranch($recepcion, $branch);

        $paciente = $this->pacienteConTutor($branch, [
            'nombre' => 'María Fernanda',
            'apellido_paterno' => 'López',
            'apellido_materno' => 'Quispe',
            'ci' => '81234567',
        ]);
        $this->pacienteConTutor($branch, [
            'nombre' => 'José',
            'apellido_paterno' => 'Pérez',
            'apellido_materno' => 'Mamani',
            'ci' => '82345678',
        ]);

        $this->getJson('/api/pacientes/pacientes?search=MARIA')
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.id', $paciente->id);

        $this->getJson('/api/pacientes/pacientes?search=lopez')
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.id', $paciente->id);

        $this->getJson('/api/pacientes/pacientes?search=81234567')
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.id', $paciente->id);
    }

    #[Test]
    public function search_finds_by_principal_active_tutor_name(): void
    {
        $branch = $this->branch();
        $recepcion = $this->actingAsRole('recepcion');
        $this->withBranch($recepcion, $branch);

        $paciente = $this->pacienteConTutor($branch, [
            'nombre' => 'Ana',
            'apellido_paterno' => 'Rojas',
        ], [
            'nombre_completo' => 'Rosa Elena Gonzáles',
        ]);

        $this->getJson('/api/pacientes/pacientes?search=rosa gonzales')
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.id', $paciente->id);
    }

    #[Test]
    public function non_principal_or_inactive_tutor_does_not_match(): void
    {
        $branch = $this->branch();
        $recepcion = $this->actingAsRole('recepcion');
        $this->withBranch($recepcion, $branch);

        $paciente = $this->pacienteConTutor($branch, [
            'nombre' => 'Luis',
            'apellido_paterno' => 'Vargas',
        ], [
            'nombre_completo' => 'Tutor Principal',
        ]);

        $secundario = $this->tutor([
            'sucursal_registro_id' => $branch->id,
            'nombre_completo' => 'Tutor Secundario',
            'telefono' => '76543210',
            'parentesco_id' => Parentesco::query()->value('id'),
        ]);
        $paciente->tutores()->attach($secundario->id, [
            'parentesco_id' => $secundario->parentesco_id,
            'es_principal' => false,
        ]);

        $this->getJson('/api/pacientes/pacientes?search=tutor secundario')
            ->assertOk()
            ->assertJsonCount(0, 'data');

        $principal = Tutor::query()->where('nombre_completo', 'Tutor Principal')->firstOrFail();
        $principal->update(['activo' => false]);

        $this->getJson('/api/pacientes/pacientes?search=tutor principal')
            ->assertOk()
            ->assertJsonCount(0, 'data');
    }

    #[Test]
    public function phone_search_requires_tutor_view_contact_permission(): void
    {
        $branch = $this->branch();
        $paciente = $this->pacienteConTutor($branch, [
            'nombre' => 'Teléfono',
            'apellido_paterno' => 'Prueba',
        ], [
            'telefono' => '71234567',
        ]);

        $recepcion = $this->actingAsRole('recepcion');
        $this->withBranch($recepcion, $branch);

        $this->getJson('/api/pacientes/pacientes?search=71234567')
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.id', $paciente->id)
            ->assertJsonPath('can.search_by_tutor_phone', true);

        $this->actAsCustomRole($branch, ['paciente.ver']);

        $this->getJson('/api/pacientes/pacientes?search=71234567')
            ->assertOk()
            ->assertJsonCount(0, 'data')
            ->assertJsonPath('can.search_by_tutor_phone', false);
    }

    #[Test]
    public function sort_by_apellido_and_scope_are_stable(): void
    {
        $branchA = $this->branch();
        $branchB = $this->branch();
        $recepcion = $this->actingAsRole('recepcion');
        $this->withBranch($recepcion, $branchA);

        $zurita = $this->pacienteConTutor($branchA, [
            'nombre' => 'Ana',
            'apellido_paterno' => 'Zurita',
        ]);
        $aguilar = $this->pacienteConTutor($branchA, [
            'nombre' => 'Beto',
            'apellido_paterno' => 'Aguilar',
        ]);
        $this->pacienteConTutor($branchB, [
            'nombre' => 'Carla',
            'apellido_paterno' => 'Aarón',
        ]);

        $this->getJson('/api/pacientes/pacientes?sort=apellido&direction=asc')
            ->assertOk()
            ->assertJsonCount(2, 'data')
            ->assertJsonPath('data.0.id', $aguilar->id)
            ->assertJsonPath('data.1.id', $zurita->id);
    }

    #[Test]
    public function filters_and_minimum_search_length_are_validated(): void
    {
        $branch = $this->branch();
        $recepcion = $this->actingAsRole('recepcion');
        $this->withBranch($recepcion, $branch);

        $this->getJson('/api/pacientes/pacientes?search=a')
            ->assertStatus(422)
            ->assertJsonValidationErrors(['search']);

        foreach (['sucursal_inscripcion_id' => 1, 'tipo_servicio_id' => 1, 'estado_pago' => 'al_dia'] as $field => $value) {
            $this->getJson('/api/pacientes/pacientes?'.$field.'='.$value)
                ->assertStatus(422)
                ->assertJsonValidationErrors([$field]);
        }

        $this->getJson('/api/pacientes/pacientes')
            ->assertOk()
            ->assertJsonPath('can.view_resumen', true)
            ->assertJsonPath('can.search_by_tutor', true)
            ->assertJsonPath('can.export_listado', false);
    }

    private function pacienteConTutor(
        Branch $branch,
        array $pacienteOverrides = [],
        array $tutorOverrides = [],
    ): Paciente {
        $paciente = $this->paciente(array_merge([
            'sucursal_registro_id' => $branch->id,
        ], $pacienteOverrides));

        $tutor = $this->tutor(array_merge([
            'sucursal_registro_id' => $branch->id,
            'parentesco_id' => Parentesco::query()->value('id'),
        ], $tutorOverrides));

        $paciente->tutores()->attach($tutor->id, [
            'parentesco_id' => $tutor->parentesco_id,
            'es_principal' => true,
        ]);

        return $paciente;
    }

    /**
     * @param  list<string>  $permissions
     */
    private function actAsCustomRole(Branch $branch, array $permissions): User
    {
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        foreach ($permissions as $permission) {
            Permission::findOrCreate($permission, 'web');
        }

        $role = Role::findOrCreate('rol_busqueda_paciente_test', 'web');
        $role->syncPermissions($permissions);

        $user = User::factory()->create();
        $user->assignRole($role);
        $user->branches()->syncWithoutDetaching([$branch->id]);
        Sanctum::actingAs($user);

        return $user;
    }
}
