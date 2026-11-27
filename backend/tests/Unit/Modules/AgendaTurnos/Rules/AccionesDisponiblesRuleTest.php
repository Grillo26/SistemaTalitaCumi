<?php

namespace Tests\Unit\Modules\AgendaTurnos\Rules;

use App\Modules\AgendaTurnos\Enums\EstadoTurno;
use App\Modules\AgendaTurnos\Rules\AccionesDisponiblesRule;
use App\Modules\AgendaTurnos\Support\AccionTurnoDefinicion;
use PHPUnit\Framework\TestCase;

final class AccionesDisponiblesRuleTest extends TestCase
{
    public function test_filters_by_state_permission_and_registered_actions(): void
    {
        $acciones = [
            new AccionTurnoDefinicion(
                codigo: 'registrar_asistencia',
                etiqueta: 'Registrar asistencia',
                permiso: 'asistencia.registrar',
                estadosPermitidos: [EstadoTurno::PROGRAMADO],
                orden: 20,
            ),
            new AccionTurnoDefinicion(
                codigo: 'cancelar',
                etiqueta: 'Cancelar',
                permiso: 'turno.cancelar',
                estadosPermitidos: [EstadoTurno::PROGRAMADO, EstadoTurno::ASISTIO],
                orden: 10,
            ),
            new AccionTurnoDefinicion(
                codigo: 'reprogramar',
                etiqueta: 'Reprogramar',
                permiso: 'turno.reprogramar',
                estadosPermitidos: [EstadoTurno::PROGRAMADO],
                orden: 30,
            ),
        ];

        $disponibles = (new AccionesDisponiblesRule)(
            EstadoTurno::PROGRAMADO,
            ['turno.cancelar', 'asistencia.registrar'],
            $acciones,
            [],
        );

        $this->assertSame(['cancelar', 'registrar_asistencia'], array_map(
            static fn (AccionTurnoDefinicion $accion): string => $accion->codigo,
            $disponibles,
        ));
    }

    public function test_does_not_offer_non_repeatable_registered_action(): void
    {
        $accion = new AccionTurnoDefinicion(
            codigo: 'registrar_asistencia',
            etiqueta: 'Registrar asistencia',
            permiso: 'asistencia.registrar',
            estadosPermitidos: [EstadoTurno::PROGRAMADO],
        );

        $disponibles = (new AccionesDisponiblesRule)(
            EstadoTurno::PROGRAMADO,
            ['asistencia.registrar'],
            [$accion],
            ['registrar_asistencia'],
        );

        $this->assertSame([], $disponibles);
    }

    public function test_repeatable_action_remains_available(): void
    {
        $accion = new AccionTurnoDefinicion(
            codigo: 'registrar_asistencia',
            etiqueta: 'Registrar asistencia',
            permiso: 'asistencia.registrar',
            estadosPermitidos: [EstadoTurno::PROGRAMADO],
            repetible: true,
        );

        $disponibles = (new AccionesDisponiblesRule)(
            EstadoTurno::PROGRAMADO,
            ['asistencia.registrar'],
            [$accion],
            ['registrar_asistencia'],
        );

        $this->assertCount(1, $disponibles);
    }

    public function test_state_must_be_allowed(): void
    {
        $accion = new AccionTurnoDefinicion(
            codigo: 'cancelar',
            etiqueta: 'Cancelar',
            permiso: 'turno.cancelar',
            estadosPermitidos: [EstadoTurno::PROGRAMADO],
        );

        $disponibles = (new AccionesDisponiblesRule)(
            EstadoTurno::ASISTIO,
            ['turno.cancelar'],
            [$accion],
        );

        $this->assertSame([], $disponibles);
    }
}
