<?php

namespace Tests\Feature\Modules\Pacientes\Ficha;

use App\Models\User;
use App\Modules\Organizacion\Models\Branch;
use App\Modules\Pacientes\Contracts\PatientConsentContract;
use App\Modules\Pacientes\Enums\EstadoFichaIngreso;
use App\Modules\Pacientes\Enums\ResultadoConsentimiento;
use App\Modules\Pacientes\Models\BitacoraEntry;
use App\Modules\Pacientes\Models\Consentimiento;
use App\Modules\Pacientes\Models\ConsentimientoTipo;
use App\Modules\Pacientes\Models\FichaIngreso;
use App\Modules\Pacientes\Models\Paciente;
use App\Modules\Pacientes\Models\Parentesco;
use App\Modules\Pacientes\Models\Tutor;
use PHPUnit\Framework\Attributes\Test;
use Tests\Feature\Modules\Pacientes\PacientesTestCase;

final class ConsentimientoTest extends PacientesTestCase
{
    #[Test]
    public function recepcion_registers_and_reads_consents_without_sensitive_reason(): void
    {
        $branch = $this->branch();
        $recepcion = $this->actingAsRole('recepcion');
        $this->withBranch($recepcion, $branch);
        $paciente = $this->paciente(['sucursal_registro_id' => $branch->id]);
        $tutor = $this->tutorConParentesco($branch);
        $this->attachTutor($paciente, $tutor);
        $ficha = $this->ficha($paciente, $branch, $recepcion);
        $tipo = $this->tipo('atencion');

        $this->getJson($this->base($paciente->id, $ficha->id))
            ->assertOk()
            ->assertJsonCount(0, 'data.vigentes')
            ->assertJsonCount(0, 'data.historial')
            ->assertJsonCount(3, 'tipos')
            ->assertJsonCount(1, 'tutores')
            ->assertJsonPath('tutores.0.id', $tutor->id)
            ->assertJsonPath('can.consent_register', true)
            ->assertJsonPath('can.consent_revoke', false);

        $consentimiento = $this->postJson($this->base($paciente->id, $ficha->id), $this->payload($tipo, $tutor, [
            'motivo' => 'La familia autoriza la atención.',
        ]))->assertCreated()
            ->assertJsonPath('data.resultado', 'otorgado')
            ->assertJsonPath('data.vigente', true)
            ->assertJsonPath('data.motivo', 'La familia autoriza la atención.')
            ->json('data');

        $this->getJson($this->base($paciente->id, $ficha->id))
            ->assertOk()
            ->assertJsonCount(1, 'data.vigentes')
            ->assertJsonCount(1, 'data.historial')
            ->assertJsonPath('data.vigentes.0.id', $consentimiento['id']);

        $entry = BitacoraEntry::query()
            ->where('entidad', 'consentimiento')
            ->where('entidad_id', $consentimiento['id'])
            ->where('accion', 'OTORGAR')
            ->firstOrFail();

        $this->assertNull($entry->motivo);
        $this->assertSame('atencion', $entry->datos_nuevos['tipo_codigo']);
        $this->assertSame('otorgado', $entry->datos_nuevos['resultado']);
        $this->assertArrayNotHasKey('motivo', $entry->datos_nuevos);

        $consulta = BitacoraEntry::query()
            ->where('entidad', 'ficha_ingreso')
            ->where('entidad_id', $ficha->id)
            ->where('accion', 'CONSULTAR')
            ->firstOrFail();

        $this->assertSame(
            'ficha_ingreso_consentimientos',
            $consulta->datos_nuevos['contexto']['recurso'],
        );
    }

