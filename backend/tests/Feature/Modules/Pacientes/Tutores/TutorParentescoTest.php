<?php

namespace Tests\Feature\Modules\Pacientes\Tutores;

use App\Modules\Pacientes\Models\Parentesco;
use Tests\Feature\Modules\Pacientes\PacientesTestCase;

final class TutorParentescoTest extends PacientesTestCase
{
    public function test_legacy_parentesco_name_resolves_to_catalog(): void
    {
        $branch = $this->branch();
        $this->actingAsSuperadmin();

        $created = $this->postJson('/api/pacientes/tutores', [
            'nombre_completo' => 'Ana López',
            'ci' => '55555555',
            'telefono' => '71234567',
            'parentesco' => 'Madre',
            'sucursal_registro_id' => $branch->id,
        ])->assertCreated()->json('data');

        $madre = Parentesco::query()->where('nombre', 'Madre')->firstOrFail();

        $this->assertSame($madre->id, $created['parentesco_id']);
        $this->assertSame('Madre', $created['parentesco']);
    }

    public function test_relation_parentesco_overrides_tutor_default(): void
    {
        $branch = $this->branch();
        $this->actingAsSuperadmin();

        $tutor = $this->createTutor($branch->id, $this->parentescoId('Madre'));
        $tiaId = $this->parentescoId('Tía');

        $paciente = $this->postJson('/api/pacientes/pacientes', $this->pacientePayload([
            'tutor_principal' => [
                'modo' => 'existente',
                'tutor_id' => $tutor['id'],
                'parentesco_id' => $tiaId,
            ],
        ], $branch))->assertCreated()->json('data');

        $this->assertSame($tiaId, $paciente['tutores'][0]['parentesco_id']);
        $this->assertSame('Tía', $paciente['tutores'][0]['parentesco']);

        $ficha = $this->getJson('/api/pacientes/tutores/'.$tutor['id'])
            ->assertOk()
            ->json('data');

        $this->assertSame($this->parentescoId('Madre'), $ficha['parentesco_id']);
        $this->assertSame('Madre', $ficha['parentesco']);
        $this->assertSame($tiaId, $ficha['pacientes'][0]['parentesco_id']);
        $this->assertSame('Tía', $ficha['pacientes'][0]['parentesco']);
    }

    public function test_association_without_parentesco_uses_tutor_default(): void
    {
        $branch = $this->branch();
        $this->actingAsSuperadmin();

        $tutor = $this->createTutor($branch->id, $this->parentescoId('Padre'));

        $paciente = $this->postJson('/api/pacientes/pacientes', $this->pacientePayload([
            'tutor_principal' => [
                'modo' => 'existente',
                'tutor_id' => $tutor['id'],
                'parentesco' => null,
            ],
        ], $branch))->assertCreated()->json('data');

        $this->assertSame($this->parentescoId('Padre'), $paciente['tutores'][0]['parentesco_id']);
        $this->assertSame('Padre', $paciente['tutores'][0]['parentesco']);
    }

    public function test_inactive_parentesco_is_rejected(): void
    {
        $branch = $this->branch();
        $this->actingAsSuperadmin();

        $inactive = Parentesco::factory()->create([
            'nombre' => 'Primo',
            'activo' => false,
        ]);

        $this->postJson('/api/pacientes/tutores', [
            'nombre_completo' => 'Ana López',
            'ci' => '55555555',
            'telefono' => '71234567',
            'parentesco_id' => $inactive->id,
            'sucursal_registro_id' => $branch->id,
        ])->assertStatus(422)
            ->assertJsonValidationErrors(['parentesco_id']);

        $tutor = $this->createTutor($branch->id, $this->parentescoId('Madre'));

        $this->postJson('/api/pacientes/pacientes', $this->pacientePayload([
            'tutor_principal' => [
                'modo' => 'existente',
                'tutor_id' => $tutor['id'],
                'parentesco_id' => $inactive->id,
            ],
        ], $branch))->assertStatus(422)
            ->assertJsonValidationErrors(['tutor_principal.parentesco_id']);
    }

    private function parentescoId(string $name): int
    {
        return (int) Parentesco::query()->where('nombre', $name)->value('id');
    }

    /**
     * @return array<string, mixed>
     */
    private function createTutor(int $branchId, int $parentescoId): array
    {
        return $this->postJson('/api/pacientes/tutores', [
            'nombre_completo' => 'Ana López',
            'ci' => '55555555',
            'telefono' => '71234567',
            'parentesco_id' => $parentescoId,
            'sucursal_registro_id' => $branchId,
        ])->assertCreated()->json('data');
    }
}
