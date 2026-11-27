<?php

namespace Tests\Unit\Modules\AgendaTurnos\Rules;

use App\Modules\AgendaTurnos\Rules\GeneradorFechasRule;
use App\Modules\AgendaTurnos\Support\FechaGenerada;
use PHPUnit\Framework\TestCase;

/**
 * [Programación de Turnos] RN-002 / RF-003 / RF-015 / CA-001 / CA-005.
 */
final class GeneradorFechasRuleTest extends TestCase
{
    private GeneradorFechasRule $rule;

    protected function setUp(): void
    {
        parent::setUp();
        $this->rule = new GeneradorFechasRule;
    }

    public function test_ca_001_generates_nine_dates_for_tuesday_and_thursday_in_december(): void
    {
        $fechas = $this->rule->generar(
            diasSemana: [2, 4],
            fechaInicio: '2025-12-01',
            fechaFin: '2025-12-31',
            hoy: '2025-11-30',
            horaActual: '23:59',
            horaInicio: '09:00',
        );

        $this->assertCount(9, $fechas);
        $this->assertSame(
            [
                '2025-12-02',
                '2025-12-04',
                '2025-12-09',
                '2025-12-11',
                '2025-12-16',
                '2025-12-18',
                '2025-12-23',
                '2025-12-25',
                '2025-12-30',
            ],
            array_map(static fn (FechaGenerada $fecha): string => $fecha->fecha, $fechas),
        );
        $this->assertTrue(array_reduce(
            $fechas,
            static fn (bool $carry, FechaGenerada $fecha): bool => $carry && $fecha->esGenerable(),
            true,
        ));
    }

    public function test_ca_005_period_ending_early_has_six_dates(): void
    {
        $fechas = $this->rule->generar(
            [2, 4],
            '2025-12-01',
            '2025-12-20',
            '2025-11-30',
            '23:59',
            '09:00',
        );

        $this->assertCount(6, $fechas);
    }

    public function test_sunday_is_never_generated(): void
    {
        $fechas = $this->rule->generar(
            [7],
            '2025-12-01',
            '2025-12-31',
            '2025-11-30',
            '23:59',
            '09:00',
        );

        $this->assertSame([], $fechas);
    }

    public function test_saturday_is_allowed(): void
    {
        $fechas = $this->rule->generar(
            [6],
            '2025-12-01',
            '2025-12-07',
            '2025-11-30',
            '23:59',
            '09:00',
        );

        $this->assertCount(1, $fechas);
        $this->assertSame('2025-12-06', $fechas[0]->fecha);
    }

    public function test_a_holiday_is_excluded_with_its_description(): void
    {
        $fechas = $this->rule->generar(
            [2],
            '2025-12-01',
            '2025-12-31',
            '2025-11-30',
            '23:59',
            '09:00',
            null,
            ['2025-12-09' => 'Feriado de prueba'],
        );

        $feriado = $this->buscar($fechas, '2025-12-09');

        $this->assertNotNull($feriado);
        $this->assertSame(FechaGenerada::FERIADO, $feriado->estado);
        $this->assertStringContainsString('Feriado de prueba', (string) $feriado->motivo);
        $this->assertSame(4, $this->contarGenerables($fechas));
    }

    public function test_past_dates_are_reported_and_not_generated(): void
    {
        $fechas = $this->rule->generar(
            [2, 4],
            '2025-12-01',
            '2025-12-11',
            '2025-12-10',
            '10:00',
            '09:00',
        );

        $pasadas = array_values(array_filter(
            $fechas,
            static fn (FechaGenerada $fecha): bool => $fecha->estado === FechaGenerada::PASADA,
        ));

        $this->assertCount(3, $pasadas);
        $this->assertSame(1, $this->contarGenerables($fechas));
        $this->assertSame('2025-12-11', $fechas[3]->fecha);
    }

    public function test_today_before_the_start_time_is_generable(): void
    {
        $fechas = $this->rule->generar(
            [3],
            '2025-12-10',
            '2025-12-10',
            '2025-12-10',
            '08:59',
            '09:00',
        );

        $this->assertCount(1, $fechas);
        $this->assertTrue($fechas[0]->esGenerable());
    }

    public function test_today_at_the_start_time_is_past(): void
    {
        $fechas = $this->rule->generar(
            [3],
            '2025-12-10',
            '2025-12-10',
            '2025-12-10',
            '09:00',
            '09:00',
        );

        $this->assertCount(1, $fechas);
        $this->assertFalse($fechas[0]->esGenerable());
        $this->assertSame(FechaGenerada::PASADA, $fechas[0]->estado);
    }

    public function test_amount_limit_takes_the_first_n_and_reports_the_rest_as_omitted(): void
    {
        $fechas = $this->rule->generar(
            [2, 4],
            '2025-12-01',
            '2025-12-31',
            '2025-11-30',
            '23:59',
            '09:00',
            2,
        );

        $this->assertSame(2, $this->contarGenerables($fechas));
        $this->assertSame(7, count(array_filter(
            $fechas,
            static fn (FechaGenerada $fecha): bool => $fecha->estado === FechaGenerada::OMITIDA,
        )));
    }

    public function test_invalid_range_returns_no_dates(): void
    {
        $this->assertSame([], $this->rule->generar(
            [2],
            '2025-12-31',
            '2025-12-01',
            '2025-11-30',
            '23:59',
            '09:00',
        ));
    }

    /**
     * @param  list<FechaGenerada>  $fechas
     */
    private function buscar(array $fechas, string $fecha): ?FechaGenerada
    {
        foreach ($fechas as $item) {
            if ($item->fecha === $fecha) {
                return $item;
            }
        }

        return null;
    }

    /**
     * @param  list<FechaGenerada>  $fechas
     */
    private function contarGenerables(array $fechas): int
    {
        return count(array_filter(
            $fechas,
            static fn (FechaGenerada $fecha): bool => $fecha->esGenerable(),
        ));
    }
}
