<?php

namespace Tests\Feature\Modules\Pacientes\Ficha;

use App\Models\User;
use App\Modules\Organizacion\Models\Branch;
use App\Modules\Pacientes\Contracts\EntrevistaDirectoryContract;
use App\Modules\Pacientes\Contracts\FichaIngresoDirectoryContract;
use App\Modules\Pacientes\Contracts\FichaIngresoExporterContract;
use App\Modules\Pacientes\Models\BitacoraEntry;
use App\Modules\Seguridad\Contracts\AuditRecorderContract;
use Laravel\Sanctum\Sanctum;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;
use Tests\Feature\Modules\Pacientes\PacientesTestCase;
use Tests\Support\ThrowingAuditRecorder;

final class FichaIngresoTest extends PacientesTestCase
{
    public function test_recepcion_creates_reads_updates_and_completes_ficha(): void
    {
        $branch = $this->branch();
        $recepcion = $this->actingAsRole('recepcion');
        $this->withBranch($recepcion, $branch);
        $paciente = $this->paciente(['sucursal_registro_id' => $branch->id]);

        $ficha = $this->createFicha($paciente->id, $branch->id);

        $this->assertSame('borrador', $ficha['estado']);
        $this->assertSame(1, $ficha['version']);
        $this->assertSame('Motivo inicial de consulta.', $ficha['motivo_consulta_inicial']);
        $this->assertSame('Composición familiar inicial.', $ficha['composicion_familiar']);
        $this->assertSame('Antecedente referido por la familia.', $ficha['antecedentes_familiares']);
        $this->assertSame('Observación inicial.', $ficha['observaciones']);
        $this->assertSame('Dra. Referente', $ficha['derivado_por']);
        $this->assertSame('Centro de salud', $ficha['derivado_por_institucion']);
        $this->assertSame('70001111', $ficha['derivado_por_contacto']);
        $this->assertFalse($ficha['sensibles_protegidos']);

        $this->getJson($this->base($paciente->id))
            ->assertOk()
            ->assertJsonPath('data.id', $ficha['id'])
            ->assertJsonPath('historial', [])
            ->assertJsonPath('data.entrevista_disponible', false)
            ->assertJsonPath('paciente.id', $paciente->id);

        $updated = $this->patchJson($this->base($paciente->id).'/'.$ficha['id'], [
            'motivo_consulta_inicial' => 'Motivo actualizado.',
            'antecedentes_familiares' => null,
            'derivado_por' => 'Lic. Nueva Referente',
            'version' => 1,
        ])->assertOk()->json('data');

        $this->assertSame('Motivo actualizado.', $updated['motivo_consulta_inicial']);
        $this->assertNull($updated['antecedentes_familiares']);
        $this->assertSame(2, $updated['version']);

        $completed = $this->postJson($this->base($paciente->id).'/'.$ficha['id'].'/completar', [
            'version' => 2,
        ])->assertOk()->json('data');

        $this->assertSame('completa', $completed['estado']);
        $this->assertNotNull($completed['completada_en']);
        $this->assertSame(3, $completed['version']);
    }

