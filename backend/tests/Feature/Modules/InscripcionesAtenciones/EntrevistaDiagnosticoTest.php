<?php

namespace Tests\Feature\Modules\InscripcionesAtenciones;

use App\Modules\InscripcionesAtenciones\Models\AtencionDiagnostico;
use App\Modules\InscripcionesAtenciones\Models\AtencionEntrevista;
use App\Modules\InscripcionesAtenciones\Models\BitacoraEntry;

final class EntrevistaDiagnosticoTest extends EntrevistaDiagnosticoTestCase
{
    public function test_creates_interview_with_backend_amount(): void
    {
        $branch = $this->branch();
        $user = $this->actingAsRole('recepcion');
        $this->withBranch($user, $branch);

        $paciente = $this->paciente($branch);
        $tutor = $this->tutor($branch);
        $this->asociarTutor($paciente, $tutor);
        $servicio = $this->servicioEntrevista();
        $categoria = $this->categoriaTarifa();
        $this->tarifa($servicio, $branch, $categoria, '250.00');

        $this->postJson(
            '/api/inscripciones-atenciones/entrevista-diagnostico/entrevistas',
            $this->payloadEntrevista($branch, $paciente, $tutor, $servicio, $categoria),
        )
            ->assertCreated()
            ->assertJsonPath('data.estado', 'activa')
            ->assertJsonPath('data.monto_esperado', '250.00')
            ->assertJsonPath('data.fecha', '2026-11-25');

        $this->assertDatabaseHas('atencion_entrevista', [
            'paciente_id' => $paciente->id,
            'tutor_id' => $tutor->id,
            'estado' => 'activa',
            'monto_esperado' => 250.00,
        ]);
    }

    public function test_creates_diagnosis_with_type_professional_and_snapshot(): void
    {
        $branch = $this->branch();
        $user = $this->actingAsRole('recepcion');
        $this->withBranch($user, $branch);

        $paciente = $this->paciente($branch);
        $servicio = $this->servicioDiagnostico();
        $especialidad = $this->especialidad('Psicología');
        $tipo = $this->tipoDiagnostico($servicio, [
            ['especialidad_id' => $especialidad->id, 'cantidad_minima' => 1],
        ]);
        $profesional = $this->profesional($branch, [$especialidad->id]);
        $categoria = $this->categoriaTarifa();
        $this->tarifa($servicio, $branch, $categoria, '1200.00');

        $this->postJson(
            '/api/inscripciones-atenciones/entrevista-diagnostico/diagnosticos',
            $this->payloadDiagnostico($branch, $paciente, $tipo, $categoria, [
                'profesionales' => [$profesional->id],
            ]),
        )
            ->assertCreated()
            ->assertJsonPath('data.estado', 'activa')
            ->assertJsonPath('data.tipo_diagnostico.id', $tipo->id)
            ->assertJsonPath('data.monto_esperado', '1200.00')
            ->assertJsonPath('data.profesionales.0.id', $profesional->id);

        $diagnostico = AtencionDiagnostico::query()->firstOrFail();
        $this->assertSame($tipo->id, (int) $diagnostico->tipo_diagnostico_id);
        $this->assertDatabaseHas('atencion_diagnostico_profesional', [
            'atencion_diagnostico_id' => $diagnostico->id,
            'profesional_id' => $profesional->id,
        ]);
        $this->assertDatabaseHas('atencion_diagnostico_requisito', [
            'atencion_diagnostico_id' => $diagnostico->id,
            'especialidad_id' => $especialidad->id,
            'cantidad_minima' => 1,
        ]);
    }

    public function test_blocks_incomplete_professional_coverage(): void
    {
        $branch = $this->branch();
        $user = $this->actingAsRole('recepcion');
        $this->withBranch($user, $branch);

        $paciente = $this->paciente($branch);
        $servicio = $this->servicioDiagnostico();
        $especialidad = $this->especialidad('Psicopedagogía');
        $tipo = $this->tipoDiagnostico($servicio, [
            ['especialidad_id' => $especialidad->id, 'cantidad_minima' => 2],
        ]);
        $profesional = $this->profesional($branch, [$especialidad->id]);
        $categoria = $this->categoriaTarifa();
        $this->tarifa($servicio, $branch, $categoria);

        $this->postJson(
            '/api/inscripciones-atenciones/entrevista-diagnostico/diagnosticos',
            $this->payloadDiagnostico($branch, $paciente, $tipo, $categoria, [
                'profesionales' => [$profesional->id],
            ]),
        )
            ->assertStatus(422)
            ->assertJsonPath('code', 'PROFESIONALES_INSUFICIENTES')
            ->assertJsonPath('message', 'Faltan profesionales requeridos por el tipo.');
    }

