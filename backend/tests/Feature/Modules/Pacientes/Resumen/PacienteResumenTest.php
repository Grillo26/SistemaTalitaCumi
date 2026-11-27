<?php

namespace Tests\Feature\Modules\Pacientes\Resumen;

use App\Models\User;
use App\Modules\Organizacion\Models\Branch;
use App\Modules\Pacientes\Contracts\PatientAttendanceSummaryContract;
use App\Modules\Pacientes\Contracts\PatientPaymentAlertContract;
use App\Modules\Pacientes\Contracts\PatientPaymentStatusContract;
use App\Modules\Pacientes\Contracts\PatientServicesSummaryContract;
use App\Modules\Pacientes\Contracts\TutorContactContract;
use App\Modules\Pacientes\Models\BitacoraEntry;
use App\Modules\Pacientes\Models\ContactoEmergencia;
use App\Modules\Pacientes\Models\Paciente;
use App\Modules\Pacientes\Models\Parentesco;
use App\Modules\Pacientes\Models\Tutor;
use App\Modules\Seguridad\Contracts\AuditRecorderContract;
use Laravel\Sanctum\Sanctum;
use PHPUnit\Framework\Attributes\Test;
use RuntimeException;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;
use Tests\Feature\Modules\Pacientes\PacientesTestCase;
use Tests\Support\ThrowingAuditRecorder;

final class PacienteResumenTest extends PacientesTestCase
{
    #[Test]
    public function resumen_returns_basic_data_tutors_contacts_and_flags_without_clinical_content(): void
    {
        $branch = $this->branch();
        $recepcion = $this->actingAsRole('recepcion');
        $this->withBranch($recepcion, $branch);

        [$paciente, $tutor] = $this->pacienteConTutor($branch, [
            'alergias' => 'Penicilina',
            'condiciones_medicas' => 'Asma',
            'observaciones' => 'Observación clínica que no debe copiarse.',
        ]);
        $contacto = ContactoEmergencia::factory()->create([
            'paciente_id' => $paciente->id,
            'parentesco_id' => Parentesco::query()->value('id'),
            'telefono' => '76543210',
        ]);

        $response = $this->getJson($this->base($paciente->id))
            ->assertOk()
            ->assertJsonPath('data.paciente.id', $paciente->id)
            ->assertJsonPath('data.bloques.datos_basicos.estado', 'disponible')
            ->assertJsonPath('data.bloques.datos_sensibles.data.tiene_alergias', true)
            ->assertJsonPath('data.bloques.datos_sensibles.data.tiene_condiciones_medicas', true)
            ->assertJsonPath('data.bloques.tutores.data.items.0.nombre_completo', $tutor->nombre_completo)
            ->assertJsonPath('data.bloques.tutores.data.items.0.telefono', $tutor->telefono)
            ->assertJsonPath('data.bloques.contactos_emergencia.data.items.0.telefono', '76543210')
            ->assertJsonPath('data.bloques.servicios_vigentes.estado', 'no_disponible')
            ->assertJsonPath('data.bloques.servicios_vigentes.motivo', 'MODULO_NO_IMPLEMENTADO')
            ->assertJsonPath('data.bloques.estado_pago.estado', 'no_disponible')
            ->assertJsonPath('data.bloques.estado_pago.data', null)
            ->assertJsonPath('data.bloques.asistencia.motivo', 'SIN_SERVICIOS_VIGENTES')
            ->assertJsonPath('data.bloques.proximo_vencimiento.motivo', 'MODULO_NO_IMPLEMENTADO')
            ->assertJsonPath('data.bloques.sucursales.motivo', 'MODULO_NO_IMPLEMENTADO')
            ->assertJsonPath('data.bloques.alerta_pago.motivo', 'MODULO_NO_IMPLEMENTADO')
            ->assertJsonPath('can.view_resumen', true)
            ->assertJsonPath('can.search_by_tutor_phone', true)
            ->assertJsonPath('can.export_listado', false)
            ->assertJsonPath('can.alerta_pago', false);

        $response->assertJsonMissingPath('data.bloques.datos_basicos.data.alergias');
        $response->assertJsonMissingPath('data.bloques.datos_basicos.data.condiciones_medicas');
        $response->assertJsonMissingPath('data.bloques.datos_basicos.data.observaciones');

        $audits = BitacoraEntry::query()
            ->where('entidad', 'paciente')
            ->where('entidad_id', $paciente->id)
            ->where('accion', 'CONSULTAR')
            ->get();

        $this->assertCount(1, $audits);
        $this->assertSame('ficha_resumen', $audits[0]->datos_nuevos['contexto']['recurso']);
        $this->assertSame($branch->id, $audits[0]->sucursal_id);
        $this->assertArrayNotHasKey('telefono', $audits[0]->datos_nuevos['contexto']);
        $this->assertArrayNotHasKey('documento', $audits[0]->datos_nuevos['contexto']);
        $this->assertArrayNotHasKey('alergias', $audits[0]->datos_nuevos['contexto']);

        $this->assertNotNull($contacto->id);
    }