    public function test_second_open_ficha_is_rejected_and_reingreso_requires_archive(): void
    {
        $branch = $this->branch();
        $recepcion = $this->actingAsRole('recepcion');
        $this->withBranch($recepcion, $branch);
        $paciente = $this->paciente(['sucursal_registro_id' => $branch->id]);
        $ficha = $this->createFicha($paciente->id, $branch->id);

        $this->postJson($this->base($paciente->id), $this->payload($branch->id))
            ->assertStatus(409)
            ->assertJsonPath('code', 'FICHA_ALREADY_EXISTS');

        $admin = $this->actingAsRole('administracion');
        $this->withBranch($admin, $branch);

        $this->postJson($this->base($paciente->id).'/'.$ficha['id'].'/archivar', [
            'motivo' => 'Reingreso: se archiva la ficha anterior.',
            'version' => 1,
        ])->assertOk()
            ->assertJsonPath('data.estado', 'archivada');

        $recepcion = $this->actingAsRole('recepcion');
        $this->withBranch($recepcion, $branch);
        $nueva = $this->createFicha($paciente->id, $branch->id);

        $this->getJson($this->base($paciente->id))
            ->assertOk()
            ->assertJsonPath('data.id', $nueva['id'])
            ->assertJsonCount(1, 'historial')
            ->assertJsonPath('historial.0.id', $ficha['id']);

        $this->getJson($this->base($paciente->id).'/'.$ficha['id'])
            ->assertOk()
            ->assertJsonPath('data.estado', 'archivada');

        $this->patchJson($this->base($paciente->id).'/'.$ficha['id'], [
            'motivo_consulta_inicial' => 'No debe editarse.',
            'version' => 2,
        ])->assertStatus(409)
            ->assertJsonPath('code', 'FICHA_ARCHIVED');

        $this->deleteJson($this->base($paciente->id).'/'.$nueva['id'])
            ->assertStatus(409)
            ->assertJsonPath('code', 'FICHA_DELETE_NOT_ALLOWED');

        $admin = $this->actingAsRole('administracion');
        $this->withBranch($admin, $branch);

        $this->postJson($this->base($paciente->id).'/'.$nueva['id'].'/archivar', [
            'motivo' => 'Se archiva para probar reactivación.',
            'version' => 1,
        ])->assertOk();

        $recepcion = $this->actingAsRole('recepcion');
        $this->withBranch($recepcion, $branch);

        $this->postJson($this->base($paciente->id).'/'.$ficha['id'].'/reactivar', [
            'motivo' => 'Corrección del archivo anterior.',
            'version' => 2,
        ])->assertStatus(403);

        $superadmin = $this->actingAsSuperadmin();
        $this->withBranch($superadmin, $branch);

        $reactivada = $this->postJson($this->base($paciente->id).'/'.$ficha['id'].'/reactivar', [
            'motivo' => 'Corrección del archivo anterior.',
            'version' => 2,
        ])->assertOk()->json('data');

        $this->assertSame('borrador', $reactivada['estado']);
        $this->assertSame(3, $reactivada['version']);

        $this->getJson($this->base($paciente->id))
            ->assertOk()
            ->assertJsonPath('data.id', $ficha['id']);
    }

    public function test_sensitive_fields_are_omitted_without_permission(): void
    {
        $branch = $this->branch();
        $superadmin = $this->actingAsSuperadmin();
        $this->withBranch($superadmin, $branch);
        $paciente = $this->paciente(['sucursal_registro_id' => $branch->id]);
        $ficha = $this->createFicha($paciente->id, $branch->id);

        $this->actAsCustomRole($branch, [
            'ficha_ingreso.ver',
            'ficha_ingreso.editar',
        ]);

        $data = $this->getJson($this->base($paciente->id))
            ->assertOk()
            ->json('data');

        $this->assertTrue($data['sensibles_protegidos']);
        $this->assertFalse($data['can']['view_sensibles']);
        $this->assertArrayNotHasKey('motivo_consulta_inicial', $data);
        $this->assertArrayNotHasKey('antecedentes_familiares', $data);
        $this->assertArrayNotHasKey('observaciones', $data);
        $this->assertArrayNotHasKey('derivado_por_contacto', $data);
        $this->assertContains('antecedentes_familiares', $data['campos_protegidos']);

        $revisiones = $this->getJson($this->base($paciente->id).'/'.$ficha['id'].'/revisiones')
            ->assertOk()
            ->json('data');

        $this->assertTrue($revisiones[0]['contenido_protegido']);
        $this->assertNull($revisiones[0]['snapshot']);

        $this->patchJson($this->base($paciente->id).'/'.$ficha['id'], [
            'antecedentes_familiares' => 'Intento no autorizado.',
            'version' => 1,
        ])->assertStatus(403)
            ->assertJsonPath('code', 'FICHA_SENSITIVE_FIELDS_FORBIDDEN');

        $this->patchJson($this->base($paciente->id).'/'.$ficha['id'], [
            'derivado_por' => 'Referente actualizado',
            'version' => 1,
        ])->assertOk()
            ->assertJsonPath('data.derivado_por', 'Referente actualizado');
    }

    public function test_scope_blocks_ficha_of_another_branch_and_creation_in_foreign_branch(): void
    {
        $branchA = $this->branch();
        $branchB = $this->branch();

        $superadmin = $this->actingAsSuperadmin();
        $this->withBranch($superadmin, $branchA);
        $paciente = $this->paciente(['sucursal_registro_id' => $branchA->id]);
        $ficha = $this->createFicha($paciente->id, $branchB->id);

        $recepcion = $this->actingAsRole('recepcion');
        $this->withBranch($recepcion, $branchA);

        $this->getJson($this->base($paciente->id).'/'.$ficha['id'])
            ->assertStatus(404)
            ->assertJsonPath('code', 'FICHA_NOT_FOUND');

        $this->postJson($this->base($paciente->id), $this->payload($branchB->id))
            ->assertStatus(403);

        $sinSucursal = $this->actingAsRole('recepcion');

        $this->getJson($this->base($paciente->id))->assertStatus(404);
        $this->assertNotSame($recepcion->id, $sinSucursal->id);
    }

