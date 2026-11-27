<?php

namespace Tests\Unit\Modules\InscripcionesAtenciones\Rules;

use App\Modules\InscripcionesAtenciones\Enums\EstadoInscripcion;
use App\Modules\InscripcionesAtenciones\Enums\MotivoPausa;
use App\Modules\InscripcionesAtenciones\Exceptions\MotivoEstadoInvalidoException;
use App\Modules\InscripcionesAtenciones\Rules\EstadoEfectivoInscripcionRule;
use App\Modules\InscripcionesAtenciones\Rules\EstadoInscripcionTransitionRule;
use App\Modules\InscripcionesAtenciones\Rules\EstadoPacienteTransitionRule;
use App\Modules\InscripcionesAtenciones\Rules\InscripcionAdmiteAsistenciaRule;
use App\Modules\InscripcionesAtenciones\Rules\MotivoEstadoObligatorioRule;
use App\Modules\InscripcionesAtenciones\Rules\RetiroEnCascadaRule;
use App\Modules\Pacientes\Enums\EstadoPaciente;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

final class EstadoRulesTest extends TestCase
{
    /**
     * @return array<string, array{EstadoPaciente, EstadoPaciente, bool}>
     */
    public static function pacienteTransitions(): array
    {
        return [
            'activo a activo' => [EstadoPaciente::ACTIVO, EstadoPaciente::ACTIVO, false],
            'activo a pausa' => [EstadoPaciente::ACTIVO, EstadoPaciente::EN_PAUSA, true],
            'activo a retirado' => [EstadoPaciente::ACTIVO, EstadoPaciente::RETIRADO, true],
            'pausa a activo' => [EstadoPaciente::EN_PAUSA, EstadoPaciente::ACTIVO, true],
            'pausa a pausa' => [EstadoPaciente::EN_PAUSA, EstadoPaciente::EN_PAUSA, false],
            'pausa a retirado' => [EstadoPaciente::EN_PAUSA, EstadoPaciente::RETIRADO, true],
            'retirado a activo' => [EstadoPaciente::RETIRADO, EstadoPaciente::ACTIVO, true],
            'retirado a pausa' => [EstadoPaciente::RETIRADO, EstadoPaciente::EN_PAUSA, false],
            'retirado a retirado' => [EstadoPaciente::RETIRADO, EstadoPaciente::RETIRADO, false],
        ];
    }

    #[DataProvider('pacienteTransitions')]
    public function test_paciente_transition_matrix(
        EstadoPaciente $desde,
        EstadoPaciente $hacia,
        bool $permitida,
    ): void {
        $this->assertSame($permitida, EstadoPacienteTransitionRule::permite($desde, $hacia));
    }

    /**
     * @return array<string, array{EstadoInscripcion, EstadoInscripcion, string|null}>
     */
    public static function inscripcionTransitions(): array
    {
        $rows = [];

        foreach (EstadoInscripcion::cases() as $desde) {
            foreach (EstadoInscripcion::cases() as $hacia) {
                $key = $desde->value.'>'.$hacia->value;
                $expected = match ($key) {
                    'activa>en_pausa', 'activa>retirada', 'en_pausa>activa', 'en_pausa>retirada' => EstadoInscripcionTransitionRule::MANUAL,
                    'activa>vencida', 'activa>renovada', 'vencida>renovada' => EstadoInscripcionTransitionRule::SISTEMA,
                    default => null,
                };
                $rows[$key] = [$desde, $hacia, $expected];
            }
        }

        return $rows;
    }

    #[DataProvider('inscripcionTransitions')]
    public function test_inscripcion_transition_matrix(
        EstadoInscripcion $desde,
        EstadoInscripcion $hacia,
        ?string $tipo,
    ): void {
        $resultado = EstadoInscripcionTransitionRule::tipo($desde, $hacia);

        $this->assertSame($tipo, $resultado[1] ?? null);
        $this->assertSame($tipo !== null, EstadoInscripcionTransitionRule::esPermitida($desde, $hacia));
    }

