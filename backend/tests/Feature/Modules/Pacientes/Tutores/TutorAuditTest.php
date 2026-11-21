<?php

namespace Tests\Feature\Modules\Pacientes\Tutores;

use App\Modules\Pacientes\Models\BitacoraEntry;
use App\Modules\Pacientes\Models\Parentesco;
use App\Modules\Seguridad\Contracts\AuditRecorderContract;
use Tests\Feature\Modules\Pacientes\PacientesTestCase;
use Tests\Support\ThrowingAuditRecorder;

final class TutorAuditTest extends PacientesTestCase
{
    public function test_create_update_and_association_are_audited_with_full_values_and_branches(): void
    {
        $branchA = $this->branch();
        $branchB = $this->branch();
        $this->actingAsSuperadmin();

        $tutor = $this->createTutor($branchA->id, [
            'ci' => '7654321',
            'telefono' => '71234567',
            'email' => 'ana@example.test',
            'direccion' => 'Av. Siempre Viva 123',
        ]);

        $created = BitacoraEntry::query()
            ->where('entidad', 'tutor')
            ->where('entidad_id', $tutor['id'])
            ->where('accion', 'CREAR')
            ->firstOrFail();

        $this->assertSame($branchA->id, $created->sucursal_id);
        $this->assertSame('71234567', $created->datos_nuevos['telefono']);
        $this->assertSame('7654321', $created->datos_nuevos['ci']);
        $this->assertSame('Av. Siempre Viva 123', $created->datos_nuevos['direccion']);

        $paciente = $this->postJson('/api/pacientes/pacientes', $this->pacientePayload([
            'nombre' => 'Paciente de otra sucursal',
            'tutor_principal' => [
                'modo' => 'existente',
                'tutor_id' => $tutor['id'],
                'parentesco_id' => $this->parentescoId('Madre'),
            ],
        ], $branchB))->assertCreated()->json('data');

        $association = BitacoraEntry::query()
            ->where('entidad', 'paciente_tutor')
            ->where('accion', 'ASOCIAR')
            ->where('entidad_id', $paciente['id'])
            ->firstOrFail();

        $this->assertSame($branchB->id, $association->sucursal_id);
        $this->assertSame($tutor['id'], $association->datos_nuevos['tutor_id']);
        $this->assertSame($this->parentescoId('Madre'), $association->datos_nuevos['parentesco_id']);
        $this->assertTrue($association->datos_nuevos['es_principal']);

        $this->patchJson('/api/pacientes/tutores/'.$tutor['id'], [
            'telefono' => '70001111',
            'version' => 1,
        ])->assertOk();

        $updates = BitacoraEntry::query()
            ->where('entidad', 'tutor')
            ->where('entidad_id', $tutor['id'])
            ->where('accion', 'ACTUALIZAR')
            ->orderBy('sucursal_id')
            ->get();

        $this->assertCount(2, $updates);
        $this->assertSame([$branchA->id, $branchB->id], $updates->pluck('sucursal_id')->all());
        $this->assertSame('71234567', $updates[0]->datos_anteriores['telefono']);
        $this->assertSame('70001111', $updates[0]->datos_nuevos['telefono']);
        $this->assertSame('71234567', $updates[1]->datos_anteriores['telefono']);
        $this->assertSame('70001111', $updates[1]->datos_nuevos['telefono']);
        $this->assertContains('telefono', $updates[0]->datos_nuevos['campos_modificados']);
    }

    public function test_principal_change_and_status_change_are_audited_with_reason(): void
    {
        $branch = $this->branch();
        $this->actingAsSuperadmin();

        $paciente = $this->postJson('/api/pacientes/pacientes', $this->pacientePayload([], $branch))
            ->assertCreated()
            ->json('data');

        $principalId = $paciente['tutores'][0]['tutor_id'];

        $secondary = $this->createTutor($branch->id, [
            'nombre_completo' => 'Tutor Secundario',
            'ci' => '88888888',
            'telefono' => '70002222',
        ]);

        $this->postJson('/api/pacientes/pacientes/'.$paciente['id'].'/tutores', [
            'tutor_id' => $secondary['id'],
            'es_principal' => false,
        ])->assertCreated();

        $this->postJson(
            '/api/pacientes/pacientes/'.$paciente['id'].'/tutores/'.$secondary['id'].'/principal',
            ['motivo' => 'La madre solicitó el cambio.'],
        )->assertOk();

        $principalAudit = BitacoraEntry::query()
            ->where('entidad', 'paciente_tutor')
            ->where('accion', 'CAMBIAR_PRINCIPAL')
            ->where('entidad_id', $paciente['id'])
            ->latest('id')
            ->firstOrFail();

        $this->assertSame('La madre solicitó el cambio.', $principalAudit->motivo);
        $this->assertSame($principalId, $principalAudit->datos_anteriores['tutor_anterior_id']);
        $this->assertSame($secondary['id'], $principalAudit->datos_nuevos['tutor_id']);

        $this->postJson('/api/pacientes/tutores/'.$principalId.'/estado', [
            'activo' => false,
            'motivo' => 'Ya no es contacto principal.',
            'version' => 1,
        ])->assertOk();

        $statusAudit = BitacoraEntry::query()
            ->where('entidad', 'tutor')
            ->where('entidad_id', $principalId)
            ->where('accion', 'CAMBIAR_ESTADO')
            ->firstOrFail();

        $this->assertSame('Ya no es contacto principal.', $statusAudit->motivo);
        $this->assertTrue((bool) $statusAudit->critico);
    }

    public function test_consultation_is_audited_only_on_ficha(): void
    {
        $branch = $this->branch();
        $this->actingAsSuperadmin();

        $tutor = $this->createTutor($branch->id);

        $this->getJson('/api/pacientes/tutores')->assertOk();

        $this->assertDatabaseMissing('bitacora', [
            'entidad' => 'tutor',
            'accion' => 'CONSULTAR',
        ]);

        $this->getJson('/api/pacientes/tutores/'.$tutor['id'])->assertOk();

        $entry = BitacoraEntry::query()
            ->where('entidad', 'tutor')
            ->where('entidad_id', $tutor['id'])
            ->where('accion', 'CONSULTAR')
            ->firstOrFail();

        $this->assertSame($branch->id, $entry->sucursal_id);
        $this->assertSame('ficha_tutor', $entry->datos_nuevos['contexto']['recurso']);
        $this->assertArrayNotHasKey('telefono', $entry->datos_nuevos['contexto']);
    }

    public function test_ficha_is_fail_closed_when_audit_fails(): void
    {
        $branch = $this->branch();
        $this->actingAsSuperadmin();

        $tutor = $this->createTutor($branch->id);

        $this->app->instance(AuditRecorderContract::class, new ThrowingAuditRecorder);

        $this->getJson('/api/pacientes/tutores/'.$tutor['id'])
            ->assertStatus(503)
            ->assertJsonPath('code', 'AUDIT_UNAVAILABLE');

        $this->getJson('/api/pacientes/tutores')->assertOk();
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
