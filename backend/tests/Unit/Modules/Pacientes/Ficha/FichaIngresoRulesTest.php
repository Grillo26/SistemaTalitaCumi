<?php

namespace Tests\Unit\Modules\Pacientes\Ficha;

use App\Modules\Pacientes\Enums\EstadoFichaIngreso;
use App\Modules\Pacientes\Exceptions\FichaIngresoArchivedException;
use App\Modules\Pacientes\Exceptions\FichaIngresoDateInvalidException;
use App\Modules\Pacientes\Exceptions\FichaIngresoStatusTransitionException;
use App\Modules\Pacientes\Models\FichaIngreso;
use App\Modules\Pacientes\Rules\FichaIngresoDateRule;
use App\Modules\Pacientes\Rules\FichaIngresoStatusRule;
use Carbon\CarbonImmutable;
use PHPUnit\Framework\TestCase;

final class FichaIngresoRulesTest extends TestCase
{
    public function test_ingreso_date_cannot_be_future(): void
    {
        $rule = new FichaIngresoDateRule;
        $today = CarbonImmutable::parse('2026-11-21');

        $rule->assertNotFuture($today, $today);
        $rule->assertNotFuture($today->subDay(), $today);
        $this->addToAssertionCount(2);

        $this->expectException(FichaIngresoDateInvalidException::class);
        $rule->assertNotFuture($today->addDay(), $today);
    }

    public function test_archived_ficha_cannot_be_edited_or_completed(): void
    {
        $rule = new FichaIngresoStatusRule;
        $ficha = new FichaIngreso(['estado' => EstadoFichaIngreso::ARCHIVADA->value]);

        $this->expectException(FichaIngresoArchivedException::class);
        $rule->assertCanEdit($ficha);
    }

    public function test_complete_requires_a_draft_and_is_not_repeatable(): void
    {
        $rule = new FichaIngresoStatusRule;
        $draft = new FichaIngreso(['estado' => EstadoFichaIngreso::BORRADOR->value]);
        $complete = new FichaIngreso(['estado' => EstadoFichaIngreso::COMPLETA->value]);

        $rule->assertCanComplete($draft);
        $this->addToAssertionCount(1);

        $this->expectException(FichaIngresoStatusTransitionException::class);
        $rule->assertCanComplete($complete);
    }

    public function test_reactivation_only_applies_to_archived_and_restores_previous_state(): void
    {
        $rule = new FichaIngresoStatusRule;
        $draft = new FichaIngreso(['estado' => EstadoFichaIngreso::BORRADOR->value]);

        $this->expectException(FichaIngresoStatusTransitionException::class);
        $rule->assertCanReactivate($draft);
    }

    public function test_reactivation_target_keeps_complete_state(): void
    {
        $rule = new FichaIngresoStatusRule;

        $borrador = new FichaIngreso([
            'estado' => EstadoFichaIngreso::ARCHIVADA->value,
            'estado_anterior' => EstadoFichaIngreso::BORRADOR->value,
        ]);
        $completa = new FichaIngreso([
            'estado' => EstadoFichaIngreso::ARCHIVADA->value,
            'estado_anterior' => EstadoFichaIngreso::COMPLETA->value,
        ]);

        $this->assertSame(EstadoFichaIngreso::BORRADOR, $rule->reactivationTarget($borrador));
        $this->assertSame(EstadoFichaIngreso::COMPLETA, $rule->reactivationTarget($completa));
    }
}
