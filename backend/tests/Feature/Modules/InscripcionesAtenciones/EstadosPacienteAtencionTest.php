<?php

namespace Tests\Feature\Modules\InscripcionesAtenciones;

final class EstadosPacienteAtencionTest extends EntrevistaDiagnosticoTestCase
{
    public function test_interview_blocks_paused_patient(): void
    {
        $branch = $this->branch();
        $user = $this->actingAsRole('recepcion');
        $this->withBranch($user, $branch);
        $paciente = $this->paciente($branch, ['estado' => 'en_pausa']);
        $tutor = $this->tutor($branch);
        $this->asociarTutor($paciente, $tutor);
        $servicio = $this->servicioEntrevista();
        $categoria = $this->categoriaTarifa();
        $this->tarifa($servicio, $branch, $categoria, '250.00');

        $this->postJson(
            '/api/inscripciones-atenciones/entrevista-diagnostico/entrevistas',
            $this->payloadEntrevista($branch, $paciente, $tutor, $servicio, $categoria),
        )
            ->assertStatus(409)
            ->assertJsonPath('code', 'PATIENT_NOT_INSCRIBIBLE')
            ->assertJsonPath('motivo_codigo', 'PATIENT_PAUSED');
    }

    public function test_diagnosis_blocks_retired_patient(): void
    {
        $branch = $this->branch();
        $user = $this->actingAsRole('recepcion');
        $this->withBranch($user, $branch);
        $paciente = $this->paciente($branch, ['estado' => 'retirado']);
        $servicio = $this->servicioDiagnostico();
        $tipo = $this->tipoDiagnostico($servicio, []);
        $categoria = $this->categoriaTarifa();
        $this->tarifa($servicio, $branch, $categoria, '1200.00');

        $this->postJson(
            '/api/inscripciones-atenciones/entrevista-diagnostico/diagnosticos',
            $this->payloadDiagnostico($branch, $paciente, $tipo, $categoria),
        )
            ->assertStatus(409)
            ->assertJsonPath('code', 'PATIENT_NOT_INSCRIBIBLE')
            ->assertJsonPath('motivo_codigo', 'PATIENT_RETIRED');
    }
}
