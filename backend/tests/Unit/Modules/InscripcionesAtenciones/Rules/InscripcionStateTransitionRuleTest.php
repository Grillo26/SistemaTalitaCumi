<?php

namespace Tests\Unit\Modules\InscripcionesAtenciones\Rules;

use App\Modules\InscripcionesAtenciones\Enums\EstadoInscripcionSala;
use App\Modules\InscripcionesAtenciones\Exceptions\InscripcionNoGestionableException;
use App\Modules\InscripcionesAtenciones\Exceptions\InscripcionRetiradaException;
use App\Modules\InscripcionesAtenciones\Rules\InscripcionStateTransitionRule;
use PHPUnit\Framework\TestCase;

final class InscripcionStateTransitionRuleTest extends TestCase
{
    public function test_active_inscription_allows_edit_renew_and_withdraw(): void
    {
        InscripcionStateTransitionRule::assertPuedeEditar(EstadoInscripcionSala::ACTIVA);
        InscripcionStateTransitionRule::assertPuedeRenovar(EstadoInscripcionSala::ACTIVA);
        InscripcionStateTransitionRule::assertPuedeRetirar(EstadoInscripcionSala::ACTIVA);

        $this->assertTrue(true);
    }

    public function test_retired_inscription_blocks_every_operation(): void
    {
        $this->expectException(InscripcionRetiradaException::class);

        InscripcionStateTransitionRule::assertPuedeEditar(EstadoInscripcionSala::RETIRADA);
    }

    public function test_reserved_state_blocks_operation_with_specific_error(): void
    {
        $this->expectException(InscripcionNoGestionableException::class);

        InscripcionStateTransitionRule::assertPuedeRenovar(EstadoInscripcionSala::EN_PAUSA);
    }
}
