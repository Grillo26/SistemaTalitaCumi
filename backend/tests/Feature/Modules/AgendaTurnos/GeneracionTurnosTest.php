<?php

namespace Tests\Feature\Modules\AgendaTurnos;

use App\Models\User;
use App\Modules\AgendaTurnos\Models\Turno;
use App\Modules\AgendaTurnos\Support\TurnoBatchWriter;
use App\Modules\Organizacion\Contracts\SalaPatronContract;
use App\Modules\Organizacion\Contracts\SalaPatronResumen;
use App\Modules\Profesionales\Contracts\ProfessionalDirectoryContract;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Laravel\Sanctum\Sanctum;
use LogicException;
use RuntimeException;

/**
 * [Programación de Turnos] RF-001 a RF-015, RN-001 a RN-009,
 * CA-001 a CA-010, RT-001, RT-002.
 */
final class GeneracionTurnosTest extends ProgramacionTurnosTestCase
{
    public function test_preview_does_not_persist_and_generation_creates_the_pattern_ca_001_ca_002(): void
    {
        $escenario = $this->escenario();
        $paquete = $this->crearPaquete($escenario);

        $preview = $this->getJson($this->url('paquete_sesiones', $paquete->id, '/preview-generacion'));

        $preview
            ->assertOk()
            ->assertJsonPath('data.resumen.generables', 9)
            ->assertJsonPath('data.resumen.faltantes', 1)
            ->assertJsonPath('data.resumen.requiere_confirmar_advertencias', true)
            ->assertJsonPath('data.resumen.puede_confirmar', false)
            ->assertJsonCount(9, 'data.filas');

        $this->assertDatabaseCount('turno', 0);
        $this->assertDatabaseCount('turno_lote_generacion', 0);
        $this->assertDatabaseMissing('bitacora', ['accion' => 'GENERAR']);

        $response = $this->postJson($this->url('paquete_sesiones', $paquete->id, '/generar'), [
            'clave_idempotencia' => (string) Str::uuid(),
            'confirmar_advertencias' => true,
        ]);

        $response
            ->assertCreated()
            ->assertJsonPath('data.cantidad_creados', 9)
            ->assertJsonPath('data.accion', 'generar');

        $loteId = (int) $response->json('data.lote_id');

        $this->assertDatabaseCount('turno', 9);
        $this->assertDatabaseCount('turno_lote_generacion', 1);
        $this->assertDatabaseHas('turno', [
            'paquete_sesiones_id' => $paquete->id,
            'inscripcion_sala_id' => null,
            'estado' => 'programado',
            'lote_generacion_id' => $loteId,
            'fecha' => '2025-12-02',
        ]);
        $this->assertDatabaseHas('bitacora', [
            'accion' => 'GENERAR',
            'entidad' => 'turno_lote_generacion',
            'entidad_id' => $loteId,
            'modulo' => 'agenda',
        ]);

        $this->assertSame(
            ['2025-12-02', '2025-12-04', '2025-12-09'],
            Turno::query()->ordenado()->limit(3)->pluck('fecha')->map->format('Y-m-d')->all(),
        );
    }

    public function test_same_idempotency_key_reuses_the_batch_and_a_new_key_is_blocked_rt_001(): void
    {
        $escenario = $this->escenario();
        $paquete = $this->crearPaquete($escenario);
        $clave = (string) Str::uuid();

        $primera = $this->postJson($this->url('paquete_sesiones', $paquete->id, '/generar'), [
            'clave_idempotencia' => $clave,
            'confirmar_advertencias' => true,
        ])->assertCreated();

        $segunda = $this->postJson($this->url('paquete_sesiones', $paquete->id, '/generar'), [
            'clave_idempotencia' => $clave,
            'confirmar_advertencias' => true,
        ])->assertCreated();

        $this->assertSame($primera->json('data.lote_id'), $segunda->json('data.lote_id'));
        $this->assertTrue($segunda->json('data.reutilizado'));
        $this->assertDatabaseCount('turno_lote_generacion', 1);
        $this->assertDatabaseCount('turno', 9);

        $otraClave = $this->postJson($this->url('paquete_sesiones', $paquete->id, '/generar'), [
            'clave_idempotencia' => (string) Str::uuid(),
            'confirmar_advertencias' => true,
        ]);

        $otraClave
            ->assertStatus(409)
            ->assertJsonPath('code', 'TURNOS_YA_GENERADOS')
            ->assertJsonPath('context.regenerar_disponible', true);
    }