    public function test_audit_does_not_copy_sensitive_content_and_consultation_is_fail_closed(): void
    {
        $branchA = $this->branch();
        $branchB = $this->branch();
        $superadmin = $this->actingAsSuperadmin();
        $paciente = $this->paciente(['sucursal_registro_id' => $branchA->id]);
        $ficha = $this->createFicha($paciente->id, $branchB->id);

        $creates = BitacoraEntry::query()
            ->where('entidad', 'ficha_ingreso')
            ->where('entidad_id', $ficha['id'])
            ->where('accion', 'CREAR')
            ->orderBy('sucursal_id')
            ->get();

        $this->assertCount(2, $creates);
        $this->assertSame([$branchA->id, $branchB->id], $creates->pluck('sucursal_id')->all());
        $this->assertTrue($creates[0]->datos_nuevos['tiene_motivo_consulta']);
        $this->assertTrue($creates[0]->datos_nuevos['tiene_antecedentes']);
        $this->assertArrayNotHasKey('motivo_consulta_inicial', $creates[0]->datos_nuevos);
        $this->assertArrayNotHasKey('antecedentes_familiares', $creates[0]->datos_nuevos);
        $this->assertArrayNotHasKey('observaciones', $creates[0]->datos_nuevos);
        $this->assertArrayNotHasKey('derivado_por_contacto', $creates[0]->datos_nuevos);

        $this->patchJson($this->base($paciente->id).'/'.$ficha['id'], [
            'motivo_consulta_inicial' => 'Motivo nuevo.',
            'version' => 1,
        ])->assertOk();

        $update = BitacoraEntry::query()
            ->where('entidad', 'ficha_ingreso')
            ->where('entidad_id', $ficha['id'])
            ->where('accion', 'ACTUALIZAR')
            ->firstOrFail();

        $this->assertSame(['motivo_consulta_inicial'], $update->datos_nuevos['campos_modificados']);
        $this->assertArrayNotHasKey('motivo_consulta_inicial', $update->datos_anteriores);
        $this->assertArrayNotHasKey('motivo_consulta_inicial', $update->datos_nuevos);

        $this->getJson($this->base($paciente->id))->assertOk();

        $consultas = BitacoraEntry::query()
            ->where('entidad', 'ficha_ingreso')
            ->where('entidad_id', $ficha['id'])
            ->where('accion', 'CONSULTAR')
            ->get();

        $this->assertCount(1, $consultas);
        $this->assertSame('ficha_ingreso', $consultas[0]->datos_nuevos['contexto']['recurso']);

        $this->getJson($this->base($paciente->id).'/'.$ficha['id'].'/revisiones')->assertOk();

        $this->assertSame(
            2,
            BitacoraEntry::query()
                ->where('entidad', 'ficha_ingreso')
                ->where('entidad_id', $ficha['id'])
                ->where('accion', 'CONSULTAR')
                ->count(),
        );

        $this->postJson($this->base($paciente->id).'/'.$ficha['id'].'/archivar', [
            'motivo' => 'Archivo de prueba.',
            'version' => 2,
        ])->assertOk();

        $archive = BitacoraEntry::query()
            ->where('entidad', 'ficha_ingreso')
            ->where('entidad_id', $ficha['id'])
            ->where('accion', 'ARCHIVAR')
            ->firstOrFail();

        $this->assertTrue((bool) $archive->critico);
        $this->assertSame('Archivo de prueba.', $archive->motivo);
        $this->assertArrayNotHasKey('motivo_consulta_inicial', $archive->datos_nuevos);

        $this->app->instance(AuditRecorderContract::class, new ThrowingAuditRecorder);

        $this->getJson($this->base($paciente->id).'/'.$ficha['id'])
            ->assertStatus(503)
            ->assertJsonPath('code', 'AUDIT_UNAVAILABLE');
    }

