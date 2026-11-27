<?php

namespace Tests\Feature\Modules\InscripcionesAtenciones;

final class EstadosPacienteSalaPaqueteTest extends PaquetesSesionesTestCase
{
    public function test_sala_and_package_block_paused_and_retired_patients(): void
    {
        $sala = $this->scenario('administracion');
        $sala['paciente']->update(['estado' => 'en_pausa']);

        $this->crearInscripcion($sala)
            ->assertStatus(409)
            ->assertJsonPath('code', 'PATIENT_NOT_INSCRIBIBLE')
            ->assertJsonPath('motivo_codigo', 'PATIENT_PAUSED');

        $sala['paciente']->update(['estado' => 'retirado']);

        $this->crearInscripcion($sala)
            ->assertStatus(409)
            ->assertJsonPath('code', 'PATIENT_NOT_INSCRIBIBLE')
            ->assertJsonPath('motivo_codigo', 'PATIENT_RETIRED');

        $paquete = $this->escenarioPaquete('administracion');
        $paquete['paciente']->update(['estado' => 'en_pausa']);

        $this->crearPaquete($paquete)
            ->assertStatus(409)
            ->assertJsonPath('code', 'PATIENT_NOT_INSCRIBIBLE')
            ->assertJsonPath('motivo_codigo', 'PATIENT_PAUSED');

        $paquete['paciente']->update(['estado' => 'retirado']);

        $this->crearPaquete($paquete)
            ->assertStatus(409)
            ->assertJsonPath('code', 'PATIENT_NOT_INSCRIBIBLE')
            ->assertJsonPath('motivo_codigo', 'PATIENT_RETIRED');
    }
}
