<?php

namespace Tests\Feature\Modules\AgendaTurnos;

use App\Modules\Organizacion\Contracts\SalaDirectoryContract;
use App\Modules\Pacientes\Contracts\PatientDirectoryContract;
use App\Modules\Profesionales\Contracts\ProfessionalDirectoryContract;
use Tests\Support\ThrowingPatientDirectoryContract;
use Tests\Support\ThrowingProfessionalDirectoryContract;
use Tests\Support\ThrowingSalaDirectoryContract;

final class AgendaContractsUnavailableTest extends AgendaSemanalTestCase
{
    public function test_agenda_loads_with_unavailable_operational_contracts(): void
    {
        $scenario = $this->scenario();
        $this->turno(
            $scenario['branch'],
            $scenario['sala'],
            $scenario['paciente'],
            $scenario['servicio'],
            $scenario['profesional'],
        );

        $this->app->instance(
            PatientDirectoryContract::class,
            new ThrowingPatientDirectoryContract,
        );
        $this->app->instance(
            ProfessionalDirectoryContract::class,
            new ThrowingProfessionalDirectoryContract,
        );
        $this->app->instance(
            SalaDirectoryContract::class,
            new ThrowingSalaDirectoryContract,
        );

        $response = $this->getJson($this->weekUrl($scenario['branch']->id));

        $response
            ->assertOk()
            ->assertJsonPath('data.disponibilidad.pacientes', false)
            ->assertJsonPath('data.disponibilidad.profesionales', false)
            ->assertJsonPath('data.disponibilidad.salas', false)
            ->assertJsonPath('data.turnos.0.paciente.nombre_completo', null)
            ->assertJsonPath('data.turnos.0.paciente.disponible', false)
            ->assertJsonPath('data.turnos.0.sala.nombre', null)
            ->assertJsonPath('data.turnos.0.profesional.nombre_completo', null)
            ->assertJsonPath('data.turnos.0.servicio.disponible', true);
    }
}
