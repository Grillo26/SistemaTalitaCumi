<?php

namespace Tests\Feature\Modules\AgendaTurnos;

use App\Modules\AgendaTurnos\Models\Turno;
use Carbon\Carbon;
use Carbon\CarbonImmutable;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;

/**
 * [Programación de Turnos] RF-010 / RF-011 / RF-014 / RN-006 / RT-004 /
 * CA-007 / CA-008.
 */
final class RegeneracionTurnosTest extends ProgramacionTurnosTestCase
{
    public function test_regeneration_cancels_only_future_programmed_and_preserves_attendance_ca_007(): void
    {
        $escenario = $this->escenario();
        $paquete = $this->crearPaquete($escenario);

        $this->postJson($this->url('paquete_sesiones', $paquete->id, '/generar'), [
            'clave_idempotencia' => (string) Str::uuid(),
            'confirmar_advertencias' => true,
        ])->assertCreated()->assertJsonPath('data.cantidad_creados', 9);

        $this->viajarA('2025-12-15 10:00:00');

        $turnos = Turno::query()
            ->where('paquete_sesiones_id', $paquete->id)
            ->ordenado()
            ->get();

        $asistio = $this->turnoEnFecha($turnos, '2025-12-02');
        $falto = $this->turnoEnFecha($turnos, '2025-12-04');
        $asistio->forceFill(['estado' => 'asistio'])->save();
        $falto->forceFill(['estado' => 'falto'])->save();

        $preview = $this->getJson(
            $this->url('paquete_sesiones', $paquete->id, '/preview-regeneracion'),
        );

        $preview
            ->assertOk()
            ->assertJsonPath('data.objetivo', 8)
            ->assertJsonPath('data.resumen.cancelar', 5)
            ->assertJsonPath('data.resumen.preservar', 4)
            ->assertJsonPath('data.resumen.asistidos', 1)
            ->assertJsonPath('data.resumen.faltados', 1)
            ->assertJsonPath('data.resumen.pasados_sin_marcar', 2)
            ->assertJsonPath('data.resumen.generables', 5)
            ->assertJsonPath('data.resumen.faltantes', 3)
            ->assertJsonPath('data.resumen.puede_confirmar', false)
            ->assertJsonCount(5, 'data.cancelar')
            ->assertJsonCount(4, 'data.preservar');

        $clave = (string) Str::uuid();
        $response = $this->postJson(
            $this->url('paquete_sesiones', $paquete->id, '/regenerar'),
            [
                'clave_idempotencia' => $clave,
                'confirmar_advertencias' => true,
            ],
        );

        $response
            ->assertOk()
            ->assertJsonPath('data.accion', 'regenerar')
            ->assertJsonPath('data.cantidad_creados', 5)
            ->assertJsonPath('data.cantidad_cancelados', 5)
            ->assertJsonPath('data.cantidad_preservados', 4);

        $loteId = (int) $response->json('data.lote_id');

        $this->assertDatabaseHas('turno', [
            'id' => $asistio->id,
            'estado' => 'asistio',
            'cancelado_por_lote_id' => null,
        ]);
        $this->assertDatabaseHas('turno', [
            'id' => $falto->id,
            'estado' => 'falto',
            'cancelado_por_lote_id' => null,
        ]);

        foreach (['2025-12-09', '2025-12-11'] as $fecha) {
            $this->assertDatabaseHas('turno', [
                'paquete_sesiones_id' => $paquete->id,
                'fecha' => $fecha,
                'estado' => 'programado',
            ]);
        }

        foreach (['2025-12-16', '2025-12-18', '2025-12-23', '2025-12-25', '2025-12-30'] as $fecha) {
            $this->assertDatabaseHas('turno', [
                'paquete_sesiones_id' => $paquete->id,
                'fecha' => $fecha,
                'estado' => 'cancelado',
                'cancelado_por_lote_id' => $loteId,
                'cancelacion_origen' => 'regeneracion',
            ]);
        }

        $this->assertSame(5, Turno::query()
            ->where('paquete_sesiones_id', $paquete->id)
            ->where('lote_generacion_id', $loteId)
            ->count());

        $this->assertDatabaseHas('bitacora', [
            'accion' => 'REGENERAR',
            'entidad' => 'turno_lote_generacion',
            'entidad_id' => $loteId,
            'critico' => true,
        ]);

        $reintento = $this->postJson(
            $this->url('paquete_sesiones', $paquete->id, '/regenerar'),
            [
                'clave_idempotencia' => $clave,
                'confirmar_advertencias' => true,
            ],
        )->assertOk();

        $this->assertTrue($reintento->json('data.reutilizado'));
        $this->assertSame($loteId, (int) $reintento->json('data.lote_id'));
    }