    #[Test]
    public function a_negative_consent_is_visible_and_can_be_followed_by_a_grant(): void
    {
        $branch = $this->branch();
        $recepcion = $this->actingAsRole('recepcion');
        $this->withBranch($recepcion, $branch);
        $paciente = $this->paciente(['sucursal_registro_id' => $branch->id]);
        $tutor = $this->tutorConParentesco($branch);
        $this->attachTutor($paciente, $tutor);
        $ficha = $this->ficha($paciente, $branch, $recepcion);
        $tipo = $this->tipo('atencion');

        $negado = $this->postJson(
            $this->base($paciente->id, $ficha->id),
            $this->payload($tipo, $tutor, ['resultado' => 'negado', 'motivo' => 'La familia solicita esperar.']),
        )->assertCreated()
            ->assertJsonPath('data.resultado', 'negado')
            ->assertJsonPath('data.vigente', false)
            ->json('data');

        $this->getJson($this->base($paciente->id, $ficha->id))
            ->assertOk()
            ->assertJsonCount(0, 'data.vigentes')
            ->assertJsonCount(1, 'data.historial')
            ->assertJsonPath('data.historial.0.id', $negado['id']);

        $this->postJson($this->base($paciente->id, $ficha->id), $this->payload($tipo, $tutor))
            ->assertCreated()
            ->assertJsonPath('data.resultado', 'otorgado')
            ->assertJsonPath('data.vigente', true);
    }

    #[Test]
    public function an_active_consent_cannot_be_duplicated(): void
    {
        $branch = $this->branch();
        $recepcion = $this->actingAsRole('recepcion');
        $this->withBranch($recepcion, $branch);
        $paciente = $this->paciente(['sucursal_registro_id' => $branch->id]);
        $tutor = $this->tutorConParentesco($branch);
        $this->attachTutor($paciente, $tutor);
        $ficha = $this->ficha($paciente, $branch, $recepcion);
        $tipo = $this->tipo('atencion');

        $this->postJson($this->base($paciente->id, $ficha->id), $this->payload($tipo, $tutor))
            ->assertCreated();

        $this->postJson($this->base($paciente->id, $ficha->id), $this->payload($tipo, $tutor))
            ->assertStatus(409)
            ->assertJsonPath('code', 'CONSENT_ALREADY_ACTIVE');
    }

    #[Test]
    public function only_administracion_or_superadmin_can_revoke_and_it_is_append_only(): void
    {
        $branch = $this->branch();
        $recepcion = $this->actingAsRole('recepcion');
        $this->withBranch($recepcion, $branch);
        $paciente = $this->paciente(['sucursal_registro_id' => $branch->id]);
        $tutor = $this->tutorConParentesco($branch);
        $this->attachTutor($paciente, $tutor);
        $ficha = $this->ficha($paciente, $branch, $recepcion);
        $tipo = $this->tipo('atencion');

        $original = $this->postJson(
            $this->base($paciente->id, $ficha->id),
            $this->payload($tipo, $tutor, ['motivo' => 'Texto sensible que no debe ir a bitácora.']),
        )->assertCreated()->json('data');

        $this->postJson(
            $this->base($paciente->id, $ficha->id).'/'.$original['id'].'/revocar',
            ['motivo' => 'La familia retira la autorización.'],
        )->assertStatus(403);

        $admin = $this->actingAsRole('administracion');
        $this->withBranch($admin, $branch);

        $this->postJson(
            $this->base($paciente->id, $ficha->id).'/'.$original['id'].'/revocar',
            [],
        )->assertStatus(422)
            ->assertJsonValidationErrors(['motivo']);

        $revocacion = $this->postJson(
            $this->base($paciente->id, $ficha->id).'/'.$original['id'].'/revocar',
            ['motivo' => 'La familia retira la autorización.'],
        )->assertOk()
            ->assertJsonPath('data.resultado', 'revocado')
            ->assertJsonPath('data.revoca_a_id', $original['id'])
            ->assertJsonPath('data.vigente', false)
            ->json('data');

        $this->assertDatabaseHas('consentimiento', [
            'id' => $original['id'],
            'resultado' => ResultadoConsentimiento::OTORGADO->value,
        ]);

        $this->getJson($this->base($paciente->id, $ficha->id))
            ->assertOk()
            ->assertJsonCount(0, 'data.vigentes')
            ->assertJsonCount(2, 'data.historial');

        $this->postJson(
            $this->base($paciente->id, $ficha->id).'/'.$original['id'].'/revocar',
            ['motivo' => 'Repetida.'],
        )->assertStatus(409)
            ->assertJsonPath('code', 'CONSENT_NOT_REVOCABLE');

        $this->postJson(
            $this->base($paciente->id, $ficha->id).'/'.$revocacion['id'].'/revocar',
            ['motivo' => 'No aplica.'],
        )->assertStatus(409)
            ->assertJsonPath('code', 'CONSENT_NOT_REVOCABLE');

        $entry = BitacoraEntry::query()
            ->where('entidad', 'consentimiento')
            ->where('entidad_id', $revocacion['id'])
            ->where('accion', 'REVOCAR')
            ->firstOrFail();

        $this->assertTrue((bool) $entry->critico);
        $this->assertNull($entry->motivo);
        $this->assertSame($original['id'], $entry->datos_anteriores['consentimiento_original_id']);
        $this->assertArrayNotHasKey('motivo', $entry->datos_nuevos);

        $this->deleteJson($this->base($paciente->id, $ficha->id).'/'.$original['id'])
            ->assertStatus(409)
            ->assertJsonPath('code', 'CONSENT_DELETE_NOT_ALLOWED');
    }

