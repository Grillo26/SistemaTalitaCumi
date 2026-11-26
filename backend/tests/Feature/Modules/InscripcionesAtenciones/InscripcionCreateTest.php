<?php

namespace Tests\Feature\Modules\InscripcionesAtenciones;

use App\Modules\CatalogoServicios\Models\TipoServicio;

final class InscripcionCreateTest extends InscripcionesAtencionesTestCase
{
    public function test_preview_returns_backend_amount(): void
    {
        $branch = $this->branch();
        $user = $this->actingAsRole('recepcion');
        $this->withBranch($user, $branch);

        $servicio = $this->servicioSalaGrupal();
        $categoria = $this->categoriaTarifa();
        $this->tarifa($servicio, $branch, $categoria, '500.00');

        $this->getJson('/api/inscripciones-atenciones/salas-grupales/monto-esperado?'
            .http_build_query([
                'servicio_id' => $servicio->id,
                'sucursal_id' => $branch->id,
                'categoria_tarifa_id' => $categoria->id,
                'fecha_inicio' => '2026-11-01',
            ]))
            ->assertOk()
            ->assertJsonPath('data.monto', '500.00')
            ->assertJsonPath('tarifa_disponible', true);
    }

    public function test_rejects_service_that_is_not_sala_grupal(): void
    {
        $branch = $this->branch();
        $user = $this->actingAsRole('recepcion');
        $this->withBranch($user, $branch);

        $tipoEntrevista = TipoServicio::query()
            ->where('codigo', 'entrevista')
            ->firstOrFail();
        $servicio = $this->servicioSalaGrupal([
            'tipo_servicio_id' => $tipoEntrevista->id,
            'nombre' => 'Entrevista de prueba',
        ]);
        $categoria = $this->categoriaTarifa();
        $this->tarifa($servicio, $branch, $categoria);

        $this->getJson('/api/inscripciones-atenciones/salas-grupales/monto-esperado?'
            .http_build_query([
                'servicio_id' => $servicio->id,
                'sucursal_id' => $branch->id,
                'categoria_tarifa_id' => $categoria->id,
                'fecha_inicio' => '2026-11-01',
            ]))
            ->assertStatus(422)
            ->assertJsonPath('code', 'SERVICIO_NO_ES_SALA_GRUPAL');
    }

    public function test_preview_returns_no_tariff_when_none_is_vigente(): void
    {
        $branch = $this->branch();
        $user = $this->actingAsRole('recepcion');
        $this->withBranch($user, $branch);

        $servicio = $this->servicioSalaGrupal();
        $categoria = $this->categoriaTarifa();

        $this->getJson('/api/inscripciones-atenciones/salas-grupales/monto-esperado?'
            .http_build_query([
                'servicio_id' => $servicio->id,
                'sucursal_id' => $branch->id,
                'categoria_tarifa_id' => $categoria->id,
                'fecha_inicio' => '2026-11-01',
            ]))
            ->assertOk()
            ->assertJsonPath('data', null)
            ->assertJsonPath('tarifa_disponible', false);
    }

    public function test_client_cannot_send_the_amount(): void
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
        $this->tarifa($servicio, $branch, $categoria);