    public function test_patient_overlap_blocks_the_whole_generation_ca_003(): void
    {
        $escenario = $this->escenario();
        $paquete = $this->crearPaquete($escenario);

        Turno::factory()
            ->paraPaciente($escenario['paciente']->id)
            ->paraServicio($escenario['servicio']->id)
            ->paraSucursal($escenario['branch']->id)
            ->paraFecha('2025-12-02')
            ->paraHorario('09:00', '09:50')
            ->create();

        $preview = $this->getJson($this->url('paquete_sesiones', $paquete->id, '/preview-generacion'));

        $preview
            ->assertOk()
            ->assertJsonPath('data.resumen.bloqueados', 1)
            ->assertJsonPath('data.resumen.generables', 8)
            ->assertJsonPath('data.filas.0.hallazgos.0.tipo', 'paciente')
            ->assertJsonPath('data.filas.0.hallazgos.0.severidad', 'bloqueo');

        $response = $this->postJson($this->url('paquete_sesiones', $paquete->id, '/generar'), [
            'clave_idempotencia' => (string) Str::uuid(),
            'confirmar_advertencias' => true,
        ]);

        $response->assertStatus(409)->assertJsonPath('code', 'GENERACION_BLOQUEADA');
        $this->assertDatabaseCount('turno', 1);
        $this->assertDatabaseCount('turno_lote_generacion', 0);
    }

    public function test_professional_overlap_blocks_generation_ca_004(): void
    {
        $escenario = $this->escenario();
        $paquete = $this->crearPaquete($escenario, [
            'profesional_id' => $escenario['profesional']->id,
        ]);

        Turno::factory()
            ->paraPaciente($this->paciente($escenario['branch'])->id)
            ->paraServicio($escenario['servicio']->id)
            ->paraSucursal($escenario['branch']->id)
            ->conProfesional($escenario['profesional']->id)
            ->paraFecha('2025-12-02')
            ->paraHorario('09:00', '09:50')
            ->create();

        $preview = $this->getJson($this->url('paquete_sesiones', $paquete->id, '/preview-generacion'));

        $preview
            ->assertOk()
            ->assertJsonPath('data.resumen.bloqueados', 1)
            ->assertJsonPath('data.filas.0.hallazgos.0.tipo', 'profesional');
    }

    public function test_conflict_created_after_preview_is_revalidated_on_confirm(): void
    {
        $escenario = $this->escenario();
        $paquete = $this->crearPaquete($escenario);

        $this->getJson($this->url('paquete_sesiones', $paquete->id, '/preview-generacion'))
            ->assertOk()
            ->assertJsonPath('data.resumen.bloqueados', 0);

        Turno::factory()
            ->paraPaciente($escenario['paciente']->id)
            ->paraServicio($escenario['servicio']->id)
            ->paraSucursal($escenario['branch']->id)
            ->paraFecha('2025-12-02')
            ->paraHorario('09:00', '09:50')
            ->create();

        $this->postJson($this->url('paquete_sesiones', $paquete->id, '/generar'), [
            'clave_idempotencia' => (string) Str::uuid(),
            'confirmar_advertencias' => false,
        ])
            ->assertStatus(409)
            ->assertJsonPath('code', 'GENERACION_BLOQUEADA');
    }

