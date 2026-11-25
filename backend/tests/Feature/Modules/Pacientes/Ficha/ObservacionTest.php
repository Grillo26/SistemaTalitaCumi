<?php

namespace Tests\Feature\Modules\Pacientes\Ficha;

use App\Models\User;
use App\Modules\Organizacion\Models\Branch;
use App\Modules\Pacientes\Enums\EstadoFichaIngreso;
use App\Modules\Pacientes\Models\BitacoraEntry;
use App\Modules\Pacientes\Models\FichaIngreso;
use App\Modules\Pacientes\Models\FichaIngresoObservacion;
use App\Modules\Pacientes\Models\Paciente;
use Laravel\Sanctum\Sanctum;
use PHPUnit\Framework\Attributes\Test;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;
use Tests\Feature\Modules\Pacientes\PacientesTestCase;

final class ObservacionTest extends PacientesTestCase
{
    #[Test]
    public function recepcion_creates_and_reads_observations_without_auditing_text(): void
    {
        $branch = $this->branch();
        $recepcion = $this->actingAsRole('recepcion');
        $this->withBranch($recepcion, $branch);
        $paciente = $this->paciente(['sucursal_registro_id' => $branch->id]);
        $ficha = $this->ficha($paciente, $branch, $recepcion);

        $this->getJson($this->base($paciente->id, $ficha->id))
            ->assertOk()
            ->assertJsonCount(0, 'data')
            ->assertJsonPath('can.observation_add', true);

        $observacion = $this->postJson($this->base($paciente->id, $ficha->id), [
            'fecha' => now()->toDateString(),
            'texto' => 'La familia comenta que el niño se adaptó bien.',
        ])->assertCreated()
            ->assertJsonPath('data.texto', 'La familia comenta que el niño se adaptó bien.')
            ->assertJsonPath('data.texto_protegido', false)
            ->json('data');

        $this->getJson($this->base($paciente->id, $ficha->id))
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.id', $observacion['id']);

        $entry = BitacoraEntry::query()
            ->where('entidad', 'ficha_ingreso_observacion')
            ->where('entidad_id', $observacion['id'])
            ->where('accion', 'CREAR')
            ->firstOrFail();

        $this->assertNull($entry->motivo);
        $this->assertTrue($entry->datos_nuevos['tiene_texto']);
        $this->assertArrayNotHasKey('texto', $entry->datos_nuevos);

        $consulta = BitacoraEntry::query()
            ->where('entidad', 'ficha_ingreso')
            ->where('entidad_id', $ficha->id)
            ->where('accion', 'CONSULTAR')
            ->firstOrFail();

        $this->assertSame(
            'ficha_ingreso_observaciones',
            $consulta->datos_nuevos['contexto']['recurso'],
        );
    }

    #[Test]
    public function observations_are_append_only_and_can_reference_a_previous_one(): void
    {
        $branch = $this->branch();
        $recepcion = $this->actingAsRole('recepcion');
        $this->withBranch($recepcion, $branch);
        $paciente = $this->paciente(['sucursal_registro_id' => $branch->id]);
        $ficha = $this->ficha($paciente, $branch, $recepcion);

        $primera = $this->postJson($this->base($paciente->id, $ficha->id), [
            'fecha' => now()->subDay()->toDateString(),
            'texto' => 'Primera observación.',
        ])->assertCreated()->json('data');

        $segunda = $this->postJson($this->base($paciente->id, $ficha->id), [
            'fecha' => now()->toDateString(),
            'texto' => 'Corrección: la primera observación tenía un error de fecha.',
            'observacion_referencia_id' => $primera['id'],
        ])->assertCreated()
            ->assertJsonPath('data.observacion_referencia_id', $primera['id'])
            ->assertJsonPath('data.referencia.id', $primera['id'])
            ->json('data');

        $this->getJson($this->base($paciente->id, $ficha->id))
            ->assertOk()
            ->assertJsonCount(2, 'data')
            ->assertJsonPath('data.0.id', $segunda['id'])
            ->assertJsonPath('data.1.id', $primera['id']);

        $this->deleteJson($this->base($paciente->id, $ficha->id).'/'.$primera['id'])
            ->assertStatus(409)
            ->assertJsonPath('code', 'OBSERVACION_DELETE_NOT_ALLOWED');
    }

    #[Test]
    public function reference_must_belong_to_the_same_ficha(): void
    {
        $branch = $this->branch();
        $recepcion = $this->actingAsRole('recepcion');
        $this->withBranch($recepcion, $branch);
        $pacienteA = $this->paciente(['sucursal_registro_id' => $branch->id]);
        $pacienteB = $this->paciente(['sucursal_registro_id' => $branch->id]);
        $fichaA = $this->ficha($pacienteA, $branch, $recepcion);
        $fichaB = $this->ficha($pacienteB, $branch, $recepcion);

        $ajena = $this->postJson($this->base($pacienteB->id, $fichaB->id), [
            'fecha' => now()->toDateString(),
            'texto' => 'Observación de otra ficha.',
        ])->assertCreated()->json('data');

        $this->postJson($this->base($pacienteA->id, $fichaA->id), [
            'fecha' => now()->toDateString(),
            'texto' => 'Intento de referencia cruzada.',
            'observacion_referencia_id' => $ajena['id'],
        ])->assertStatus(422)
            ->assertJsonPath('code', 'OBSERVACION_REFERENCE_INVALID')
            ->assertJsonValidationErrors(['observacion_referencia_id']);
    }

