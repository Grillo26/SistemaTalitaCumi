<?php

namespace Tests\Feature\Modules\InscripcionesAtenciones;

use App\Modules\InscripcionesAtenciones\Models\InscripcionSala;

final class InscripcionCreateSmokeTest extends InscripcionesAtencionesTestCase
{
    public function test_creates_an_inscription_with_backend_amount(): void
    {
        $branch = $this->branch();
        $user = $this->actingAsRole('recepcion');
        $this->withBranch($user, $branch);

        $paciente = $this->paciente($branch);
        $tutor = $this->tutor($branch);
        $this->asociarTutor($paciente, $tutor);
        $sala = $this->sala($branch);
        $servicio = $this->servicioSalaGrupal();
        $categoria = $this->categoriaTarifa();
        $this->tarifa($servicio, $branch, $categoria, '500.00');

        $response = $this->postJson(
            '/api/inscripciones-atenciones/salas-grupales',
            $this->payload($paciente, $tutor, $branch, $sala, $servicio, $categoria),
        );

        $response->assertCreated();
        $response->assertJsonPath('data.estado', 'activa');
        $response->assertJsonPath('data.monto_esperado', '500.00');

        $this->assertDatabaseHas('inscripcion_sala', [
            'paciente_id' => $paciente->id,
            'sala_id' => $sala->id,
            'estado' => 'activa',
            'monto_esperado' => 500.00,
        ]);

        $inscripcion = InscripcionSala::query()->firstOrFail();
        $this->assertSame('500.00', (string) $inscripcion->monto_esperado);
    }
}