    public function test_allows_unassigned_diagnosis_and_records_omitted_interview(): void
    {
        $branch = $this->branch();
        $user = $this->actingAsRole('recepcion');
        $this->withBranch($user, $branch);

        $paciente = $this->paciente($branch);
        $servicio = $this->servicioDiagnostico();
        $tipo = $this->tipoDiagnostico($servicio, []);
        $categoria = $this->categoriaTarifa();
        $this->tarifa($servicio, $branch, $categoria);

        $this->postJson(
            '/api/inscripciones-atenciones/entrevista-diagnostico/diagnosticos',
            $this->payloadDiagnostico($branch, $paciente, $tipo, $categoria, [
                'omitio_entrevista' => true,
                'motivo_omision_entrevista' => 'La familia decidió ir directo a la evaluación.',
            ]),
        )
            ->assertCreated()
            ->assertJsonPath('data.omitio_entrevista', true)
            ->assertJsonPath('data.motivo_omision_entrevista', 'La familia decidió ir directo a la evaluación.');
    }

    public function test_requires_omission_or_previous_interview(): void
    {
        $branch = $this->branch();
        $user = $this->actingAsRole('recepcion');
        $this->withBranch($user, $branch);

        $paciente = $this->paciente($branch);
        $servicio = $this->servicioDiagnostico();
        $tipo = $this->tipoDiagnostico($servicio, []);
        $categoria = $this->categoriaTarifa();
        $this->tarifa($servicio, $branch, $categoria);

        $this->postJson(
            '/api/inscripciones-atenciones/entrevista-diagnostico/diagnosticos',
            $this->payloadDiagnostico($branch, $paciente, $tipo, $categoria, [
                'omitio_entrevista' => false,
            ]),
        )
            ->assertStatus(422)
            ->assertJsonPath('code', 'ENTREVISTA_OMITIDA_INVALIDA');
    }

    public function test_withdraws_and_blocks_edit_after_withdraw(): void
    {
        $branch = $this->branch();
        $user = $this->actingAsRole('administracion');
        $this->withBranch($user, $branch);

        $paciente = $this->paciente($branch);
        $tutor = $this->tutor($branch);
        $this->asociarTutor($paciente, $tutor);
        $servicio = $this->servicioEntrevista();
        $categoria = $this->categoriaTarifa();
        $this->tarifa($servicio, $branch, $categoria);

        $created = $this->postJson(
            '/api/inscripciones-atenciones/entrevista-diagnostico/entrevistas',
            $this->payloadEntrevista($branch, $paciente, $tutor, $servicio, $categoria),
        )->assertCreated()->json('data');

        $this->postJson(
            '/api/inscripciones-atenciones/entrevista-diagnostico/entrevistas/'.$created['id'].'/retirar',
            [
                'motivo' => 'error_registro',
                'motivo_detalle' => 'Se cargó la fecha equivocada.',
                'fecha_baja' => '2026-11-25',
            ],
        )
            ->assertOk()
            ->assertJsonPath('data.estado', 'retirada')
            ->assertJsonPath('data.retiro.motivo_codigo', 'error_registro');

        $this->patchJson(
            '/api/inscripciones-atenciones/entrevista-diagnostico/entrevistas/'.$created['id'],
            ['notas_admin' => 'Intento posterior'],
        )
            ->assertStatus(409)
            ->assertJsonPath('code', 'ATENCION_RETIRADA');

        $audit = BitacoraEntry::query()
            ->where('entidad', 'atencion_entrevista')
            ->where('entidad_id', $created['id'])
            ->where('accion', 'RETIRAR')
            ->firstOrFail();

        $this->assertTrue($audit->critico);
        $this->assertSame('error_registro', $audit->datos_nuevos['motivo_codigo']);
    }

    public function test_reception_cannot_edit_or_withdraw(): void
    {
        $branch = $this->branch();
        $user = $this->actingAsRole('recepcion');
        $this->withBranch($user, $branch);

        $paciente = $this->paciente($branch);
        $tutor = $this->tutor($branch);
        $this->asociarTutor($paciente, $tutor);
        $servicio = $this->servicioEntrevista();
        $categoria = $this->categoriaTarifa();
        $this->tarifa($servicio, $branch, $categoria);

        $created = $this->postJson(
            '/api/inscripciones-atenciones/entrevista-diagnostico/entrevistas',
            $this->payloadEntrevista($branch, $paciente, $tutor, $servicio, $categoria),
        )->assertCreated()->json('data');

        $this->patchJson(
            '/api/inscripciones-atenciones/entrevista-diagnostico/entrevistas/'.$created['id'],
            ['notas_admin' => 'No debería poder'],
        )->assertForbidden();

        $this->postJson(
            '/api/inscripciones-atenciones/entrevista-diagnostico/entrevistas/'.$created['id'].'/retirar',
            ['motivo' => 'retiro_paciente', 'fecha_baja' => '2026-11-25'],
        )->assertForbidden();
    }

