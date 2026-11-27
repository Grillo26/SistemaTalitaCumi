<?php

namespace Tests\Feature\Modules\InscripcionesAtenciones;

final class EstadosReporteTest extends InscripcionesAtencionesTestCase
{
    public function test_reports_return_counts_for_patients_and_inscriptions(): void
    {
        $escenario = $this->scenario('administracion');
        $this->crearInscripcion($escenario)->assertCreated();

        $this->postJson('/api/inscripciones-atenciones/estados/pacientes/'.$escenario['paciente']->id.'/pausar', [
            'estado' => 'en_pausa',
            'estado_esperado' => 'activo',
            'motivo' => 'viaje',
        ])->assertOk();

        $this->getJson('/api/inscripciones-atenciones/estados/reportes?tipo=pacientes')
            ->assertOk()
            ->assertJsonPath('data.tipo', 'pacientes')
            ->assertJsonPath('data.conteos.pacientes.en_pausa', 1)
            ->assertJsonPath('data.conteos.inscripciones.activa', 1);

        $this->getJson('/api/inscripciones-atenciones/estados/reportes?tipo=inscripciones')
            ->assertOk()
            ->assertJsonPath('data.tipo', 'inscripciones')
            ->assertJsonPath('data.conteos.inscripciones.activa', 1)
            ->assertJsonPath('data.meta.total', 1);
    }

    public function test_report_filters_by_patient_state(): void
    {
        $escenario = $this->scenario('administracion');
        $this->postJson('/api/inscripciones-atenciones/estados/pacientes/'.$escenario['paciente']->id.'/pausar', [
            'estado' => 'en_pausa',
            'estado_esperado' => 'activo',
            'motivo' => 'enfermedad',
        ])->assertOk();

        $this->getJson('/api/inscripciones-atenciones/estados/reportes?tipo=pacientes&estado=en_pausa')
            ->assertOk()
            ->assertJsonPath('data.meta.total', 1)
            ->assertJsonPath('data.data.0.estado', 'en_pausa');

        $this->getJson('/api/inscripciones-atenciones/estados/reportes?tipo=pacientes&estado=activo')
            ->assertOk()
            ->assertJsonPath('data.meta.total', 0);
    }

    public function test_export_returns_clear_503_and_reception_cannot_report(): void
    {
        $this->actingAsRole('administracion');

        $this->getJson('/api/inscripciones-atenciones/estados/reportes/exportar?formato=pdf')
            ->assertStatus(503)
            ->assertJsonPath('code', 'MODULO_NO_DISPONIBLE');

        $this->actingAsRole('recepcion');

        $this->getJson('/api/inscripciones-atenciones/estados/reportes')
            ->assertForbidden();
    }
}
