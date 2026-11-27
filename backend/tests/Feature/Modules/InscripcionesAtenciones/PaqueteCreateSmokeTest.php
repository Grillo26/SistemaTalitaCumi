<?php

namespace Tests\Feature\Modules\InscripcionesAtenciones;

final class PaqueteCreateSmokeTest extends PaquetesSesionesTestCase
{
    public function test_creates_a_package_with_backend_total(): void
    {
        $escenario = $this->escenarioPaquete();

        $response = $this->crearPaquete($escenario);

        $response->assertCreated();
        $response->assertJsonPath('data.estado', 'activo');
        $response->assertJsonPath('data.monto_esperado', '500.00');
        $response->assertJsonPath('data.cantidad_sesiones', 10);
        $response->assertJsonPath('data.dias_semana', [1, 3]);
        $response->assertJsonPath('data.profesional.id', $escenario['profesional']->id);

        $this->assertDatabaseHas('paquete_sesiones', [
            'paciente_id' => $escenario['paciente']->id,
            'servicio_id' => $escenario['servicio']->id,
            'cantidad_sesiones' => 10,
            'monto_esperado' => 500.00,
            'estado' => 'activo',
        ]);
    }
}
