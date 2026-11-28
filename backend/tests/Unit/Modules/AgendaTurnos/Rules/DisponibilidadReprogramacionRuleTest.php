<?php

namespace Tests\Unit\Modules\AgendaTurnos\Rules;

use App\Modules\AgendaTurnos\Enums\EstadoTurno;
use App\Modules\AgendaTurnos\Enums\SeveridadHallazgo;
use App\Modules\AgendaTurnos\Rules\DisponibilidadReprogramacionRule;
use App\Modules\AgendaTurnos\Rules\SesionGrupalRule;
use App\Modules\AgendaTurnos\Rules\TurnoSolapamientoRule;
use App\Modules\AgendaTurnos\Support\TurnoCandidato;
use App\Modules\AgendaTurnos\Support\TurnoExistente;
use PHPUnit\Framework\TestCase;

/**
 * [Reprogramación y Cancelación] RF-004 / RN-005 / CA-004.
 */
final class DisponibilidadReprogramacionRuleTest extends TestCase
{
    private DisponibilidadReprogramacionRule $rule;

    protected function setUp(): void
    {
        parent::setUp();
        $this->rule = new DisponibilidadReprogramacionRule(new TurnoSolapamientoRule);
    }

    public function test_partial_overlap_blocks_the_patient(): void
    {
        $hallazgos = $this->rule->evaluar(
            $this->candidato('09:30', '10:20'),
            [$this->existente(1, '09:00', '10:00')],
            99,
        );

        $this->assertCount(1, $hallazgos);
        $this->assertSame('paciente', $hallazgos[0]->tipo);
        $this->assertSame(SeveridadHallazgo::BLOQUEO, $hallazgos[0]->severidad);
    }

    public function test_contiguous_turns_do_not_conflict(): void
    {
        $this->assertSame([], $this->rule->evaluar(
            $this->candidato('10:00', '11:00'),
            [$this->existente(1, '09:00', '10:00')],
            99,
        ));
    }

    public function test_cancelled_and_reprogrammed_turns_are_ignored(): void
    {
        $this->assertSame([], $this->rule->evaluar(
            $this->candidato('09:30', '10:20'),
            [
                $this->existente(1, '09:00', '10:00', EstadoTurno::CANCELADO),
                $this->existente(2, '09:00', '10:00', EstadoTurno::REPROGRAMADO),
            ],
            99,
        ));
    }

    public function test_same_group_session_does_not_block_professional_or_room(): void
    {
        $clave = SesionGrupalRule::clave(5, '2025-12-02', '09:00', '09:50', 10);
        $existente = $this->existente(1, '09:00', '09:50', profesionalId: 7, salaId: 5, sesionKey: $clave);
        $candidato = $this->candidato('09:00', '09:50', pacienteId: 2, profesionalId: 7, salaId: 5);

        $this->assertSame([], $this->rule->evaluar($candidato, [$existente], 99));
    }

    public function test_room_is_a_warning_and_holiday_is_informative(): void
    {
        $existente = $this->existente(1, '09:00', '10:00', pacienteId: 1, salaId: 5, servicioId: 10);
        $candidato = $this->candidato('09:30', '10:20', pacienteId: 2, salaId: 5, servicioId: 11);

        $hallazgos = $this->rule->evaluar(
            $candidato,
            [$existente],
            99,
            ['2025-12-02' => 'Feriado de prueba'],
        );

        $tipos = array_map(static fn ($hallazgo): string => $hallazgo->tipo, $hallazgos);
        $this->assertContains('sala', $tipos);
        $this->assertContains('feriado', $tipos);

        foreach ($hallazgos as $hallazgo) {
            if ($hallazgo->tipo === 'sala') {
                $this->assertSame(SeveridadHallazgo::ADVERTENCIA, $hallazgo->severidad);
            }

            if ($hallazgo->tipo === 'feriado') {
                $this->assertSame(SeveridadHallazgo::INFORMATIVO, $hallazgo->severidad);
            }
        }
    }

    private function existente(
        int $id,
        string $inicio,
        string $fin,
        EstadoTurno $estado = EstadoTurno::PROGRAMADO,
        int $pacienteId = 1,
        ?int $profesionalId = null,
        ?int $salaId = null,
        int $servicioId = 10,
        ?string $sesionKey = null,
    ): TurnoExistente {
        return new TurnoExistente(
            id: $id,
            sucursalId: 1,
            pacienteId: $pacienteId,
            profesionalId: $profesionalId,
            salaId: $salaId,
            servicioId: $servicioId,
            fecha: '2025-12-02',
            horaInicio: $inicio,
            horaFin: $fin,
            estado: $estado,
            sesionKey: $sesionKey ?? SesionGrupalRule::clave($salaId, '2025-12-02', $inicio, $fin, $servicioId),
        );
    }

    private function candidato(
        string $inicio,
        string $fin,
        int $pacienteId = 1,
        ?int $profesionalId = null,
        ?int $salaId = null,
        int $servicioId = 10,
    ): TurnoCandidato {
        return new TurnoCandidato(
            pacienteId: $pacienteId,
            profesionalId: $profesionalId,
            salaId: $salaId,
            servicioId: $servicioId,
            fecha: '2025-12-02',
            horaInicio: $inicio,
            horaFin: $fin,
            sesionKey: SesionGrupalRule::clave($salaId, '2025-12-02', $inicio, $fin, $servicioId),
        );
    }
}