    #[Test]
    public function ficha_without_active_tutors_blocks_consents(): void
    {
        $branch = $this->branch();
        $recepcion = $this->actingAsRole('recepcion');
        $this->withBranch($recepcion, $branch);
        $paciente = $this->paciente(['sucursal_registro_id' => $branch->id]);
        $ficha = $this->ficha($paciente, $branch, $recepcion);
        $tipo = $this->tipo('atencion');
        $tutor = $this->tutorConParentesco($branch);

        $this->getJson($this->base($paciente->id, $ficha->id))
            ->assertOk()
            ->assertJsonCount(0, 'tutores');

        $this->postJson($this->base($paciente->id, $ficha->id), $this->payload($tipo, $tutor))
            ->assertStatus(409)
            ->assertJsonPath('code', 'CONSENT_TUTOR_REQUIRED');
    }

    #[Test]
    public function tutor_must_be_associated_and_active(): void
    {
        $branch = $this->branch();
        $recepcion = $this->actingAsRole('recepcion');
        $this->withBranch($recepcion, $branch);
        $paciente = $this->paciente(['sucursal_registro_id' => $branch->id]);
        $activo = $this->tutorConParentesco($branch);
        $otroActivo = $this->tutorConParentesco($branch);
        $inactivo = $this->tutorConParentesco($branch, ['activo' => false]);
        $this->attachTutor($paciente, $activo);
        $this->attachTutor($paciente, $inactivo, false);
        $ficha = $this->ficha($paciente, $branch, $recepcion);
        $tipo = $this->tipo('atencion');

        $this->postJson($this->base($paciente->id, $ficha->id), $this->payload($tipo, $otroActivo))
            ->assertStatus(422)
            ->assertJsonPath('code', 'CONSENT_TUTOR_NOT_ASSOCIATED')
            ->assertJsonValidationErrors(['tutor_id']);

        $this->postJson($this->base($paciente->id, $ficha->id), $this->payload($tipo, $inactivo))
            ->assertStatus(422)
            ->assertJsonPath('code', 'CONSENT_TUTOR_NOT_ASSOCIATED');
    }

    #[Test]
    public function archived_ficha_blocks_register_and_revoke(): void
    {
        $branch = $this->branch();
        $recepcion = $this->actingAsRole('recepcion');
        $this->withBranch($recepcion, $branch);
        $paciente = $this->paciente(['sucursal_registro_id' => $branch->id]);
        $tutor = $this->tutorConParentesco($branch);
        $this->attachTutor($paciente, $tutor);
        $ficha = $this->ficha($paciente, $branch, $recepcion);
        $tipo = $this->tipo('atencion');

        $consentimiento = $this->postJson(
            $this->base($paciente->id, $ficha->id),
            $this->payload($tipo, $tutor),
        )->assertCreated()->json('data');

        $ficha->forceFill([
            'estado' => EstadoFichaIngreso::ARCHIVADA->value,
            'estado_anterior' => EstadoFichaIngreso::BORRADOR->value,
            'archivada_en' => now(),
            'archivada_por_id' => $recepcion->id,
            'motivo_archivado' => 'Archivo de prueba.',
        ])->save();

        $this->postJson($this->base($paciente->id, $ficha->id), $this->payload($tipo, $tutor))
            ->assertStatus(409)
            ->assertJsonPath('code', 'FICHA_ARCHIVED');

        $this->postJson(
            $this->base($paciente->id, $ficha->id).'/'.$consentimiento['id'].'/revocar',
            ['motivo' => 'No aplica.'],
        )->assertStatus(403);

        $admin = $this->actingAsRole('administracion');
        $this->withBranch($admin, $branch);

        $this->postJson(
            $this->base($paciente->id, $ficha->id).'/'.$consentimiento['id'].'/revocar',
            ['motivo' => 'No aplica.'],
        )->assertStatus(409)
            ->assertJsonPath('code', 'FICHA_ARCHIVED');
    }