    public function test_regeneration_with_zero_remaining_sessions_only_preserves(): void
    {
        $escenario = $this->escenario();
        $paquete = $this->crearPaquete($escenario, [
            'cantidad_sesiones' => 2,
        ]);

        $this->postJson($this->url('paquete_sesiones', $paquete->id, '/generar'), [
            'clave_idempotencia' => (string) Str::uuid(),
            'confirmar_advertencias' => true,
        ])->assertCreated()->assertJsonPath('data.cantidad_creados', 2);

        $turnos = Turno::query()
            ->where('paquete_sesiones_id', $paquete->id)
            ->ordenado()
            ->get();

        $turnos[0]->forceFill(['estado' => 'asistio'])->save();
        $turnos[1]->forceFill(['estado' => 'falto'])->save();

        $this->viajarA('2025-12-15 10:00:00');

        $preview = $this->getJson(
            $this->url('paquete_sesiones', $paquete->id, '/preview-regeneracion'),
        );

        $preview
            ->assertOk()
            ->assertJsonPath('data.objetivo', 0)
            ->assertJsonPath('data.resumen.cancelar', 0)
            ->assertJsonPath('data.resumen.preservar', 2)
            ->assertJsonPath('data.resumen.generables', 0)
            ->assertJsonPath('data.resumen.puede_confirmar', true);

        $this->postJson($this->url('paquete_sesiones', $paquete->id, '/regenerar'), [
            'clave_idempotencia' => (string) Str::uuid(),
            'confirmar_advertencias' => false,
        ])
            ->assertOk()
            ->assertJsonPath('data.cantidad_creados', 0)
            ->assertJsonPath('data.cantidad_cancelados', 0)
            ->assertJsonPath('data.cantidad_preservados', 2);
    }

    public function test_regeneration_requires_confirmation_when_sessions_are_missing(): void
    {
        $escenario = $this->escenario();
        $paquete = $this->crearPaquete($escenario);

        $this->postJson($this->url('paquete_sesiones', $paquete->id, '/generar'), [
            'clave_idempotencia' => (string) Str::uuid(),
            'confirmar_advertencias' => true,
        ])->assertCreated();

        $this->viajarA('2025-12-15 10:00:00');

        $this->postJson($this->url('paquete_sesiones', $paquete->id, '/regenerar'), [
            'clave_idempotencia' => (string) Str::uuid(),
            'confirmar_advertencias' => false,
        ])
            ->assertStatus(422)
            ->assertJsonPath('code', 'ADVERTENCIAS_NO_CONFIRMADAS');
    }

    /**
     * @param  Collection<int, Turno>  $turnos
     */
    private function turnoEnFecha($turnos, string $fecha): Turno
    {
        return $turnos->firstOrFail(
            static fn (Turno $turno): bool => $turno->fecha->toDateString() === $fecha,
        );
    }

    private function viajarA(string $fechaHora): void
    {
        Carbon::setTestNow(Carbon::parse($fechaHora, 'America/La_Paz'));
        CarbonImmutable::setTestNow(CarbonImmutable::parse($fechaHora, 'America/La_Paz'));
    }
}