    #[Test]
    public function tutors_and_contacts_respect_permissions_and_mask_phones(): void
    {
        $branch = $this->branch();
        [$paciente] = $this->pacienteConTutor($branch, [
            'nombre' => 'Privacidad',
            'apellido_paterno' => 'Resumen',
        ]);

        ContactoEmergencia::factory()->create([
            'paciente_id' => $paciente->id,
            'parentesco_id' => Parentesco::query()->value('id'),
            'telefono' => '76543210',
        ]);

        $this->actAsCustomRole($branch, ['paciente.ver']);

        $this->getJson($this->base($paciente->id))
            ->assertOk()
            ->assertJsonPath('data.bloques.tutores.estado', 'no_disponible')
            ->assertJsonPath('data.bloques.tutores.motivo', 'SIN_PERMISO')
            ->assertJsonPath('data.bloques.contactos_emergencia.motivo', 'SIN_PERMISO')
            ->assertJsonPath('can.view_contact', false);

        $this->actAsCustomRole($branch, ['paciente.ver', 'tutor.ver']);

        $this->getJson($this->base($paciente->id))
            ->assertOk()
            ->assertJsonPath('data.bloques.tutores.estado', 'disponible')
            ->assertJsonPath('data.bloques.tutores.data.items.0.telefono', '712***67')
            ->assertJsonPath('data.bloques.tutores.data.items.0.email', null)
            ->assertJsonPath('data.bloques.tutores.data.items.0.direccion', null)
            ->assertJsonPath('data.bloques.contactos_emergencia.motivo', 'SIN_PERMISO');
    }

    #[Test]
    public function audit_failure_is_fail_closed(): void
    {
        $branch = $this->branch();
        $recepcion = $this->actingAsRole('recepcion');
        $this->withBranch($recepcion, $branch);
        [$paciente] = $this->pacienteConTutor($branch);

        $this->app->instance(AuditRecorderContract::class, new ThrowingAuditRecorder);

        $this->getJson($this->base($paciente->id))
            ->assertStatus(503)
            ->assertJsonPath('code', 'AUDIT_UNAVAILABLE');
    }

    #[Test]
    public function summary_is_scoped_and_branch_context_is_validated(): void
    {
        $branchA = $this->branch();
        $branchB = $this->branch();
        $recepcion = $this->actingAsRole('recepcion');
        $this->withBranch($recepcion, $branchA);

        [$pacienteA] = $this->pacienteConTutor($branchA);
        [$pacienteB] = $this->pacienteConTutor($branchB);

        $this->getJson($this->base($pacienteB->id))
            ->assertStatus(404)
            ->assertJsonPath('code', 'PATIENT_NOT_FOUND');

        $this->getJson($this->base($pacienteA->id).'?sucursal_id='.$branchB->id)
            ->assertStatus(403)
            ->assertJsonPath('code', 'RESUMEN_SUCURSAL_INVALIDA');

        $this->getJson($this->base($pacienteA->id).'?sucursal_id='.$branchA->id)
            ->assertOk()
            ->assertJsonPath('data.contexto_sucursal', $branchA->id);
    }