    public function test_estado_efectivo_precedence_and_date_boundaries(): void
    {
        $hoy = '2026-11-25';

        $this->assertSame(
            EstadoInscripcion::RETIRADA,
            EstadoEfectivoInscripcionRule::effective(EstadoInscripcion::RETIRADA, '2030-01-01', $hoy, true),
        );
        $this->assertSame(
            EstadoInscripcion::EN_PAUSA,
            EstadoEfectivoInscripcionRule::effective(EstadoInscripcion::EN_PAUSA, '2020-01-01', $hoy, true),
        );
        $this->assertSame(
            EstadoInscripcion::RENOVADA,
            EstadoEfectivoInscripcionRule::effective(EstadoInscripcion::ACTIVA, '2030-01-01', $hoy, true),
        );
        $this->assertSame(
            EstadoInscripcion::VENCIDA,
            EstadoEfectivoInscripcionRule::effective(EstadoInscripcion::ACTIVA, '2026-11-24', $hoy, false),
        );
        $this->assertSame(
            EstadoInscripcion::ACTIVA,
            EstadoEfectivoInscripcionRule::effective(EstadoInscripcion::ACTIVA, '2026-11-25', $hoy, false),
        );
        $this->assertSame(
            EstadoInscripcion::ACTIVA,
            EstadoEfectivoInscripcionRule::effective(EstadoInscripcion::ACTIVA, '2026-11-26', $hoy, false),
        );
        $this->assertSame(
            EstadoInscripcion::VENCIDA,
            EstadoEfectivoInscripcionRule::effective(EstadoInscripcion::VENCIDA, '2030-01-01', $hoy, false),
        );
    }

    public function test_motivo_rule_requires_code_and_other_detail(): void
    {
        MotivoEstadoObligatorioRule::assert('viaje', null, MotivoPausa::values(), true);
        MotivoEstadoObligatorioRule::assert('otro', 'Detalle', MotivoPausa::values(), true);

        $this->expectException(MotivoEstadoInvalidoException::class);
        MotivoEstadoObligatorioRule::assert('otro', ' ', MotivoPausa::values(), true);
    }

    public function test_motivo_rule_rejects_missing_or_unknown_code(): void
    {
        $this->expectException(MotivoEstadoInvalidoException::class);
        MotivoEstadoObligatorioRule::assert('', null, MotivoPausa::values(), true);
    }

    public function test_motivo_rule_rejects_unknown_code(): void
    {
        $this->expectException(MotivoEstadoInvalidoException::class);
        MotivoEstadoObligatorioRule::assert('inventado', null, MotivoPausa::values(), true);
    }

    public function test_admission_and_cascade_rules(): void
    {
        $this->assertTrue(InscripcionAdmiteAsistenciaRule::permite(EstadoInscripcion::ACTIVA));
        $this->assertFalse(InscripcionAdmiteAsistenciaRule::permite(EstadoInscripcion::EN_PAUSA));
        $this->assertFalse(InscripcionAdmiteAsistenciaRule::permite(EstadoInscripcion::VENCIDA));
        $this->assertFalse(InscripcionAdmiteAsistenciaRule::permite(EstadoInscripcion::RENOVADA));
        $this->assertFalse(InscripcionAdmiteAsistenciaRule::permite(EstadoInscripcion::RETIRADA));

        $this->assertTrue(RetiroEnCascadaRule::arrastra(EstadoInscripcion::ACTIVA));
        $this->assertTrue(RetiroEnCascadaRule::arrastra(EstadoInscripcion::EN_PAUSA));
        $this->assertFalse(RetiroEnCascadaRule::arrastra(EstadoInscripcion::VENCIDA));
        $this->assertFalse(RetiroEnCascadaRule::arrastra(EstadoInscripcion::RENOVADA));
        $this->assertFalse(RetiroEnCascadaRule::arrastra(EstadoInscripcion::RETIRADA));
    }
}
