<?php

namespace Tests\Unit\Modules\AgendaTurnos\Rules;

use App\Modules\AgendaTurnos\Enums\EstadoGeneralDiagnostico;
use App\Modules\AgendaTurnos\Rules\EstadoGeneralDiagnosticoRule;
use PHPUnit\Framework\TestCase;

/**
 * 07.05/RF-005 / 07.05/RN-004 / 07.05/RN-005.
 */
final class EstadoGeneralDiagnosticoRuleTest extends TestCase
{
    private EstadoGeneralDiagnosticoRule $rule;

    protected function setUp(): void
    {
        parent::setUp();

        $this->rule = new EstadoGeneralDiagnosticoRule;
    }

    public function test_sin_requisitos_es_incompleto(): void
    {
        $resultado = $this->rule->evaluar([], [], []);

        $this->assertSame(EstadoGeneralDiagnostico::INCOMPLETO, $resultado->estado);
        $this->assertSame('sin_requisitos', $resultado->faltantes[0]['tipo']);
    }

    public function test_todos_los_puestos_con_turno_programado_es_en_proceso(): void
    {
        $resultado = $this->rule->evaluar(
            [$this->requisito(10, 'Psicología', 1), $this->requisito(20, 'Fonoaudiología', 1)],
            [
                ['id' => 1, 'profesional_id' => 100, 'estado' => 'programado'],
                ['id' => 2, 'profesional_id' => 200, 'estado' => 'programado'],
            ],
            [100 => [10], 200 => [20]],
        );

        $this->assertSame(EstadoGeneralDiagnostico::EN_PROCESO, $resultado->estado);
        $this->assertTrue($resultado->puestos[0]['cubierto']);
        $this->assertSame([], $resultado->faltantesSinTurno());
    }

    public function test_todos_los_puestos_asistidos_es_completo(): void
    {
        $resultado = $this->rule->evaluar(
            [$this->requisito(10, 'Psicología', 1), $this->requisito(20, 'Fonoaudiología', 1)],
            [
                ['id' => 1, 'profesional_id' => 100, 'estado' => 'asistio'],
                ['id' => 2, 'profesional_id' => 200, 'estado' => 'asistio'],
            ],
            [100 => [10], 200 => [20]],
        );

        $this->assertSame(EstadoGeneralDiagnostico::COMPLETO, $resultado->estado);
        $this->assertTrue($resultado->completo());
    }

    public function test_un_puesto_sin_turno_es_incompleto(): void
    {
        $resultado = $this->rule->evaluar(
            [$this->requisito(10, 'Psicología', 1), $this->requisito(20, 'Fonoaudiología', 1)],
            [
                ['id' => 1, 'profesional_id' => 100, 'estado' => 'asistio'],
            ],
            [100 => [10], 200 => [20]],
        );

        $this->assertSame(EstadoGeneralDiagnostico::INCOMPLETO, $resultado->estado);
        $this->assertCount(1, $resultado->faltantesSinTurno());
        $this->assertSame(20, $resultado->faltantesSinTurno()[0]['especialidad_id']);
    }

    public function test_falto_cancelado_y_reprogramado_dejan_el_puesto_descubierto(): void
    {
        foreach (['falto', 'cancelado', 'reprogramado'] as $estado) {
            $resultado = $this->rule->evaluar(
                [$this->requisito(10, 'Psicología', 1)],
                [
                    ['id' => 1, 'profesional_id' => 100, 'estado' => $estado],
                ],
                [100 => [10]],
            );

            $this->assertSame(
                EstadoGeneralDiagnostico::INCOMPLETO,
                $resultado->estado,
                "Estado de turno {$estado} no debe cubrir el puesto.",
            );
        }
    }

    public function test_cantidad_minima_exige_profesionales_distintos(): void
    {
        $requisitos = [$this->requisito(10, 'Psicología', 2)];
        $especialidades = [100 => [10], 200 => [10]];

        $uno = $this->rule->evaluar(
            $requisitos,
            [['id' => 1, 'profesional_id' => 100, 'estado' => 'asistio']],
            $especialidades,
        );
        $this->assertSame(EstadoGeneralDiagnostico::INCOMPLETO, $uno->estado);

        $dos = $this->rule->evaluar(
            $requisitos,
            [
                ['id' => 1, 'profesional_id' => 100, 'estado' => 'asistio'],
                ['id' => 2, 'profesional_id' => 200, 'estado' => 'asistio'],
            ],
            $especialidades,
        );
        $this->assertSame(EstadoGeneralDiagnostico::COMPLETO, $dos->estado);
    }

    public function test_un_mismo_profesional_con_dos_turnos_no_duplica_la_cobertura(): void
    {
        $resultado = $this->rule->evaluar(
            [$this->requisito(10, 'Psicología', 2)],
            [
                ['id' => 1, 'profesional_id' => 100, 'estado' => 'programado'],
                ['id' => 2, 'profesional_id' => 100, 'estado' => 'programado'],
            ],
            [100 => [10]],
        );

        $this->assertSame(EstadoGeneralDiagnostico::INCOMPLETO, $resultado->estado);
        $this->assertSame(1, $resultado->puestos[0]['vigentes']);
    }

    public function test_asistencia_parcial_muestra_faltante_sin_asistencia(): void
    {
        $resultado = $this->rule->evaluar(
            [$this->requisito(10, 'Psicología', 1), $this->requisito(20, 'Fonoaudiología', 1)],
            [
                ['id' => 1, 'profesional_id' => 100, 'estado' => 'asistio'],
                ['id' => 2, 'profesional_id' => 200, 'estado' => 'programado'],
            ],
            [100 => [10], 200 => [20]],
        );

        $this->assertSame(EstadoGeneralDiagnostico::EN_PROCESO, $resultado->estado);
        $this->assertCount(1, $resultado->faltantesSinAsistencia());
        $this->assertSame(20, $resultado->faltantesSinAsistencia()[0]['especialidad_id']);
    }

    /**
     * @return array{especialidad_id: int, especialidad_nombre: string, cantidad_minima: int}
     */
    private function requisito(int $id, string $nombre, int $cantidad): array
    {
        return [
            'especialidad_id' => $id,
            'especialidad_nombre' => $nombre,
            'cantidad_minima' => $cantidad,
        ];
    }
}