    #[Test]
    public function inactive_type_and_future_date_are_rejected(): void
    {
        $branch = $this->branch();
        $recepcion = $this->actingAsRole('recepcion');
        $this->withBranch($recepcion, $branch);
        $paciente = $this->paciente(['sucursal_registro_id' => $branch->id]);
        $tutor = $this->tutorConParentesco($branch);
        $this->attachTutor($paciente, $tutor);
        $ficha = $this->ficha($paciente, $branch, $recepcion);
        $tipo = $this->tipo('atencion');

        $this->postJson($this->base($paciente->id, $ficha->id), $this->payload($tipo, $tutor, [
            'fecha' => now()->addDay()->toDateString(),
        ]))->assertStatus(422)
            ->assertJsonPath('code', 'CONSENT_DATE_INVALID')
            ->assertJsonValidationErrors(['fecha']);

        $tipo->update(['activo' => false]);

        $this->postJson($this->base($paciente->id, $ficha->id), $this->payload($tipo, $tutor))
            ->assertStatus(409)
            ->assertJsonPath('code', 'CONSENT_TYPE_INACTIVE');
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
    public function catalog_is_superadmin_only_versioned_and_not_deletable(): void
    {
        $superadmin = $this->actingAsSuperadmin();

        $created = $this->postJson($this->catalogBase(), [
            'codigo' => 'autorizacion_extra',
            'nombre' => 'Autorización extra',
            'descripcion' => 'Tipo adicional de prueba.',
            'orden' => 40,
            'activo' => true,
        ])->assertCreated()
            ->assertJsonPath('data.codigo', 'autorizacion_extra')
            ->assertJsonPath('data.version', 1)
            ->json('data');

        $this->postJson($this->catalogBase(), [
            'codigo' => 'autorizacion_extra',
            'nombre' => 'Duplicado',
        ])->assertStatus(422)
            ->assertJsonValidationErrors(['codigo']);

        $this->patchJson($this->catalogBase().'/'.$created['id'], [
            'nombre' => 'Autorización extra editada',
            'version' => 1,
        ])->assertOk()
            ->assertJsonPath('data.nombre', 'Autorización extra editada')
            ->assertJsonPath('data.version', 2);

        $this->patchJson($this->catalogBase().'/'.$created['id'], [
            'nombre' => 'Versión vieja',
            'version' => 1,
        ])->assertStatus(409)
            ->assertJsonPath('code', 'CONSENT_TYPE_VERSION_CONFLICT');

        $this->patchJson($this->catalogBase().'/'.$created['id'], [
            'codigo' => 'otro_codigo',
            'version' => 2,
        ])->assertStatus(422)
            ->assertJsonValidationErrors(['codigo']);

        $this->deleteJson($this->catalogBase().'/'.$created['id'])
            ->assertStatus(409)
            ->assertJsonPath('code', 'CONSENT_TYPE_DELETE_NOT_ALLOWED');

        $admin = $this->actingAsRole('administracion');

        $this->getJson($this->catalogBase())->assertStatus(403);
        $this->postJson($this->catalogBase(), [
            'codigo' => 'no_permitido',
            'nombre' => 'No permitido',
        ])->assertStatus(403);

        $this->actingAsSuperadmin();

        $this->getJson($this->catalogBase().'?incluir_inactivos=1')
            ->assertOk()
            ->assertJsonCount(4, 'data');

        $this->assertNotSame($admin->id, $superadmin->id);
    }

    #[Test]
    public function contract_reports_the_active_consent_of_the_latest_ficha(): void
    {
        $contract = app(PatientConsentContract::class);
        $branch = $this->branch();
        $recepcion = $this->actingAsRole('recepcion');
        $this->withBranch($recepcion, $branch);
        $paciente = $this->paciente(['sucursal_registro_id' => $branch->id]);

        $this->assertFalse($contract->tieneConsentimientoVigente($paciente->id));
        $this->assertNull($contract->consentimientoVigente($paciente->id));

        $tutor = $this->tutorConParentesco($branch);
        $this->attachTutor($paciente, $tutor);
        $ficha = $this->ficha($paciente, $branch, $recepcion);
        $tipo = $this->tipo('atencion');
        $consentimiento = $this->postJson(
            $this->base($paciente->id, $ficha->id),
            $this->payload($tipo, $tutor),
        )->assertCreated()->json('data');

        $this->assertTrue($contract->tieneConsentimientoVigente($paciente->id));

        $resumen = $contract->consentimientoVigente($paciente->id);

        $this->assertNotNull($resumen);
        $this->assertSame($consentimiento['id'], $resumen->consentimientoId);
        $this->assertSame($ficha->id, $resumen->fichaIngresoId);
        $this->assertSame('atencion', $resumen->tipoCodigo);
        $this->assertSame('otorgado', $resumen->resultado);

        $admin = $this->actingAsRole('administracion');
        $this->withBranch($admin, $branch);

        $this->postJson(
            $this->base($paciente->id, $ficha->id).'/'.$consentimiento['id'].'/revocar',
            ['motivo' => 'Revocación de prueba.'],
        )->assertOk();

        $this->assertFalse($contract->tieneConsentimientoVigente($paciente->id));
    }

    #[Test]
    public function consent_model_is_immutable(): void
    {
        $consentimiento = Consentimiento::factory()->create();

        try {
            $consentimiento->update(['motivo' => 'No debe actualizarse.']);
            $this->fail('El consentimiento debía rechazar la edición.');
        } catch (\LogicException $exception) {
            $this->assertStringContainsString('inmutables', $exception->getMessage());
        }

        try {
            $consentimiento->delete();
            $this->fail('El consentimiento debía rechazar la eliminación.');
        } catch (\LogicException $exception) {
            $this->assertStringContainsString('no se eliminan', $exception->getMessage());
        }
    }

    private function base(int $pacienteId, int $fichaId): string
    {
        return '/api/pacientes/pacientes/'.$pacienteId.'/ficha-ingreso/'.$fichaId.'/consentimientos';
    }

    private function catalogBase(): string
    {
        return '/api/pacientes/ficha-ingreso/consentimientos/tipos';
    }

    private function ficha(Paciente $paciente, Branch $branch, User $actor): FichaIngreso
    {
        return FichaIngreso::factory()->create([
            'paciente_id' => $paciente->id,
            'sucursal_ingreso_id' => $branch->id,
            'usuario_registro_id' => $actor->id,
        ]);
    }

    private function tutorConParentesco(Branch $branch, array $overrides = []): Tutor
    {
        return $this->tutor(array_merge([
            'sucursal_registro_id' => $branch->id,
            'parentesco_id' => Parentesco::query()->value('id'),
        ], $overrides));
    }

    private function attachTutor(Paciente $paciente, Tutor $tutor, bool $principal = true): void
    {
        $paciente->tutores()->attach($tutor->id, [
            'parentesco_id' => $tutor->parentesco_id,
            'es_principal' => $principal,
        ]);
    }

    private function tipo(string $codigo): ConsentimientoTipo
    {
        return ConsentimientoTipo::query()->where('codigo', $codigo)->firstOrFail();
    }

    /**
     * @param  array<string, mixed>  $overrides
     * @return array<string, mixed>
     */
    private function payload(ConsentimientoTipo $tipo, Tutor $tutor, array $overrides = []): array
    {
        return array_merge([
            'consentimiento_tipo_id' => $tipo->id,
            'tutor_id' => $tutor->id,
            'resultado' => 'otorgado',
            'fecha' => now()->toDateString(),
            'motivo' => null,
        ], $overrides);
    }
}