    public function test_generation_without_professional_works_even_if_professionals_directory_fails_ca_006(): void
    {
        $this->app->instance(ProfessionalDirectoryContract::class, new class implements ProfessionalDirectoryContract
        {
            public function existe(int $profesionalId): bool
            {
                throw new LogicException('Profesionales no disponible');
            }

            public function estaActivo(int $profesionalId): bool
            {
                throw new LogicException('Profesionales no disponible');
            }

            public function nombres(array $profesionalIds): array
            {
                throw new LogicException('Profesionales no disponible');
            }

            public function nombresPorSucursal(int $sucursalId, bool $soloActivos = true): array
            {
                throw new LogicException('Profesionales no disponible');
            }

            public function asegurarActivo(int $profesionalId): void
            {
                throw new LogicException('Profesionales no disponible');
            }
        });

        $escenario = $this->escenario();
        $paquete = $this->crearPaquete($escenario, ['profesional_id' => null]);

        $preview = $this->getJson($this->url('paquete_sesiones', $paquete->id, '/preview-generacion'));

        $preview
            ->assertOk()
            ->assertJsonPath('data.resumen.generables', 9)
            ->assertJsonPath('data.origen.profesional', null);

        $this->postJson($this->url('paquete_sesiones', $paquete->id, '/generar'), [
            'clave_idempotencia' => (string) Str::uuid(),
            'confirmar_advertencias' => true,
        ])->assertCreated()->assertJsonPath('data.cantidad_creados', 9);
    }

    public function test_expired_package_is_not_generable_and_suggests_renewal_ca_005(): void
    {
        $escenario = $this->escenario();
        $paquete = $this->crearPaquete($escenario, [
            'fecha_inicio' => '2025-10-01',
            'fecha_fin' => '2025-10-31',
        ]);

        $this->getJson($this->url('paquete_sesiones', $paquete->id, '/preview-generacion'))
            ->assertStatus(409)
            ->assertJsonPath('code', 'ORIGEN_NO_GENERABLE')
            ->assertJsonPath('message', 'El paquete está vencido/a. Renovelo antes de generar turnos.');
    }

    public function test_user_without_branch_is_rejected_by_the_backend_ca_009(): void
    {
        $escenario = $this->escenario();
        $paquete = $this->crearPaquete($escenario);

        $user = User::factory()->create();
        $user->givePermissionTo(['turno.ver', 'turno.generar', 'paquete.ver']);
        Sanctum::actingAs($user);

        $this->getJson($this->url('paquete_sesiones', $paquete->id, '/preview-generacion'))
            ->assertStatus(403)
            ->assertJsonPath('code', 'BRANCH_ACCESS_DENIED');
    }

    public function test_transaction_rolls_back_turns_batch_and_audit_on_writer_failure_ca_008(): void
    {
        $escenario = $this->escenario();
        $paquete = $this->crearPaquete($escenario);

        $this->app->instance(TurnoBatchWriter::class, new class extends TurnoBatchWriter
        {
            public function insertar(array $filas): void
            {
                parent::insertar($filas);

                throw new RuntimeException('Fallo simulado a mitad de la inserción');
            }
        });

        $this->postJson($this->url('paquete_sesiones', $paquete->id, '/generar'), [
            'clave_idempotencia' => (string) Str::uuid(),
            'confirmar_advertencias' => true,
        ])->assertStatus(500);

        $this->assertDatabaseCount('turno', 0);
        $this->assertDatabaseCount('turno_lote_generacion', 0);
        $this->assertDatabaseMissing('bitacora', ['accion' => 'GENERAR']);
    }

