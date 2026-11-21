<?php

namespace Tests\Feature\Modules\Pacientes\Registro;

use App\Modules\Pacientes\Models\BitacoraEntry;
use App\Modules\Seguridad\Contracts\AuditRecorderContract;
use Tests\Feature\Modules\Pacientes\PacientesTestCase;
use Tests\Support\ThrowingAuditRecorder;

final class PacienteConsultationAuditTest extends PacientesTestCase
{
    public function test_only_ficha_consultation_is_audited(): void
    {
        $branch = $this->branch();
        $user = $this->actingAsRole('recepcion');
        $this->withBranch($user, $branch);

        $created = $this->postJson('/api/pacientes/pacientes', $this->pacientePayload([], $branch))
            ->assertCreated()
            ->json('data');

        $this->getJson('/api/pacientes/pacientes')->assertOk();

        $this->assertDatabaseMissing('bitacora', [
            'entidad' => 'paciente',
            'accion' => 'CONSULTAR',
        ]);

        $this->getJson('/api/pacientes/pacientes/'.$created['id'])->assertOk();

        $entry = BitacoraEntry::query()
            ->where('entidad', 'paciente')
            ->where('entidad_id', $created['id'])
            ->where('accion', 'CONSULTAR')
            ->firstOrFail();

        $this->assertSame('ficha_paciente', $entry->datos_nuevos['contexto']['recurso']);
        $this->assertArrayNotHasKey('contenido', $entry->datos_nuevos['contexto']);
    }

    public function test_ficha_is_fail_closed_when_audit_fails(): void
    {
        $branch = $this->branch();
        $user = $this->actingAsRole('recepcion');
        $this->withBranch($user, $branch);

        $created = $this->postJson('/api/pacientes/pacientes', $this->pacientePayload([], $branch))
            ->assertCreated()
            ->json('data');

        $this->app->instance(AuditRecorderContract::class, new ThrowingAuditRecorder);

        $this->getJson('/api/pacientes/pacientes/'.$created['id'])
            ->assertStatus(503)
            ->assertJsonPath('code', 'AUDIT_UNAVAILABLE');

        $this->getJson('/api/pacientes/pacientes')
            ->assertOk();
    }
}