    public function test_delete_is_always_rejected_and_payment_stub_is_clear(): void
    {
        $branch = $this->branch();
        $user = $this->actingAsRole('administracion');
        $this->withBranch($user, $branch);

        $paciente = $this->paciente($branch);
        $tutor = $this->tutor($branch);
        $this->asociarTutor($paciente, $tutor);
        $servicio = $this->servicioEntrevista();
        $categoria = $this->categoriaTarifa();
        $this->tarifa($servicio, $branch, $categoria);

        $created = $this->postJson(
            '/api/inscripciones-atenciones/entrevista-diagnostico/entrevistas',
            $this->payloadEntrevista($branch, $paciente, $tutor, $servicio, $categoria),
        )->assertCreated()->json('data');

        $this->deleteJson(
            '/api/inscripciones-atenciones/entrevista-diagnostico/entrevistas/'.$created['id'],
        )
            ->assertStatus(409)
            ->assertJsonPath('code', 'ATENCION_DELETE_NOT_ALLOWED');

        $this->getJson(
            '/api/inscripciones-atenciones/entrevista-diagnostico/entrevistas/'.$created['id'].'/pago',
        )
            ->assertStatus(503)
            ->assertJsonPath('code', 'MODULO_NO_DISPONIBLE')
            ->assertJsonPath('modulo', 'Pagos y caja');

        $this->getJson(
            '/api/inscripciones-atenciones/entrevista-diagnostico/entrevistas/exportar',
        )
            ->assertStatus(503)
            ->assertJsonPath('code', 'MODULO_NO_DISPONIBLE')
            ->assertJsonPath('modulo', 'Exportación');

        $this->assertSame(1, AtencionEntrevista::query()->count());
    }

    public function test_lists_and_filters_interviews_by_branch_and_patient(): void
    {
        $branch = $this->branch();
        $user = $this->actingAsRole('recepcion');
        $this->withBranch($user, $branch);

        $paciente = $this->paciente($branch);
        $tutor = $this->tutor($branch);
        $this->asociarTutor($paciente, $tutor);
        $servicio = $this->servicioEntrevista();
        $categoria = $this->categoriaTarifa();
        $this->tarifa($servicio, $branch, $categoria);

        $this->postJson(
            '/api/inscripciones-atenciones/entrevista-diagnostico/entrevistas',
            $this->payloadEntrevista($branch, $paciente, $tutor, $servicio, $categoria),
        )->assertCreated();

        $this->getJson(
            '/api/inscripciones-atenciones/entrevista-diagnostico/entrevistas?'
            .http_build_query([
                'sucursal_id' => $branch->id,
                'paciente_id' => $paciente->id,
                'estado' => 'activa',
            ]),
        )
            ->assertOk()
            ->assertJsonPath('meta.total', 1)
            ->assertJsonPath('data.0.paciente.id', $paciente->id);
    }

    public function test_filters_diagnoses_by_type_and_professional_and_keeps_multiday_range(): void
    {
        $branch = $this->branch();
        $user = $this->actingAsRole('recepcion');
        $this->withBranch($user, $branch);

        $paciente = $this->paciente($branch);
        $servicio = $this->servicioDiagnostico();
        $especialidad = $this->especialidad('Psicología clínica');
        $tipo = $this->tipoDiagnostico($servicio, [
            ['especialidad_id' => $especialidad->id, 'cantidad_minima' => 1],
        ]);
        $profesional = $this->profesional($branch, [$especialidad->id]);
        $categoria = $this->categoriaTarifa();
        $this->tarifa($servicio, $branch, $categoria);

        $created = $this->postJson(
            '/api/inscripciones-atenciones/entrevista-diagnostico/diagnosticos',
            $this->payloadDiagnostico($branch, $paciente, $tipo, $categoria, [
                'profesionales' => [$profesional->id],
            ]),
        )
            ->assertCreated()
            ->assertJsonPath('data.fecha_inicio', '2026-12-01')
            ->assertJsonPath('data.fecha_fin', '2026-12-03')
            ->json('data');

        $this->getJson(
            '/api/inscripciones-atenciones/entrevista-diagnostico/diagnosticos?'
            .http_build_query([
                'tipo_diagnostico_id' => $tipo->id,
                'profesional_id' => $profesional->id,
            ]),
        )
            ->assertOk()
            ->assertJsonPath('meta.total', 1)
            ->assertJsonPath('data.0.id', $created['id']);
    }