    public function test_contracts_stubs_dates_versions_and_permissions(): void
    {
        $branch = $this->branch();
        $recepcion = $this->actingAsRole('recepcion');
        $this->withBranch($recepcion, $branch);
        $paciente = $this->paciente(['sucursal_registro_id' => $branch->id]);

        $this->postJson($this->base($paciente->id), $this->payload($branch->id, [
            'fecha_ingreso' => now()->addDay()->toDateString(),
        ]))->assertStatus(422)
            ->assertJsonPath('code', 'FICHA_DATE_INVALID');

        $this->postJson($this->base($paciente->id), $this->payload($branch->id, [
            'motivo_consulta_inicial' => '',
        ]))->assertStatus(422)
            ->assertJsonValidationErrors(['motivo_consulta_inicial']);

        $ficha = $this->createFicha($paciente->id, $branch->id);
        $contract = app(FichaIngresoDirectoryContract::class);

        $this->assertTrue($contract->existe($paciente->id));
        $this->assertSame($ficha['id'], $contract->fichaVigenteId($paciente->id));
        $this->assertSame('borrador', $contract->resumen($paciente->id)?->estado->value);
        $this->assertFalse($contract->tieneFichaCompleta($paciente->id));

        $this->postJson($this->base($paciente->id).'/'.$ficha['id'].'/completar', [
            'version' => 1,
        ])->assertOk();

        $this->assertTrue($contract->tieneFichaCompleta($paciente->id));

        $this->patchJson($this->base($paciente->id).'/'.$ficha['id'], [
            'motivo_consulta_inicial' => 'Versión vieja.',
            'version' => 1,
        ])->assertStatus(409)
            ->assertJsonPath('code', 'FICHA_VERSION_CONFLICT');

        $entrevista = app(EntrevistaDirectoryContract::class);
        $exporter = app(FichaIngresoExporterContract::class);

        $this->assertFalse($entrevista->disponible());

        try {
            $entrevista->existe(1);
            $this->fail('El stub de entrevista debía lanzar LogicException.');
        } catch (\LogicException $exception) {
            $this->assertStringContainsString('Módulo 06', $exception->getMessage());
        }

        try {
            $exporter->exportar($ficha['id'], $recepcion);
            $this->fail('El exportador debía lanzar LogicException.');
        } catch (\LogicException $exception) {
            $this->assertStringContainsString('Exportación PDF', $exception->getMessage());
        }
    }

    public function test_administracion_cannot_create_and_recepcion_cannot_archive(): void
    {
        $branch = $this->branch();
        $paciente = $this->paciente(['sucursal_registro_id' => $branch->id]);

        $admin = $this->actingAsRole('administracion');
        $this->withBranch($admin, $branch);

        $this->postJson($this->base($paciente->id), $this->payload($branch->id))
            ->assertStatus(403);

        $recepcion = $this->actingAsRole('recepcion');
        $this->withBranch($recepcion, $branch);
        $ficha = $this->createFicha($paciente->id, $branch->id);

        $this->postJson($this->base($paciente->id).'/'.$ficha['id'].'/archivar', [
            'motivo' => 'No autorizado.',
            'version' => 1,
        ])->assertStatus(403);
    }

    private function base(int $pacienteId): string
    {
        return '/api/pacientes/pacientes/'.$pacienteId.'/ficha-ingreso';
    }

    /**
     * @param  array<string, mixed>  $overrides
     * @return array<string, mixed>
     */
    private function payload(int $branchId, array $overrides = []): array
    {
        return array_merge([
            'motivo_consulta_inicial' => 'Motivo inicial de consulta.',
            'fecha_ingreso' => now()->toDateString(),
            'sucursal_ingreso_id' => $branchId,
            'composicion_familiar' => 'Composición familiar inicial.',
            'antecedentes_familiares' => 'Antecedente referido por la familia.',
            'observaciones' => 'Observación inicial.',
            'derivado_por' => 'Dra. Referente',
            'derivado_por_institucion' => 'Centro de salud',
            'derivado_por_contacto' => '70001111',
        ], $overrides);
    }

    /**
     * @param  array<string, mixed>  $overrides
     * @return array<string, mixed>
     */
    private function createFicha(int $pacienteId, int $branchId, array $overrides = []): array
    {
        return $this->postJson($this->base($pacienteId), $this->payload($branchId, $overrides))
            ->assertCreated()
            ->json('data');
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

        $role = Role::findOrCreate('rol_ficha_ingreso_test', 'web');
        $role->syncPermissions($permissions);

        $user = User::factory()->create();
        $user->assignRole($role);
        $user->branches()->syncWithoutDetaching([$branch->id]);
        Sanctum::actingAs($user);

        return $user;
    }
}
