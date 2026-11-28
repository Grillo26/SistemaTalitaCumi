<?php

namespace Tests\Unit\Modules\AgendaTurnos\Rules;

use App\Modules\AgendaTurnos\Enums\EstadoTurno;
use App\Modules\AgendaTurnos\Enums\SeveridadHallazgo;
use App\Modules\AgendaTurnos\Rules\SesionGrupalRule;
use App\Modules\AgendaTurnos\Rules\TurnoSolapamientoRule;
use App\Modules\AgendaTurnos\Support\TurnoCandidato;
use App\Modules\AgendaTurnos\Support\TurnoExistente;
use PHPUnit\Framework\TestCase;

/**
 * [Programación de Turnos] RF-004 / RF-005 / RF-006 / RT-002.
 */
final class TurnoSolapamientoRuleTest extends TestCase
{
    private TurnoSolapamientoRule $rule;

    protected function setUp(): void
    {
        parent::setUp();
        $this->rule = new TurnoSolapamientoRule;
    }

    public function test_partial_overlap_blocks_the_same_patient(): void
    {
        $existente = $this->existente(id: 1, inicio: '09:00', fin: '10:00');
        $candidato = $this->candidato(inicio: '09:30', fin: '10:30');

        $hallazgos = $this->rule->evaluar($candidato, [$existente]);

        $this->assertCount(1, $hallazgos);
        $this->assertSame('paciente', $hallazgos[0]->tipo);
        $this->assertSame(SeveridadHallazgo::BLOQUEO, $hallazgos[0]->severidad);
    }

    public function test_contiguous_turns_do_not_overlap(): void
    {
        $existente = $this->existente(id: 1, inicio: '09:00', fin: '10:00');
        $candidato = $this->candidato(inicio: '10:00', fin: '11:00');

        $this->assertSame([], $this->rule->evaluar($candidato, [$existente]));
    }

    public function test_same_range_overlaps(): void
    {
        $existente = $this->existente(id: 1, inicio: '09:00', fin: '10:00');
        $candidato = $this->candidato(inicio: '09:00', fin: '10:00');

        $this->assertCount(1, $this->rule->evaluar($candidato, [$existente]));
    }

    public function test_cancelled_and_reprogrammed_turns_are_ignored(): void
    {
        $cancelado = $this->existente(1, estado: EstadoTurno::CANCELADO);
        $reprogramado = $this->existente(2, estado: EstadoTurno::REPROGRAMADO);
        $candidato = $this->candidato();

        $this->assertSame([], $this->rule->evaluar($candidato, [$cancelado, $reprogramado]));
    }

    public function test_excluded_turn_ids_are_ignored(): void
    {
        $existente = $this->existente(1);
        $candidato = $this->candidato();

        $this->assertSame([], $this->rule->evaluar($candidato, [$existente], [1]));
    }

    public function test_same_group_session_does_not_block_professional_or_room(): void
    {
        $clave = SesionGrupalRule::clave(5, '2025-12-02', '09:00', '09:50', 10);
        $existente = $this->existente(
            id: 1,
            pacienteId: 1,
            profesionalId: 7,
            salaId: 5,
            sesionKey: $clave,
        );
        $candidato = $this->candidato(
            pacienteId: 2,
            profesionalId: 7,
            salaId: 5,
            sesionKey: $clave,
            inicio: '09:00',
            fin: '09:50',
        );

        $this->assertSame([], $this->rule->evaluar($candidato, [$existente]));
    }

    public function test_different_session_with_same_professional_and_room_blocks_professional_and_warns_room(): void
    {
        $existente = $this->existente(
            id: 1,
            pacienteId: 1,
            profesionalId: 7,
            salaId: 5,
            servicioId: 10,
        );
        $candidato = $this->candidato(
            pacienteId: 2,
            profesionalId: 7,
            salaId: 5,
            servicioId: 11,
        );

        $hallazgos = $this->rule->evaluar($candidato, [$existente]);
        $tipos = array_map(static fn ($hallazgo): string => $hallazgo->tipo, $hallazgos);

        $this->assertContains('profesional', $tipos);
        $this->assertContains('sala', $tipos);

        foreach ($hallazgos as $hallazgo) {
            if ($hallazgo->tipo === 'profesional') {
                $this->assertSame(SeveridadHallazgo::BLOQUEO, $hallazgo->severidad);
            }

            if ($hallazgo->tipo === 'sala') {
                $this->assertSame(SeveridadHallazgo::ADVERTENCIA, $hallazgo->severidad);
            }
        }
    }

    public function test_room_warning_alone_when_there_is_no_professional(): void
    {
        $existente = $this->existente(id: 1, pacienteId: 1, salaId: 5, servicioId: 10);
        $candidato = $this->candidato(pacienteId: 2, salaId: 5, servicioId: 11);

        $hallazgos = $this->rule->evaluar($candidato, [$existente]);

        $this->assertCount(1, $hallazgos);
        $this->assertSame('sala', $hallazgos[0]->tipo);
        $this->assertSame(SeveridadHallazgo::ADVERTENCIA, $hallazgos[0]->severidad);
    }

    public function test_turns_on_different_dates_do_not_overlap(): void
    {
        $existente = $this->existente(id: 1, fecha: '2025-12-04');
        $candidato = $this->candidato(fecha: '2025-12-02');

        $this->assertSame([], $this->rule->evaluar($candidato, [$existente]));
    }

    private function existente(
        int $id = 1,
        int $pacienteId = 1,
        ?int $profesionalId = null,
        ?int $salaId = null,
        int $servicioId = 10,
        string $fecha = '2025-12-02',
        string $inicio = '09:00',
        string $fin = '09:50',
        EstadoTurno $estado = EstadoTurno::PROGRAMADO,
        ?string $sesionKey = null,
    ): TurnoExistente {
        return new TurnoExistente(
            id: $id,
            sucursalId: 1,
            pacienteId: $pacienteId,
            profesionalId: $profesionalId,
            salaId: $salaId,
            servicioId: $servicioId,
            fecha: $fecha,
            horaInicio: $inicio,
            horaFin: $fin,
            estado: $estado,
            sesionKey: $sesionKey ?? SesionGrupalRule::clave(
                $salaId,
                $fecha,
                $inicio,
                $fin,
                $servicioId,
            ),
        );
    }

    private function candidato(
        int $pacienteId = 1,
        ?int $profesionalId = null,
        ?int $salaId = null,
        int $servicioId = 10,
        string $fecha = '2025-12-02',
        string $inicio = '09:30',
        string $fin = '10:20',
        ?string $sesionKey = null,
    ): TurnoCandidato {
        return new TurnoCandidato(
            pacienteId: $pacienteId,
            profesionalId: $profesionalId,
            salaId: $salaId,
            servicioId: $servicioId,
            fecha: $fecha,
            horaInicio: $inicio,
            horaFin: $fin,
            sesionKey: $sesionKey ?? SesionGrupalRule::clave(
                $salaId,
                $fecha,
                $inicio,
                $fin,
                $servicioId,
            ),
        );
    }
}