    public function test_links_previous_interview_to_diagnosis(): void
    {
        $branch = $this->branch();
        $user = $this->actingAsRole('administracion');
        $this->withBranch($user, $branch);

        $paciente = $this->paciente($branch);
        $tutor = $this->tutor($branch);
        $this->asociarTutor($paciente, $tutor);
        $servicioEntrevista = $this->servicioEntrevista();
        $servicioDiagnostico = $this->servicioDiagnostico();
        $categoria = $this->categoriaTarifa();
        $this->tarifa($servicioEntrevista, $branch, $categoria, '250.00');
        $this->tarifa($servicioDiagnostico, $branch, $categoria, '1200.00');
        $tipo = $this->tipoDiagnostico($servicioDiagnostico, []);

        $entrevista = $this->postJson(
            '/api/inscripciones-atenciones/entrevista-diagnostico/entrevistas',
            $this->payloadEntrevista($branch, $paciente, $tutor, $servicioEntrevista, $categoria),
        )->assertCreated()->json('data');

        $diagnostico = $this->postJson(
            '/api/inscripciones-atenciones/entrevista-diagnostico/diagnosticos',
            $this->payloadDiagnostico($branch, $paciente, $tipo, $categoria),
        )->assertCreated()->json('data');

        $this->postJson(
            '/api/inscripciones-atenciones/entrevista-diagnostico/diagnosticos/'.$diagnostico['id'].'/vincular-entrevista',
            ['entrevista_id' => $entrevista['id']],
        )
            ->assertOk()
            ->assertJsonPath('data.entrevista_previa_id', $entrevista['id'])
            ->assertJsonPath('data.omitio_entrevista', false);

        $this->assertDatabaseHas('bitacora', [
            'entidad' => 'atencion_diagnostico',
            'entidad_id' => $diagnostico['id'],
            'accion' => 'VINCULAR',
        ]);
    }

    public function test_comprobante_includes_legend_type_professionals_and_amount(): void
    {
        $branch = $this->branch();
        $user = $this->actingAsRole('recepcion');
        $this->withBranch($user, $branch);

        $paciente = $this->paciente($branch);
        $servicio = $this->servicioDiagnostico();
        $especialidad = $this->especialidad('Psicopedagogía clínica');
        $tipo = $this->tipoDiagnostico($servicio, [
            ['especialidad_id' => $especialidad->id, 'cantidad_minima' => 1],
        ]);
        $profesional = $this->profesional($branch, [$especialidad->id]);
        $categoria = $this->categoriaTarifa();
        $this->tarifa($servicio, $branch, $categoria, '900.00');

        $created = $this->postJson(
            '/api/inscripciones-atenciones/entrevista-diagnostico/diagnosticos',
            $this->payloadDiagnostico($branch, $paciente, $tipo, $categoria, [
                'profesionales' => [$profesional->id],
            ]),
        )->assertCreated()->json('data');

        $this->getJson(
            '/api/inscripciones-atenciones/entrevista-diagnostico/diagnosticos/'.$created['id'].'/comprobante',
        )
            ->assertOk()
            ->assertJsonPath('data.tipo', 'diagnostico')
            ->assertJsonPath('data.atencion.tipo_diagnostico.nombre', $tipo->nombre)
            ->assertJsonPath('data.atencion.profesionales.0.id', $profesional->id)
            ->assertJsonPath('data.atencion.monto_esperado', '900.00')
            ->assertJsonStructure(['data' => ['centro', 'moneda', 'leyenda_no_factura', 'fecha_emision']]);
    }

    public function test_rejects_professional_from_another_branch(): void
    {
        $branch = $this->branch();
        $other = $this->branch();
        $user = $this->actingAsRole('recepcion');
        $this->withBranch($user, $branch);
        $this->withBranch($user, $other);

        $paciente = $this->paciente($branch);
        $servicio = $this->servicioDiagnostico();
        $especialidad = $this->especialidad('Psicología externa');
        $tipo = $this->tipoDiagnostico($servicio, [
            ['especialidad_id' => $especialidad->id, 'cantidad_minima' => 1],
        ]);
        $profesional = $this->profesional($other, [$especialidad->id]);
        $categoria = $this->categoriaTarifa();
        $this->tarifa($servicio, $branch, $categoria);

        $this->postJson(
            '/api/inscripciones-atenciones/entrevista-diagnostico/diagnosticos',
            $this->payloadDiagnostico($branch, $paciente, $tipo, $categoria, [
                'profesionales' => [$profesional->id],
            ]),
        )
            ->assertStatus(422)
            ->assertJsonPath('code', 'PROFESIONAL_FUERA_DE_SUCURSAL');
    }

