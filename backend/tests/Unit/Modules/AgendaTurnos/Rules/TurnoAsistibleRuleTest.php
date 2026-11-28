<?php

namespace Tests\Unit\Modules\AgendaTurnos\Rules;

use App\Modules\AgendaTurnos\Enums\EstadoTurno;
use App\Modules\AgendaTurnos\Models\Turno;
use App\Modules\AgendaTurnos\Rules\TurnoAsistibleRule;
use App\Modules\AgendaTurnos\Support\AsistenciaClock;
use App\Modules\InscripcionesAtenciones\Contracts\OrigenTurnosResumen;
use App\Modules\Organizacion\Contracts\ParameterReaderContract;
use App\Modules\Organizacion\Enums\ParameterCategory;
use App\Modules\Organizacion\Enums\ParameterType;
use App\Modules\Organizacion\Support\ParameterValue;
use Carbon\CarbonImmutable;
use PHPUnit\Framework\TestCase;

/**
 * 07.04/RF-001, 07.04/RF-008, 07.04/RN-003, 07.04/RN-005.
 */
final class TurnoAsistibleRuleTest extends TestCase
{
    public function test_future_turn_is_blocked(): void
    {
        $rule = $this->rule();
        $turno = $this->turno('2025-11-30', '11:00');

        $errores = $rule->errores(
            $turno,
            false,
            null,
            CarbonImmutable::parse('2025-11-30 10:00:00', 'America/La_Paz'),
        );

        $this->assertContains('El turno todavía no comenzó; no se puede registrar asistencia antes de su hora de inicio.', $errores);
    }

    public function test_turn_at_start_time_is_allowed(): void
    {
        $rule = $this->rule();
        $turno = $this->turno('2025-11-30', '10:00');

        $this->assertSame([], $rule->errores(
            $turno,
            false,
            null,
            CarbonImmutable::parse('2025-11-30 10:00:00', 'America/La_Paz'),
        ));
    }

    public function test_retired_package_is_blocked(): void
    {
        $rule = $this->rule();
        $turno = $this->turno('2025-11-30', '09:00');
        $origen = $this->origen('paquete_sesiones', 'retirada');

        $this->assertContains(
            'El paquete no está disponible para registrar asistencia.',
            $rule->errores($turno, true, $origen, $this->ahora()),
        );
    }

    public function test_expired_package_allows_turn_inside_its_period(): void
    {
        $rule = $this->rule();
        $turno = $this->turno('2025-11-30', '09:00');
        $origen = $this->origen('paquete_sesiones', 'vencida');

        $this->assertSame([], $rule->errores($turno, true, $origen, $this->ahora()));
    }

    public function test_turn_outside_origin_period_is_blocked(): void
    {
        $rule = $this->rule();
        $turno = $this->turno('2025-12-15', '09:00');
        $origen = $this->origen('paquete_sesiones', 'activa', '2025-11-01', '2025-11-30');

        $this->assertContains(
            'El turno está fuera del período de la entidad origen.',
            $rule->errores($turno, true, $origen, CarbonImmutable::parse('2025-12-15 10:00:00', 'America/La_Paz')),
        );
    }

    public function test_non_active_room_inscription_is_blocked(): void
    {
        $rule = $this->rule();
        $turno = $this->turno('2025-11-30', '09:00');
        $origen = $this->origen('inscripcion_sala', 'en_pausa');

        $this->assertContains(
            'La inscripción no está activa; no permite registrar asistencias.',
            $rule->errores($turno, true, $origen, $this->ahora()),
        );
    }

    public function test_cancelled_turn_is_blocked(): void
    {
        $rule = $this->rule();
        $turno = $this->turno('2025-11-30', '09:00', EstadoTurno::CANCELADO);

        $this->assertNotSame([], $rule->errores($turno, false, null, $this->ahora()));
    }

    private function rule(): TurnoAsistibleRule
    {
        return new TurnoAsistibleRule(new AsistenciaClock(new class implements ParameterReaderContract
        {
            public function obtener(string $clave): ParameterValue
            {
                return new ParameterValue(
                    clave: $clave,
                    valor: 'America/La_Paz',
                    tipo: ParameterType::TEXTO,
                    unidad: null,
                    categoria: ParameterCategory::ZONA_HORARIA,
                    sensible: false,
                    visibleRecepcion: false,
                    actualizadoEn: null,
                );
            }

            public function obtenerVarios(array $claves): array
            {
                return [];
            }

            public function porCategoria(string $categoria): array
            {
                return [];
            }
        }));
    }

    private function ahora(): CarbonImmutable
    {
        return CarbonImmutable::parse('2025-11-30 10:00:00', 'America/La_Paz');
    }

    private function turno(
        string $fecha,
        string $hora,
        EstadoTurno $estado = EstadoTurno::PROGRAMADO,
    ): Turno {
        $turno = new Turno;
        $turno->forceFill([
            'fecha' => $fecha,
            'hora_inicio' => $hora,
            'hora_fin' => '09:50',
            'estado' => $estado->value,
            'paciente_id' => 1,
            'sucursal_id' => 1,
            'servicio_id' => 1,
        ]);

        return $turno;
    }

    private function origen(
        string $tipo,
        string $estado,
        string $desde = '2025-11-01',
        string $hasta = '2025-11-30',
    ): OrigenTurnosResumen {
        return new OrigenTurnosResumen(
            tipo: $tipo,
            id: 1,
            sucursalId: 1,
            pacienteId: 1,
            servicioId: 1,
            salaId: null,
            profesionalId: null,
            fechaInicio: $desde,
            fechaFin: $hasta,
            cantidadSesiones: 10,
            estadoEfectivo: $estado,
        );
    }
}
