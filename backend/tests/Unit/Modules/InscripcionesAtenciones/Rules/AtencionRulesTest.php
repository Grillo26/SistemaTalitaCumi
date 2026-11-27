<?php

namespace Tests\Unit\Modules\InscripcionesAtenciones\Rules;

use App\Modules\InscripcionesAtenciones\Enums\MotivoBajaAtencion;
use App\Modules\InscripcionesAtenciones\Exceptions\AtencionFechasInvalidasException;
use App\Modules\InscripcionesAtenciones\Exceptions\EntrevistaOmitidaInvalidaException;
use App\Modules\InscripcionesAtenciones\Exceptions\NotasAdministrativasInvalidasException;
use App\Modules\InscripcionesAtenciones\Exceptions\ProfesionalesInsuficientesException;
use App\Modules\InscripcionesAtenciones\Rules\AtencionFechasRule;
use App\Modules\InscripcionesAtenciones\Rules\EntrevistaOmitidaRule;
use App\Modules\InscripcionesAtenciones\Rules\MotivoBajaAtencionRule;
use App\Modules\InscripcionesAtenciones\Rules\NotasAdministrativasRule;
use App\Modules\InscripcionesAtenciones\Rules\ProfesionalesRequeridosRule;
use PHPUnit\Framework\TestCase;

final class AtencionRulesTest extends TestCase
{
    public function test_interview_requires_a_valid_single_date(): void
    {
        AtencionFechasRule::assertEntrevista('2026-11-25');

        $this->expectException(AtencionFechasInvalidasException::class);
        AtencionFechasRule::assertEntrevista('2026-02-31');
    }

    public function test_diagnosis_accepts_same_day_and_multiday_but_rejects_reverse_range(): void
    {
        AtencionFechasRule::assertDiagnostico('2026-11-25', '2026-11-25');
        AtencionFechasRule::assertDiagnostico('2026-11-25', '2026-11-28');

        $this->expectException(AtencionFechasInvalidasException::class);
        AtencionFechasRule::assertDiagnostico('2026-11-28', '2026-11-25');
    }

    public function test_diagnosis_professional_coverage_allows_unassigned_and_blocks_partial(): void
    {
        $requisitos = [
            ['especialidad_id' => 1, 'especialidad_nombre' => 'Psicología', 'cantidad_minima' => 2],
        ];

        ProfesionalesRequeridosRule::assertCobertura($requisitos, []);

        $profesional = [
            'id' => 10,
            'nombre_completo' => 'Profesional',
            'especialidades' => [['id' => 1, 'nombre' => 'Psicología']],
        ];

        $this->expectException(ProfesionalesInsuficientesException::class);
        ProfesionalesRequeridosRule::assertCobertura($requisitos, [$profesional]);
    }

    public function test_diagnosis_professional_coverage_accepts_complete_count(): void
    {
        $requisitos = [
            ['especialidad_id' => 1, 'especialidad_nombre' => 'Psicología', 'cantidad_minima' => 1],
        ];

        $profesionales = [
            [
                'id' => 10,
                'nombre_completo' => 'Profesional',
                'especialidades' => [['id' => 1, 'nombre' => 'Psicología']],
            ],
        ];

        ProfesionalesRequeridosRule::assertCobertura($requisitos, $profesionales);
        $this->addToAssertionCount(1);
    }

    public function test_interview_omission_requires_a_decision(): void
    {
        EntrevistaOmitidaRule::assertValida(null, true, null);
        EntrevistaOmitidaRule::assertValida(7, false, null);

        $this->expectException(EntrevistaOmitidaInvalidaException::class);
        EntrevistaOmitidaRule::assertValida(null, false, null);
    }

    public function test_omission_and_link_are_mutually_exclusive(): void
    {
        $this->expectException(EntrevistaOmitidaInvalidaException::class);
        EntrevistaOmitidaRule::assertValida(7, true, null);
    }

    public function test_notes_and_withdrawal_reason_limits(): void
    {
        NotasAdministrativasRule::assertValidas(str_repeat('a', 500));
        MotivoBajaAtencionRule::assertValido(MotivoBajaAtencion::ERROR_REGISTRO, null);

        $this->expectException(NotasAdministrativasInvalidasException::class);
        NotasAdministrativasRule::assertValidas(str_repeat('a', 501));
    }
}