    public function test_rejects_invalid_date_range(): void
    {
        $branch = $this->branch();
        $user = $this->actingAsRole('recepcion');
        $this->withBranch($user, $branch);

        $paciente = $this->paciente($branch);
        $servicio = $this->servicioDiagnostico();
        $tipo = $this->tipoDiagnostico($servicio, []);
        $categoria = $this->categoriaTarifa();
        $this->tarifa($servicio, $branch, $categoria);

        $this->postJson(
            '/api/inscripciones-atenciones/entrevista-diagnostico/diagnosticos',
            $this->payloadDiagnostico($branch, $paciente, $tipo, $categoria, [
                'fecha_inicio' => '2026-12-05',
                'fecha_fin' => '2026-12-01',
            ]),
        )
            ->assertStatus(422)
            ->assertJsonValidationErrors('fecha_fin');
    }

    public function test_updates_diagnosis_professionals_with_audit(): void
    {
        $branch = $this->branch();
        $user = $this->actingAsRole('administracion');
        $this->withBranch($user, $branch);

        $paciente = $this->paciente($branch);
        $servicio = $this->servicioDiagnostico();
        $especialidad = $this->especialidad('Psicología de actualización');
        $tipo = $this->tipoDiagnostico($servicio, [
            ['especialidad_id' => $especialidad->id, 'cantidad_minima' => 1],
        ]);
        $profesional = $this->profesional($branch, [$especialidad->id]);
        $segundo = $this->profesional($branch, [$especialidad->id]);
        $categoria = $this->categoriaTarifa();
        $this->tarifa($servicio, $branch, $categoria);

        $created = $this->postJson(
            '/api/inscripciones-atenciones/entrevista-diagnostico/diagnosticos',
            $this->payloadDiagnostico($branch, $paciente, $tipo, $categoria, [
                'profesionales' => [$profesional->id],
            ]),
        )->assertCreated()->json('data');

        $this->patchJson(
            '/api/inscripciones-atenciones/entrevista-diagnostico/diagnosticos/'.$created['id'],
            [
                'profesionales' => [$profesional->id, $segundo->id],
                'notas_admin' => 'Se sumó un segundo profesional.',
            ],
        )
            ->assertOk()
            ->assertJsonPath('data.notas_admin', 'Se sumó un segundo profesional.')
            ->assertJsonCount(2, 'data.profesionales');

        $audit = BitacoraEntry::query()
            ->where('entidad', 'atencion_diagnostico')
            ->where('entidad_id', $created['id'])
            ->where('accion', 'ACTUALIZAR')
            ->firstOrFail();

        $this->assertCount(1, $audit->datos_anteriores['profesionales']);
        $this->assertCount(2, $audit->datos_nuevos['profesionales']);
    }

    public function test_updates_interview_date_and_notes_without_recalculating_amount(): void
    {
        $branch = $this->branch();
        $user = $this->actingAsRole('administracion');
        $this->withBranch($user, $branch);

        $paciente = $this->paciente($branch);
        $tutor = $this->tutor($branch);
        $this->asociarTutor($paciente, $tutor);
        $servicio = $this->servicioEntrevista();
        $categoria = $this->categoriaTarifa();
        $this->tarifa($servicio, $branch, $categoria, '300.00');

        $created = $this->postJson(
            '/api/inscripciones-atenciones/entrevista-diagnostico/entrevistas',
            $this->payloadEntrevista($branch, $paciente, $tutor, $servicio, $categoria),
        )->assertCreated()->json('data');

        $this->patchJson(
            '/api/inscripciones-atenciones/entrevista-diagnostico/entrevistas/'.$created['id'],
            [
                'fecha' => '2026-11-26',
                'notas_admin' => 'Reprogramada por disponibilidad.',
            ],
        )
            ->assertOk()
            ->assertJsonPath('data.fecha', '2026-11-26')
            ->assertJsonPath('data.monto_esperado', '300.00')
            ->assertJsonPath('data.notas_admin', 'Reprogramada por disponibilidad.');
    }
}
