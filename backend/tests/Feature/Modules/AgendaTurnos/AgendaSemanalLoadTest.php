<?php

namespace Tests\Feature\Modules\AgendaTurnos;

use Illuminate\Support\Facades\DB;

final class AgendaSemanalLoadTest extends AgendaSemanalTestCase
{
    public function test_loads_the_week_without_query_growth_for_500_turns(): void
    {
        $scenario = $this->scenario();

        for ($i = 0; $i < 5; $i++) {
            $this->turno(
                $scenario['branch'],
                $scenario['sala'],
                $scenario['paciente'],
                $scenario['servicio'],
                $scenario['profesional'],
                '2026-11-24',
                sprintf('%02d:00', 8 + $i),
                sprintf('%02d:50', 8 + $i),
            );
        }

        DB::flushQueryLog();
        DB::enableQueryLog();

        $first = $this->getJson($this->url($scenario['branch']->id));
        $first->assertOk()->assertJsonCount(5, 'data.turnos');

        $firstQueries = count(DB::getQueryLog());
        DB::disableQueryLog();

        for ($i = 5; $i < 500; $i++) {
            $day = 23 + ($i % 6);
            $startMinutes = 7 * 60 + ($i % 12) * 60;
            $endMinutes = $startMinutes + 50;

            $this->turno(
                $scenario['branch'],
                $scenario['sala'],
                $scenario['paciente'],
                $scenario['servicio'],
                $scenario['profesional'],
                sprintf('2026-11-%02d', $day),
                $this->time($startMinutes),
                $this->time($endMinutes),
            );
        }

        DB::flushQueryLog();
        DB::enableQueryLog();

        $second = $this->getJson($this->url($scenario['branch']->id));

        $second->assertOk()->assertJsonCount(500, 'data.turnos');
        $second->assertJsonPath('data.resumen.total', 500);

        $secondQueries = count(DB::getQueryLog());
        DB::disableQueryLog();

        $this->assertLessThan(40, $secondQueries);
        $this->assertLessThanOrEqual(
            $firstQueries + 2,
            $secondQueries,
            'La cantidad de consultas no debe crecer con la cantidad de turnos.',
        );
    }

    public function test_empty_week_returns_zero_summary_and_no_turns(): void
    {
        $scenario = $this->scenario();

        $response = $this->getJson($this->url($scenario['branch']->id));

        $response
            ->assertOk()
            ->assertJsonCount(0, 'data.turnos')
            ->assertJsonPath('data.resumen.total', 0)
            ->assertJsonPath('data.resumen.por_estado.programado', 0)
            ->assertJsonPath('data.avisos', []);
    }

    public function test_turns_from_other_branches_are_never_present(): void
    {
        $scenario = $this->scenario();
        $otherBranch = $this->branch();
        $otherArea = $this->area();
        $otherService = $this->servicio($otherArea);
        $otherRoom = $this->sala($otherBranch, $otherArea, 'Sala Norte');
        $otherPatient = $this->paciente($otherBranch);
        $otherTurn = $this->turno(
            $otherBranch,
            $otherRoom,
            $otherPatient,
            $otherService,
            null,
            '2026-11-24',
            '10:00',
            '10:50',
        );

        $response = $this->getJson($this->url($scenario['branch']->id));

        $response->assertOk();
        $ids = collect($response->json('data.turnos'))->pluck('id')->all();
        $this->assertNotContains($otherTurn->id, $ids);

        $this->getJson($this->url($otherBranch->id))
            ->assertStatus(403)
            ->assertJsonPath('code', 'BRANCH_ACCESS_DENIED');
    }

    private function url(int $branchId): string
    {
        return '/api/agenda-turnos/agenda-semanal?sucursal_id='.$branchId.'&semana=2026-11-23';
    }

    private function time(int $minutes): string
    {
        return sprintf('%02d:%02d', intdiv($minutes, 60), $minutes % 60);
    }
}
