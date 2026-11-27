<?php

namespace Tests\Feature\Modules\AgendaTurnos;

final class AgendaDetalleTest extends AgendaSemanalTestCase
{
    public function test_detail_returns_operational_fields_and_pending_actions(): void
    {
        $scenario = $this->scenario();
        $turno = $this->turno(
            $scenario['branch'],
            $scenario['sala'],
            $scenario['paciente'],
            $scenario['servicio'],
            $scenario['profesional'],
            '2026-11-24',
            '08:00',
            '08:50',
            'asistio',
        );

        $response = $this->getJson('/api/agenda-turnos/agenda-semanal/turnos/'.$turno->id);

        $response
            ->assertOk()
            ->assertJsonPath('data.id', $turno->id)
            ->assertJsonPath('data.paciente.nombre_completo', $scenario['paciente']->nombre_completo)
            ->assertJsonPath('data.servicio.nombre', $scenario['servicio']->nombre)
            ->assertJsonPath('data.sala.nombre', $scenario['sala']->nombre)
            ->assertJsonPath('data.profesional.nombre_completo', $scenario['profesional']->nombre_completo)
            ->assertJsonPath('data.estado', 'asistio')
            ->assertJsonPath('data.sin_asignar', false)
            ->assertJsonPath('acciones', [])
            ->assertJsonPath('acciones_disponibles', false);

        $data = $response->json('data');
        $this->assertArrayNotHasKey('ci', $data);
        $this->assertArrayNotHasKey('fecha_nacimiento', $data);
        $this->assertArrayNotHasKey('telefono', $data);
        $this->assertArrayNotHasKey('diagnostico', $data);
    }

    public function test_detail_outside_branch_scope_is_denied(): void
    {
        $scenario = $this->scenario();
        $otherBranch = $this->branch();
        $otherArea = $this->area();
        $otherService = $this->servicio($otherArea);
        $otherRoom = $this->sala($otherBranch, $otherArea);
        $otherPatient = $this->paciente($otherBranch);
        $otherTurn = $this->turno(
            $otherBranch,
            $otherRoom,
            $otherPatient,
            $otherService,
        );

        $this->getJson('/api/agenda-turnos/agenda-semanal/turnos/'.$otherTurn->id)
            ->assertStatus(403)
            ->assertJsonPath('code', 'BRANCH_ACCESS_DENIED');
    }

    public function test_missing_detail_returns_404(): void
    {
        $this->scenario();

        $this->getJson('/api/agenda-turnos/agenda-semanal/turnos/999999')
            ->assertStatus(404)
            ->assertJsonPath('code', 'TURNO_NOT_FOUND');
    }
}
