<?php

namespace Tests\Feature\Modules\InscripcionesAtenciones;

use App\Modules\InscripcionesAtenciones\Contracts\OrigenTurnosContract;

/**
 * [Programación de Turnos] contrato de lectura que consume el módulo 07.
 */
final class OrigenTurnosContractTest extends PaquetesSesionesTestCase
{
    public function test_package_origin_exposes_pattern_professional_and_effective_state(): void
    {
        $escenario = $this->escenarioPaquete();
        $paquete = $this->crearPaquete($escenario)->assertCreated()->json('data');

        $resumen = app(OrigenTurnosContract::class)->obtener(
            'paquete_sesiones',
            (int) $paquete['id'],
        );

        $this->assertNotNull($resumen);
        $this->assertSame('paquete_sesiones', $resumen->tipo);
        $this->assertSame((int) $paquete['id'], $resumen->id);
        $this->assertSame($escenario['branch']->id, $resumen->sucursalId);
        $this->assertSame($escenario['paciente']->id, $resumen->pacienteId);
        $this->assertSame($escenario['servicio']->id, $resumen->servicioId);
        $this->assertNull($resumen->salaId);
        $this->assertSame($escenario['profesional']->id, $resumen->profesionalId);
        $this->assertSame(10, $resumen->cantidadSesiones);
        $this->assertSame([1, 3], $resumen->diasSemana);
        $this->assertSame('08:00', substr((string) $resumen->horaInicio, 0, 5));
        $this->assertSame(50, $resumen->duracionMinutos);
        $this->assertSame('activa', $resumen->estadoEfectivo);
    }

    public function test_room_origin_leaves_the_pattern_to_the_room_contract(): void
    {
        $escenario = $this->scenario();
        $inscripcion = $this->crearInscripcion($escenario)->assertCreated()->json('data');

        $resumen = app(OrigenTurnosContract::class)->obtener(
            'inscripcion_sala',
            (int) $inscripcion['id'],
        );

        $this->assertNotNull($resumen);
        $this->assertSame('inscripcion_sala', $resumen->tipo);
        $this->assertSame($escenario['sala']->id, $resumen->salaId);
        $this->assertNull($resumen->diasSemana);
        $this->assertNull($resumen->horaInicio);
        $this->assertNull($resumen->duracionMinutos);
        $this->assertNull($resumen->cantidadSesiones);
        $this->assertSame('activa', $resumen->estadoEfectivo);
    }

    public function test_expired_package_reports_the_derived_state(): void
    {
        $escenario = $this->escenarioPaquete();
        $paquete = $this->crearPaquete($escenario, [
            'fecha_inicio' => '2026-10-01',
            'fecha_fin' => '2026-10-31',
        ])->assertCreated()->json('data');

        $resumen = app(OrigenTurnosContract::class)->obtener(
            'paquete_sesiones',
            (int) $paquete['id'],
        );

        $this->assertNotNull($resumen);
        $this->assertSame('vencida', $resumen->estadoEfectivo);
    }

    public function test_unknown_origin_returns_null_and_invalid_type_is_rejected(): void
    {
        $contract = app(OrigenTurnosContract::class);

        $this->assertNull($contract->obtener('paquete_sesiones', 999999));
        $this->assertNull($contract->obtener('inscripcion_sala', 999999));

        $this->expectException(\InvalidArgumentException::class);

        $contract->obtener('otro_tipo', 1);
    }
}