    #[Test]
    public function indicators_refresh_does_not_audit_and_returns_only_indicators(): void
    {
        $branch = $this->branch();
        $recepcion = $this->actingAsRole('recepcion');
        $this->withBranch($recepcion, $branch);
        [$paciente] = $this->pacienteConTutor($branch);

        $this->getJson($this->base($paciente->id))->assertOk();
        $this->getJson($this->base($paciente->id).'/indicadores')
            ->assertOk()
            ->assertJsonMissingPath('data.paciente')
            ->assertJsonPath('data.bloques.estado_pago.motivo', 'MODULO_NO_IMPLEMENTADO')
            ->assertJsonPath('data.bloques.asistencia.motivo', 'SIN_SERVICIOS_VIGENTES');

        $this->assertSame(
            1,
            BitacoraEntry::query()
                ->where('entidad', 'paciente')
                ->where('entidad_id', $paciente->id)
                ->where('accion', 'CONSULTAR')
                ->count(),
        );
    }

    #[Test]
    public function a_failing_source_block_degrades_without_500(): void
    {
        $branch = $this->branch();
        $recepcion = $this->actingAsRole('recepcion');
        $this->withBranch($recepcion, $branch);
        [$paciente] = $this->pacienteConTutor($branch);

        $this->app->instance(
            PatientServicesSummaryContract::class,
            new class implements PatientServicesSummaryContract
            {
                public function disponible(): bool
                {
                    return true;
                }

                public function serviciosVigentes(
                    int $pacienteId,
                    ?array $sucursalesAccesibles = null,
                    ?int $sucursalContexto = null,
                ): array {
                    throw new RuntimeException('Falla simulada de 06.');
                }

                public function sucursalesConServicios(
                    int $pacienteId,
                    ?array $sucursalesAccesibles = null,
                ): array {
                    return [];
                }
            },
        );

        $this->getJson($this->base($paciente->id))
            ->assertOk()
            ->assertJsonPath('data.bloques.servicios_vigentes.estado', 'no_disponible')
            ->assertJsonPath('data.bloques.servicios_vigentes.motivo', 'ERROR')
            ->assertJsonPath('data.bloques.sucursales.motivo', 'ERROR')
            ->assertJsonPath('data.bloques.datos_basicos.estado', 'disponible');
    }

    #[Test]
    public function stubs_are_unavailable_and_throw_only_if_invoked(): void
    {
        $contracts = [
            app(PatientServicesSummaryContract::class),
            app(PatientPaymentStatusContract::class),
            app(TutorContactContract::class),
            app(PatientPaymentAlertContract::class),
        ];

        foreach ($contracts as $contract) {
            $this->assertFalse($contract->disponible());
        }

        $this->assertTrue(app(PatientAttendanceSummaryContract::class)->disponible());

        $this->expectException(\LogicException::class);
        $contracts[0]->serviciosVigentes(1);
    }

    /**
     * @param  array<string, mixed>  $pacienteOverrides
     * @param  array<string, mixed>  $tutorOverrides
     * @return array{0: Paciente, 1: Tutor}
     */
    private function pacienteConTutor(
        Branch $branch,
        array $pacienteOverrides = [],
        array $tutorOverrides = [],
    ): array {
        $paciente = $this->paciente(array_merge([
            'sucursal_registro_id' => $branch->id,
        ], $pacienteOverrides));

        $tutor = $this->tutor(array_merge([
            'sucursal_registro_id' => $branch->id,
            'parentesco_id' => Parentesco::query()->value('id'),
            'telefono' => '71234567',
        ], $tutorOverrides));

        $paciente->tutores()->attach($tutor->id, [
            'parentesco_id' => $tutor->parentesco_id,
            'es_principal' => true,
        ]);

        return [$paciente, $tutor];
    }

    private function base(int $pacienteId): string
    {
        return '/api/pacientes/pacientes/'.$pacienteId.'/resumen';
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

        $role = Role::findOrCreate('rol_resumen_paciente_test', 'web');
        $role->syncPermissions($permissions);

        $user = User::factory()->create();
        $user->assignRole($role);
        $user->branches()->syncWithoutDetaching([$branch->id]);
        Sanctum::actingAs($user);

        return $user;
    }
}
