<?php

namespace Tests\Feature\Modules\InscripcionesAtenciones;

use App\Modules\CatalogoServicios\Models\Tarifa;
use App\Modules\CatalogoServicios\Support\CatalogoServiciosCacheKeys;
use Illuminate\Support\Facades\Cache;

final class VigenciasMultipleTest extends VigenciasTestCase
{
    public function test_multiple_renewal_is_atomic_and_creates_batch_audit(): void
    {
        [$sala, $paquete, $salaPreview, $paquetePreview] = $this->twoEntities();

        $response = $this->postJson(
            '/api/inscripciones-atenciones/vigencias/renovaciones',
            [
                'items' => [
                    [
                        'tipo_entidad' => 'inscripcion_sala',
                        'entidad_id' => $sala['id'],
                        'fecha_inicio' => $salaPreview['fecha_inicio'],
                        'fecha_fin' => $salaPreview['fecha_fin'],
                        'monto_visto' => $salaPreview['monto_vigente'],
                        'tarifa_id_vista' => $salaPreview['tarifa_vigente']['tarifa_id'],
                    ],
                    [
                        'tipo_entidad' => 'paquete_sesiones',
                        'entidad_id' => $paquete['id'],
                        'fecha_inicio' => $paquetePreview['fecha_inicio'],
                        'fecha_fin' => $paquetePreview['fecha_fin'],
                        'monto_visto' => $paquetePreview['monto_vigente'],
                        'tarifa_id_vista' => $paquetePreview['tarifa_vigente']['tarifa_id'],
                    ],
                ],
            ],
        )
            ->assertCreated()
            ->assertJsonCount(2, 'data.items');

        $this->assertNotNull($response->json('data.lote_id'));
        $this->assertDatabaseHas('inscripcion_sala', [
            'inscripcion_origen_id' => $sala['id'],
        ]);
        $this->assertDatabaseHas('paquete_sesiones', [
            'paquete_origen_id' => $paquete['id'],
        ]);
        $this->assertDatabaseHas('bitacora', [
            'entidad' => 'renovacion_multiple',
            'accion' => 'RENOVAR',
        ]);
    }

    public function test_multiple_renewal_rolls_back_all_when_one_tariff_is_not_confirmed(): void
    {
        [$sala, $paquete, $salaPreview, $paquetePreview] = $this->twoEntities();

        Tarifa::query()->whereKey($paquetePreview['tarifa_vigente']['tarifa_id'])->update([
            'monto' => '650.00',
        ]);
        Cache::forget(CatalogoServiciosCacheKeys::tarifas());

        $this->postJson(
            '/api/inscripciones-atenciones/vigencias/renovaciones',
            [
                'items' => [
                    [
                        'tipo_entidad' => 'inscripcion_sala',
                        'entidad_id' => $sala['id'],
                        'fecha_inicio' => $salaPreview['fecha_inicio'],
                        'fecha_fin' => $salaPreview['fecha_fin'],
                        'monto_visto' => $salaPreview['monto_vigente'],
                        'tarifa_id_vista' => $salaPreview['tarifa_vigente']['tarifa_id'],
                    ],
                    [
                        'tipo_entidad' => 'paquete_sesiones',
                        'entidad_id' => $paquete['id'],
                        'fecha_inicio' => $paquetePreview['fecha_inicio'],
                        'fecha_fin' => $paquetePreview['fecha_fin'],
                        'monto_visto' => $paquetePreview['monto_vigente'],
                        'tarifa_id_vista' => $paquetePreview['tarifa_vigente']['tarifa_id'],
                    ],
                ],
            ],
        )->assertStatus(409);

        $this->assertDatabaseCount('inscripcion_sala', 1);
        $this->assertDatabaseCount('paquete_sesiones', 1);
        $this->assertDatabaseMissing('inscripcion_sala', [
            'inscripcion_origen_id' => $sala['id'],
        ]);
        $this->assertDatabaseMissing('paquete_sesiones', [
            'paquete_origen_id' => $paquete['id'],
        ]);
    }

    public function test_multiple_renewal_rejects_entities_from_different_patients(): void
    {
        [$sala, $paquete, $salaPreview, $paquetePreview] = $this->twoEntities(differentPatient: true);

        $this->postJson(
            '/api/inscripciones-atenciones/vigencias/renovaciones',
            [
                'items' => [
                    [
                        'tipo_entidad' => 'inscripcion_sala',
                        'entidad_id' => $sala['id'],
                        'monto_visto' => $salaPreview['monto_vigente'],
                        'tarifa_id_vista' => $salaPreview['tarifa_vigente']['tarifa_id'],
                    ],
                    [
                        'tipo_entidad' => 'paquete_sesiones',
                        'entidad_id' => $paquete['id'],
                        'monto_visto' => $paquetePreview['monto_vigente'],
                        'tarifa_id_vista' => $paquetePreview['tarifa_vigente']['tarifa_id'],
                    ],
                ],
            ],
        )
            ->assertStatus(422)
            ->assertJsonPath('code', 'RENOVACION_MULTIPLE_INVALIDA');
    }

    /**
     * @return array{0: array<string, mixed>, 1: array<string, mixed>, 2: array<string, mixed>, 3: array<string, mixed>}
     */
    private function twoEntities(bool $differentPatient = false): array
    {
        $escenario = $this->escenarioPaquete('administracion');
        $paquete = $this->crearPaquete($escenario, [
            'fecha_inicio' => '2026-11-01',
            'fecha_fin' => '2026-11-28',
        ])->assertCreated()->json('data');

        $sala = $this->sala($escenario['branch']);
        $servicio = $this->servicioSalaGrupal();
        $categoria = $this->categoriaTarifa();
        $this->tarifa($servicio, $escenario['branch'], $categoria, '500.00');
        Cache::forget(CatalogoServiciosCacheKeys::tarifas());

        $paciente = $differentPatient ? $this->paciente($escenario['branch']) : $escenario['paciente'];
        $tutor = $differentPatient ? $this->tutor($escenario['branch']) : $escenario['tutor'];

        if ($differentPatient) {
            $this->asociarTutor($paciente, $tutor);
        }

        $salaData = $this->postJson(
            '/api/inscripciones-atenciones/salas-grupales',
            $this->payload(
                $paciente,
                $tutor,
                $escenario['branch'],
                $sala,
                $servicio,
                $categoria,
                [
                    'fecha_inicio' => '2026-11-01',
                    'fecha_fin' => '2026-11-28',
                ],
            ),
        )->assertCreated()->json('data');

        $salaPreview = $this->getJson(
            '/api/inscripciones-atenciones/vigencias/entidades/inscripcion_sala/'.$salaData['id'].'/renovacion',
        )->assertOk()->json('data');

        $paquetePreview = $this->getJson(
            '/api/inscripciones-atenciones/vigencias/entidades/paquete_sesiones/'.$paquete['id'].'/renovacion',
        )->assertOk()->json('data');

        return [$salaData, $paquete, $salaPreview, $paquetePreview];
    }
}