    #[Test]
    public function archived_ficha_blocks_observations(): void
    {
        $branch = $this->branch();
        $recepcion = $this->actingAsRole('recepcion');
        $this->withBranch($recepcion, $branch);
        $paciente = $this->paciente(['sucursal_registro_id' => $branch->id]);
        $ficha = $this->ficha($paciente, $branch, $recepcion);

        $ficha->forceFill([
            'estado' => EstadoFichaIngreso::ARCHIVADA->value,
            'estado_anterior' => EstadoFichaIngreso::BORRADOR->value,
            'archivada_en' => now(),
            'archivada_por_id' => $recepcion->id,
            'motivo_archivado' => 'Archivo de prueba.',
        ])->save();

        $this->postJson($this->base($paciente->id, $ficha->id), [
            'fecha' => now()->toDateString(),
            'texto' => 'No debe registrarse.',
        ])->assertStatus(409)
            ->assertJsonPath('code', 'FICHA_ARCHIVED');
    }

    #[Test]
    public function future_date_is_rejected(): void
    {
        $branch = $this->branch();
        $recepcion = $this->actingAsRole('recepcion');
        $this->withBranch($recepcion, $branch);
        $paciente = $this->paciente(['sucursal_registro_id' => $branch->id]);
        $ficha = $this->ficha($paciente, $branch, $recepcion);

        $this->postJson($this->base($paciente->id, $ficha->id), [
            'fecha' => now()->addDay()->toDateString(),
            'texto' => 'Fecha futura.',
        ])->assertStatus(422)
            ->assertJsonPath('code', 'OBSERVACION_DATE_INVALID')
            ->assertJsonValidationErrors(['fecha']);
    }

    #[Test]
    public function text_is_protected_without_ver_sensibles_and_creation_is_forbidden(): void
    {
        $branch = $this->branch();
        $superadmin = $this->actingAsSuperadmin();
        $paciente = $this->paciente(['sucursal_registro_id' => $branch->id]);
        $ficha = $this->ficha($paciente, $branch, $superadmin);

        $this->postJson($this->base($paciente->id, $ficha->id), [
            'fecha' => now()->toDateString(),
            'texto' => 'Texto sensible de prueba.',
        ])->assertCreated();

        $user = $this->actAsCustomRole($branch, [
            'ficha_ingreso.ver',
            'ficha_ingreso.observacion_ver',
            'ficha_ingreso.observacion_agregar',
        ]);

        $data = $this->getJson($this->base($paciente->id, $ficha->id))
            ->assertOk()
            ->json('data');

        $this->assertNull($data[0]['texto']);
        $this->assertTrue($data[0]['texto_protegido']);

        $this->postJson($this->base($paciente->id, $ficha->id), [
            'fecha' => now()->toDateString(),
            'texto' => 'No autorizado.',
        ])->assertStatus(403)
            ->assertJsonPath('code', 'FICHA_SENSITIVE_FIELDS_FORBIDDEN');

        $this->assertNotSame($superadmin->id, $user->id);
    }

    #[Test]
    public function scope_hides_ficha_from_another_branch(): void
    {
        $branchA = $this->branch();
        $branchB = $this->branch();
        $superadmin = $this->actingAsSuperadmin();
        $paciente = $this->paciente(['sucursal_registro_id' => $branchA->id]);
        $ficha = $this->ficha($paciente, $branchB, $superadmin);

        $recepcion = $this->actingAsRole('recepcion');
        $this->withBranch($recepcion, $branchA);

        $this->getJson($this->base($paciente->id, $ficha->id))
            ->assertStatus(404)
            ->assertJsonPath('code', 'FICHA_NOT_FOUND');
    }

    #[Test]
    public function observation_model_is_immutable(): void
    {
        $observacion = FichaIngresoObservacion::factory()->create();

        try {
            $observacion->update(['texto' => 'No debe actualizarse.']);
            $this->fail('La observación debía rechazar la edición.');
        } catch (\LogicException $exception) {
            $this->assertStringContainsString('inmutables', $exception->getMessage());
        }

        try {
            $observacion->delete();
            $this->fail('La observación debía rechazar la eliminación.');
        } catch (\LogicException $exception) {
            $this->assertStringContainsString('no se eliminan', $exception->getMessage());
        }
    }

    private function base(int $pacienteId, int $fichaId): string
    {
        return '/api/pacientes/pacientes/'.$pacienteId.'/ficha-ingreso/'.$fichaId.'/observaciones';
    }

    private function ficha(Paciente $paciente, Branch $branch, User $actor): FichaIngreso
    {
        return FichaIngreso::factory()->create([
            'paciente_id' => $paciente->id,
            'sucursal_ingreso_id' => $branch->id,
            'usuario_registro_id' => $actor->id,
        ]);
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

        $role = Role::findOrCreate('rol_observacion_test', 'web');
        $role->syncPermissions($permissions);

        $user = User::factory()->create();
        $user->assignRole($role);
        $user->branches()->syncWithoutDetaching([$branch->id]);
        Sanctum::actingAs($user);

        return $user;
    }
}
