<?php

namespace Tests\Unit\Modules\AgendaTurnos\Rules;

use App\Modules\AgendaTurnos\Enums\OrigenMotivoTurno;
use App\Modules\AgendaTurnos\Rules\MotivoObligatorioRule;
use App\Modules\AgendaTurnos\Support\MotivoTurnoData;
use PHPUnit\Framework\TestCase;

/**
 * [Reprogramación y Cancelación] RN-001 / RF-003.
 */
final class MotivoObligatorioRuleTest extends TestCase
{
    public function test_requires_an_active_applicable_motive(): void
    {
        $rule = new MotivoObligatorioRule;

        $this->assertSame(['Seleccioná un motivo de la lista.'], $rule->errores(null, 'cancelar', null));
        $this->assertSame(
            ['El motivo seleccionado está inactivo.'],
            $rule->errores($this->motivo(activo: false), 'cancelar', null),
        );
        $this->assertSame(
            ['El motivo seleccionado no aplica a esta acción.'],
            $rule->errores($this->motivo(aplicaCancelar: false), 'cancelar', null),
        );
    }

    public function test_requires_text_when_the_catalog_says_so(): void
    {
        $rule = new MotivoObligatorioRule;

        $this->assertSame(
            ['El motivo seleccionado exige un texto explicativo.'],
            $rule->errores($this->motivo(requiereTexto: true), 'reprogramar', '   '),
        );
        $this->assertSame([], $rule->errores(
            $this->motivo(requiereTexto: true),
            'reprogramar',
            'Detalle',
        ));
    }

    public function test_accepts_a_valid_motive(): void
    {
        $this->assertSame([], (new MotivoObligatorioRule)->errores(
            $this->motivo(),
            'cancelar',
            null,
        ));
    }

    private function motivo(
        bool $activo = true,
        bool $aplicaCancelar = true,
        bool $requiereTexto = false,
    ): MotivoTurnoData {
        return new MotivoTurnoData(
            id: 1,
            codigo: 'otro',
            nombre: 'Otro',
            aplicaReprogramar: true,
            aplicaCancelar: $aplicaCancelar,
            origen: OrigenMotivoTurno::PACIENTE,
            descuentaSaldo: null,
            requiereTexto: $requiereTexto,
            activo: $activo,
        );
    }
}