        $this->postJson(
            '/api/inscripciones-atenciones/salas-grupales',
            $this->payload($paciente, $tutor, $branch, $sala, $servicio, $categoria, [
                'monto_esperado' => '1.00',
            ]),
        )
            ->assertStatus(422)
            ->assertJsonValidationErrors('monto_esperado');
    }

    public function test_blocks_inactive_patient(): void
    {
        $branch = $this->branch();
        $user = $this->actingAsRole('recepcion');
        $this->withBranch($user, $branch);

        $paciente = $this->paciente($branch, ['estado' => 'retirado']);
        $tutor = $this->tutor($branch);
        $this->asociarTutor($paciente, $tutor);
        $sala = $this->sala($branch);
        $servicio = $this->servicioSalaGrupal();
        $categoria = $this->categoriaTarifa();
        $this->tarifa($servicio, $branch, $categoria);

        $this->postJson(
            '/api/inscripciones-atenciones/salas-grupales',
            $this->payload($paciente, $tutor, $branch, $sala, $servicio, $categoria),
        )
            ->assertStatus(409)
            ->assertJsonPath('code', 'PATIENT_NOT_INSCRIBIBLE');
    }

    public function test_blocks_duplicate_same_sala_and_period(): void
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
        $this->tarifa($servicio, $branch, $categoria);
        $payload = $this->payload($paciente, $tutor, $branch, $sala, $servicio, $categoria);

        $this->postJson('/api/inscripciones-atenciones/salas-grupales', $payload)->assertCreated();

        $this->postJson('/api/inscripciones-atenciones/salas-grupales', $payload)
            ->assertStatus(409)
            ->assertJsonPath('code', 'INSCRIPCION_DUPLICADA');
    }

    public function test_same_patient_can_be_inscribed_in_another_sala(): void
    {
        $branch = $this->branch();
        $user = $this->actingAsRole('recepcion');
        $this->withBranch($user, $branch);

        $paciente = $this->paciente($branch);
        $tutor = $this->tutor($branch);
        $this->asociarTutor($paciente, $tutor);
        $salaA = $this->sala($branch);
        $salaB = $this->sala($branch);
        $servicio = $this->servicioSalaGrupal();
        $categoria = $this->categoriaTarifa();
        $this->tarifa($servicio, $branch, $categoria);

        $this->postJson(
            '/api/inscripciones-atenciones/salas-grupales',
            $this->payload($paciente, $tutor, $branch, $salaA, $servicio, $categoria),
        )->assertCreated();

        $this->postJson(
            '/api/inscripciones-atenciones/salas-grupales',
            $this->payload($paciente, $tutor, $branch, $salaB, $servicio, $categoria),
        )->assertCreated();
    }

    public function test_blocks_inactive_sala(): void
    {
        $branch = $this->branch();
        $user = $this->actingAsRole('recepcion');
        $this->withBranch($user, $branch);

        $paciente = $this->paciente($branch);
        $tutor = $this->tutor($branch);
        $this->asociarTutor($paciente, $tutor);
        $sala = $this->sala($branch, ['activo' => false]);
        $servicio = $this->servicioSalaGrupal();
        $categoria = $this->categoriaTarifa();
        $this->tarifa($servicio, $branch, $categoria);

        $this->postJson(
            '/api/inscripciones-atenciones/salas-grupales',
            $this->payload($paciente, $tutor, $branch, $sala, $servicio, $categoria),
        )
            ->assertStatus(409)
            ->assertJsonPath('code', 'ESPACIO_INACTIVO');
    }

    public function test_blocks_sala_from_another_branch(): void
    {
        $branch = $this->branch();
        $other = $this->branch();
        $user = $this->actingAsRole('recepcion');
        $this->withBranch($user, $branch);
        $this->withBranch($user, $other);

        $paciente = $this->paciente($branch);
        $tutor = $this->tutor($branch);
        $this->asociarTutor($paciente, $tutor);
        $sala = $this->sala($other);
        $servicio = $this->servicioSalaGrupal();
        $categoria = $this->categoriaTarifa();
        $this->tarifa($servicio, $branch, $categoria);

        $this->postJson(
            '/api/inscripciones-atenciones/salas-grupales',
            $this->payload($paciente, $tutor, $branch, $sala, $servicio, $categoria),
        )
            ->assertStatus(422)
            ->assertJsonPath('code', 'ESPACIO_SUCURSAL_INCORRECTA');
    }

    public function test_blocks_branch_outside_scope(): void
    {
        $branch = $this->branch();
        $user = $this->actingAsRole('recepcion');

        $paciente = $this->paciente($branch);
        $tutor = $this->tutor($branch);
        $this->asociarTutor($paciente, $tutor);
        $sala = $this->sala($branch);
        $servicio = $this->servicioSalaGrupal();
        $categoria = $this->categoriaTarifa();
        $this->tarifa($servicio, $branch, $categoria);

        $this->postJson(
            '/api/inscripciones-atenciones/salas-grupales',
            $this->payload($paciente, $tutor, $branch, $sala, $servicio, $categoria),
        )
            ->assertStatus(403)
            ->assertJsonPath('code', 'BRANCH_ACCESS_DENIED');
    }

    public function test_blocks_tutor_not_associated_to_patient(): void
    {
        $branch = $this->branch();
        $user = $this->actingAsRole('recepcion');
        $this->withBranch($user, $branch);

        $paciente = $this->paciente($branch);
        $tutorAsociado = $this->tutor($branch);
        $this->asociarTutor($paciente, $tutorAsociado);
        $otroTutor = $this->tutor($branch);
        $sala = $this->sala($branch);
        $servicio = $this->servicioSalaGrupal();
        $categoria = $this->categoriaTarifa();
        $this->tarifa($servicio, $branch, $categoria);

        $this->postJson(
            '/api/inscripciones-atenciones/salas-grupales',
            $this->payload($paciente, $otroTutor, $branch, $sala, $servicio, $categoria),
        )
            ->assertStatus(422)
            ->assertJsonPath('code', 'TUTOR_NO_PERTENECE_AL_PACIENTE');
    }

    public function test_blocks_inactive_tutor(): void
    {
        $branch = $this->branch();
        $user = $this->actingAsRole('recepcion');
        $this->withBranch($user, $branch);

        $paciente = $this->paciente($branch);
        $tutor = $this->tutor($branch, ['activo' => false]);
        $this->asociarTutor($paciente, $tutor);
        $sala = $this->sala($branch);
        $servicio = $this->servicioSalaGrupal();
        $categoria = $this->categoriaTarifa();
        $this->tarifa($servicio, $branch, $categoria);

        $this->postJson(
            '/api/inscripciones-atenciones/salas-grupales',
            $this->payload($paciente, $tutor, $branch, $sala, $servicio, $categoria),
        )
            ->assertStatus(409)
            ->assertJsonPath('code', 'TUTOR_INACTIVE');
    }

    public function test_requires_confirmation_when_expected_tariff_changed(): void
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
        $payload = $this->payload($paciente, $tutor, $branch, $sala, $servicio, $categoria, [
            'tarifa_id_esperada' => 999999,
        ]);

        $this->postJson('/api/inscripciones-atenciones/salas-grupales', $payload)
            ->assertStatus(409)
            ->assertJsonPath('code', 'TARIFA_CAMBIO');

        $payload['confirmar_tarifa_cambio'] = true;

        $this->postJson('/api/inscripciones-atenciones/salas-grupales', $payload)
            ->assertCreated()
            ->assertJsonPath('data.monto_esperado', '500.00');
    }
}