    public function test_holidays_are_excluded_from_generation_rf_016(): void
    {
        $escenario = $this->escenario();
        $paquete = $this->crearPaquete($escenario);

        $this->postJson('/api/agenda-turnos/programacion-turnos/feriados', [
            'fecha' => '2025-12-09',
            'descripcion' => 'Feriado de prueba',
        ])->assertCreated();

        $preview = $this->getJson($this->url('paquete_sesiones', $paquete->id, '/preview-generacion'));

        $preview
            ->assertOk()
            ->assertJsonPath('data.resumen.feriados', 1)
            ->assertJsonPath('data.resumen.generables', 8);

        $this->postJson($this->url('paquete_sesiones', $paquete->id, '/generar'), [
            'clave_idempotencia' => (string) Str::uuid(),
            'confirmar_advertencias' => true,
        ])->assertCreated()->assertJsonPath('data.cantidad_creados', 8);

        $this->assertDatabaseMissing('turno', ['fecha' => '2025-12-09']);
    }

    public function test_sala_without_pattern_returns_clear_error_and_does_not_break_packages_rf_001(): void
    {
        $escenario = $this->escenario();
        $inscripcion = $this->crearInscripcionSala($escenario);
        $paquete = $this->crearPaquete($escenario);

        $this->getJson($this->url('inscripcion_sala', $inscripcion->id, '/preview-generacion'))
            ->assertStatus(422)
            ->assertJsonPath('code', 'PATRON_NO_DISPONIBLE')
            ->assertJsonPath(
                'message',
                'El patrón de días y hora de la sala todavía no está configurado.',
            );

        $this->getJson($this->url('paquete_sesiones', $paquete->id, '/preview-generacion'))
            ->assertOk()
            ->assertJsonPath('data.resumen.generables', 9);
    }

    public function test_group_session_with_several_children_does_not_false_block_ca_004(): void
    {
        $escenario = $this->escenario();
        $inscripcion = $this->crearInscripcionSala($escenario);

        $this->app->instance(SalaPatronContract::class, new class implements SalaPatronContract
        {
            public function patron(int $salaId): ?SalaPatronResumen
            {
                return new SalaPatronResumen([2], '09:00', 50, null);
            }
        });

        foreach ([1, 2] as $indice) {
            Turno::factory()
                ->paraSala($escenario['sala'])
                ->paraPaciente($this->paciente($escenario['branch'])->id)
                ->paraServicio($escenario['servicio']->id)
                ->paraFecha('2025-12-02')
                ->paraHorario('09:00', '09:50')
                ->create();
        }

        $preview = $this->getJson($this->url('inscripcion_sala', $inscripcion->id, '/preview-generacion'));

        $preview
            ->assertOk()
            ->assertJsonPath('data.resumen.bloqueados', 0)
            ->assertJsonPath('data.resumen.advertencias', 0)
            ->assertJsonPath('data.resumen.generables', 5);

        $this->postJson($this->url('inscripcion_sala', $inscripcion->id, '/generar'), [
            'clave_idempotencia' => (string) Str::uuid(),
            'confirmar_advertencias' => false,
        ])
            ->assertCreated()
            ->assertJsonPath('data.cantidad_creados', 5);
    }

    public function test_fifty_turns_are_generated_without_query_growth(): void
    {
        $escenario = $this->escenario();
        $paquete = $this->crearPaquete($escenario, [
            'cantidad_sesiones' => 50,
            'dias_semana' => [1, 3, 5],
            'fecha_inicio' => '2025-12-01',
            'fecha_fin' => '2026-06-30',
        ]);

        DB::flushQueryLog();
        DB::enableQueryLog();

        $response = $this->postJson($this->url('paquete_sesiones', $paquete->id, '/generar'), [
            'clave_idempotencia' => (string) Str::uuid(),
            'confirmar_advertencias' => true,
        ]);

        $queries = count(DB::getQueryLog());
        DB::disableQueryLog();

        $response->assertCreated()->assertJsonPath('data.cantidad_creados', 50);
        $this->assertLessThan(100, $queries, "Se ejecutaron {$queries} consultas para 50 turnos.");
    }

    public function test_there_is_no_delete_endpoint_for_turns_rnf_012(): void
    {
        $escenario = $this->escenario();
        $paquete = $this->crearPaquete($escenario);

        $this->deleteJson($this->url('paquete_sesiones', $paquete->id))
            ->assertStatus(405);
    }
}
