<?php

namespace Tests\Feature\Modules\Pacientes\Registro;

use App\Modules\Pacientes\Models\BitacoraEntry;
use App\Modules\Pacientes\Models\Paciente;
use App\Modules\Pacientes\Models\Tutor;
use Tests\Feature\Modules\Pacientes\PacientesTestCase;

final class PacienteCrudTest extends PacientesTestCase
{
    public function test_recepcion_creates_patient_with_primary_tutor_and_audit(): void
    {
        $branch = $this->branch();
        $user = $this->actingAsRole('recepcion');
        $this->withBranch($user, $branch);

        $response = $this->postJson('/api/pacientes/pacientes', $this->pacientePayload([], $branch))
            ->assertCreated()
            ->json('data');

        $this->assertStringStartsWith('PAC-', (string) $response['codigo']);
        $this->assertSame('activo', $response['estado']);
        $this->assertSame('María López Quispe', $response['nombre_completo']);
        $this->assertNotEmpty($response['edad']['etiqueta']);
        $this->assertSame('Ana López', $response['tutores'][0]['nombre_completo']);
        $this->assertTrue($response['tutores'][0]['es_principal']);

        $paciente = Paciente::query()->findOrFail($response['id']);
        $tutor = Tutor::query()->firstOrFail();

        $this->assertDatabaseHas('paciente_tutor', [
            'paciente_id' => $paciente->id,
            'tutor_id' => $tutor->id,
            'es_principal' => true,
        ]);

        $this->assertDatabaseHas('estado_paciente_historial', [
            'paciente_id' => $paciente->id,
            'estado_anterior' => null,
            'estado_nuevo' => 'activo',
        ]);

        $audit = BitacoraEntry::query()
            ->where('entidad', 'paciente')
            ->where('entidad_id', $paciente->id)
            ->where('accion', 'CREAR')
            ->firstOrFail();

        $this->assertSame('pacientes', $audit->modulo);
        $this->assertSame('exito', $audit->resultado);
        $this->assertSame($branch->id, $audit->sucursal_id);
        $this->assertArrayHasKey('nombre', $audit->datos_nuevos);
        $this->assertArrayNotHasKey('alergias', $audit->datos_nuevos);
    }

    public function test_patient_list_and_ficha_are_scoped_and_ficha_is_audited(): void
    {
        $branch = $this->branch();
        $user = $this->actingAsRole('recepcion');
        $this->withBranch($user, $branch);

        $created = $this->postJson('/api/pacientes/pacientes', $this->pacientePayload([], $branch))
            ->assertCreated()
            ->json('data');

        $this->getJson('/api/pacientes/pacientes')
            ->assertOk()
            ->assertJsonPath('data.0.id', $created['id'])
            ->assertJsonMissingPath('data.0.alergias')
            ->assertJsonMissingPath('data.0.observaciones');

        $this->getJson('/api/pacientes/pacientes?search='.strtolower((string) $created['codigo']))
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.id', $created['id']);

        $this->assertDatabaseMissing('bitacora', [
            'entidad' => 'paciente',
            'accion' => 'CONSULTAR',
        ]);

        $this->getJson('/api/pacientes/pacientes/'.$created['id'])
            ->assertOk()
            ->assertJsonPath('data.id', $created['id']);

        $this->assertDatabaseHas('bitacora', [
            'entidad' => 'paciente',
            'entidad_id' => $created['id'],
            'accion' => 'CONSULTAR',
            'sucursal_id' => $branch->id,
        ]);
    }

    public function test_update_increments_version_and_stale_version_conflicts(): void
    {
        $branch = $this->branch();
        $user = $this->actingAsRole('recepcion');
        $this->withBranch($user, $branch);

        $created = $this->postJson('/api/pacientes/pacientes', $this->pacientePayload([], $branch))
            ->assertCreated()
            ->json('data');

        $updated = $this->patchJson('/api/pacientes/pacientes/'.$created['id'], [
            'nombre' => 'María Fernanda',
            'version' => 1,
        ])->assertOk()->json('data');

        $this->assertSame(2, $updated['version']);
        $this->assertSame('María Fernanda', $updated['nombre']);

        $this->patchJson('/api/pacientes/pacientes/'.$created['id'], [
            'nombre' => 'Otro nombre',
            'version' => 1,
        ])->assertStatus(409)
            ->assertJsonPath('code', 'PATIENT_VERSION_CONFLICT');
    }

    public function test_delete_is_always_rejected_with_409(): void
    {
        $branch = $this->branch();
        $user = $this->actingAsRole('recepcion');
        $this->withBranch($user, $branch);

        $paciente = $this->paciente(['sucursal_registro_id' => $branch->id]);

        $this->deleteJson('/api/pacientes/pacientes/'.$paciente->id)
            ->assertStatus(409)
            ->assertJsonPath('code', 'PATIENT_DELETE_NOT_ALLOWED');

        $this->assertDatabaseHas('paciente', ['id' => $paciente->id]);
    }
}
