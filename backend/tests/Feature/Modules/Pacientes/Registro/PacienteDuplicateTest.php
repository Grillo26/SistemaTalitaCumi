<?php

namespace Tests\Feature\Modules\Pacientes\Registro;

use App\Modules\Pacientes\Models\BitacoraEntry;
use App\Modules\Pacientes\Models\Paciente;
use App\Modules\Pacientes\Models\Tutor;
use Tests\Feature\Modules\Pacientes\PacientesTestCase;

final class PacienteDuplicateTest extends PacientesTestCase
{
    public function test_hard_document_duplicate_inside_scope_returns_minimal_identity(): void
    {
        $branch = $this->branch();
        $user = $this->actingAsRole('recepcion');
        $this->withBranch($user, $branch);

        $existing = $this->paciente([
            'sucursal_registro_id' => $branch->id,
            'ci' => '12345678',
        ]);

        $this->postJson('/api/pacientes/pacientes', $this->pacientePayload([
            'ci' => '12345678',
            'nombre' => 'Otra persona',
        ], $branch))
            ->assertStatus(409)
            ->assertJsonPath('code', 'PATIENT_DOCUMENT_DUPLICATE')
            ->assertJsonPath('data.fuera_de_alcance', false)
            ->assertJsonPath('data.paciente_existente.id', $existing->id)
            ->assertJsonPath('data.paciente_existente.nombre_completo', $existing->nombre_completo);
    }

    public function test_hard_document_duplicate_outside_scope_does_not_leak_data(): void
    {
        $branchA = $this->branch();
        $branchB = $this->branch();
        $user = $this->actingAsRole('recepcion');
        $this->withBranch($user, $branchA);

        $existing = $this->paciente([
            'sucursal_registro_id' => $branchB->id,
            'ci' => '87654321',
        ]);

        $response = $this->postJson('/api/pacientes/pacientes', $this->pacientePayload([
            'ci' => '87654321',
            'nombre' => 'Persona distinta',
        ], $branchA))
            ->assertStatus(409)
            ->assertJsonPath('code', 'PATIENT_DOCUMENT_DUPLICATE')
            ->assertJsonPath('data.fuera_de_alcance', true)
            ->assertJsonPath('data.paciente_existente', null)
            ->json('data');

        $this->assertArrayNotHasKey('id', $response);
        $this->assertSame(1, Paciente::query()->count());
        $this->assertSame(0, Tutor::query()->count());
    }

    public function test_soft_duplicate_requires_confirmation_and_audits_it(): void
    {
        $branch = $this->branch();
        $user = $this->actingAsRole('recepcion');
        $this->withBranch($user, $branch);

        $existing = $this->paciente([
            'sucursal_registro_id' => $branch->id,
            'nombre' => 'María',
            'apellido_paterno' => 'López',
            'apellido_materno' => 'Quispe',
            'fecha_nacimiento' => '2020-05-10',
        ]);

        $payload = $this->pacientePayload([
            'ci' => null,
            'nombre' => 'María',
            'apellido_paterno' => 'López',
            'apellido_materno' => 'Quispe',
            'fecha_nacimiento' => '2020-05-10',
        ], $branch);

        $this->postJson('/api/pacientes/pacientes', $payload)
            ->assertStatus(409)
            ->assertJsonPath('code', 'POTENTIAL_DUPLICATE')
            ->assertJsonPath('data.requiere_confirmacion', true)
            ->assertJsonPath('data.coincidencias.0.id', $existing->id);

        $this->postJson('/api/pacientes/pacientes', array_merge($payload, [
            'confirmar_duplicado' => true,
        ]))->assertStatus(422)
            ->assertJsonValidationErrors(['motivo_confirmacion']);

        $created = $this->postJson('/api/pacientes/pacientes', array_merge($payload, [
            'confirmar_duplicado' => true,
            'motivo_confirmacion' => 'La familia confirmó que es otra persona con los mismos nombres.',
        ]))->assertCreated()->json('data');

        $audit = BitacoraEntry::query()
            ->where('entidad', 'paciente')
            ->where('entidad_id', $created['id'])
            ->where('accion', 'CONFIRMAR_DUPLICADO')
            ->firstOrFail();

        $this->assertSame('La familia confirmó que es otra persona con los mismos nombres.', $audit->motivo);
        $this->assertSame([$existing->id], $audit->datos_nuevos['coincidencias_en_alcance']);
        $this->assertFalse($audit->datos_nuevos['hubo_coincidencias_fuera_de_alcance']);
    }

    public function test_soft_duplicate_outside_scope_can_be_confirmed_without_seeing_data(): void
    {
        $branchA = $this->branch();
        $branchB = $this->branch();
        $user = $this->actingAsRole('recepcion');
        $this->withBranch($user, $branchA);

        $this->paciente([
            'sucursal_registro_id' => $branchB->id,
            'nombre' => 'María',
            'apellido_paterno' => 'López',
            'apellido_materno' => 'Quispe',
            'fecha_nacimiento' => '2020-05-10',
        ]);

        $payload = $this->pacientePayload([
            'ci' => null,
            'nombre' => 'María',
            'apellido_paterno' => 'López',
            'apellido_materno' => 'Quispe',
            'fecha_nacimiento' => '2020-05-10',
        ], $branchA);

        $this->postJson('/api/pacientes/pacientes', $payload)
            ->assertStatus(409)
            ->assertJsonPath('code', 'POTENTIAL_DUPLICATE')
            ->assertJsonPath('data.coincidencias', [])
            ->assertJsonPath('data.coincidencias_fuera_de_alcance', true);

        $created = $this->postJson('/api/pacientes/pacientes', array_merge($payload, [
            'confirmar_duplicado' => true,
            'motivo_confirmacion' => 'Es otra persona; la familia lo confirmó.',
        ]))->assertCreated()->json('data');

        $audit = BitacoraEntry::query()
            ->where('entidad', 'paciente')
            ->where('entidad_id', $created['id'])
            ->where('accion', 'CONFIRMAR_DUPLICADO')
            ->firstOrFail();

        $this->assertSame([], $audit->datos_nuevos['coincidencias_en_alcance']);
        $this->assertTrue($audit->datos_nuevos['hubo_coincidencias_fuera_de_alcance']);
    }
}
