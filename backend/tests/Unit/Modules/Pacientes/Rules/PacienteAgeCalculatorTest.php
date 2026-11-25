<?php

namespace Tests\Unit\Modules\Pacientes\Rules;

use App\Modules\Pacientes\Exceptions\PacienteConfigurationUnavailableException;
use App\Modules\Pacientes\Exceptions\PacienteInvalidBirthDateException;
use App\Modules\Pacientes\Models\Paciente;
use App\Modules\Pacientes\Support\PacienteAgeCalculator;
use Carbon\CarbonImmutable;
use Tests\Support\FakeParameterReaderContract;
use Tests\TestCase;

final class PacienteAgeCalculatorTest extends TestCase
{
    protected function tearDown(): void
    {
        CarbonImmutable::setTestNow();

        parent::tearDown();
    }

    public function test_calculates_age_using_configured_timezone(): void
    {
        CarbonImmutable::setTestNow(CarbonImmutable::parse('2026-11-22 01:00:00', 'UTC'));

        $calculator = new PacienteAgeCalculator(new FakeParameterReaderContract([
            'zona_horaria' => 'America/La_Paz',
        ]));

        $paciente = new Paciente(['fecha_nacimiento' => '2024-11-22']);
        $edad = $calculator->for($paciente);

        $this->assertSame(1, $edad['anios']);
        $this->assertSame(11, $edad['meses']);
        $this->assertSame(30, $edad['dias']);
        $this->assertSame('1 año y 11 meses', $edad['etiqueta']);
    }

    public function test_under_one_month_uses_days(): void
    {
        CarbonImmutable::setTestNow(CarbonImmutable::parse('2026-11-21 12:00:00', 'America/La_Paz'));

        $calculator = new PacienteAgeCalculator(new FakeParameterReaderContract([
            'zona_horaria' => 'America/La_Paz',
        ]));

        $paciente = new Paciente(['fecha_nacimiento' => '2026-11-01']);
        $edad = $calculator->for($paciente);

        $this->assertSame('20 días', $edad['etiqueta']);
    }

    public function test_under_one_year_uses_months(): void
    {
        CarbonImmutable::setTestNow(CarbonImmutable::parse('2026-11-21 12:00:00', 'America/La_Paz'));

        $calculator = new PacienteAgeCalculator(new FakeParameterReaderContract([
            'zona_horaria' => 'America/La_Paz',
        ]));

        $paciente = new Paciente(['fecha_nacimiento' => '2026-01-21']);
        $edad = $calculator->for($paciente);

        $this->assertSame('10 meses', $edad['etiqueta']);
    }

    public function test_three_years_uses_years_only(): void
    {
        CarbonImmutable::setTestNow(CarbonImmutable::parse('2026-11-21 12:00:00', 'America/La_Paz'));

        $calculator = new PacienteAgeCalculator(new FakeParameterReaderContract([
            'zona_horaria' => 'America/La_Paz',
        ]));

        $paciente = new Paciente(['fecha_nacimiento' => '2023-11-21']);
        $edad = $calculator->for($paciente);

        $this->assertSame('3 años', $edad['etiqueta']);
    }

    public function test_future_birth_date_is_rejected(): void
    {
        CarbonImmutable::setTestNow(CarbonImmutable::parse('2026-11-21 12:00:00', 'America/La_Paz'));

        $calculator = new PacienteAgeCalculator(new FakeParameterReaderContract([
            'zona_horaria' => 'America/La_Paz',
        ]));

        $this->expectException(PacienteInvalidBirthDateException::class);

        $calculator->for(new Paciente(['fecha_nacimiento' => '2026-11-22']));
    }

    public function test_missing_timezone_fails_explicitly(): void
    {
        $calculator = new PacienteAgeCalculator(new FakeParameterReaderContract);

        $this->expectException(PacienteConfigurationUnavailableException::class);

        $calculator->for(new Paciente(['fecha_nacimiento' => '2020-01-01']));
    }
}
