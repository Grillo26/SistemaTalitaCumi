<?php

namespace Tests\Feature\Modules\Pagos;

use App\Modules\Pagos\Contracts\PayableEntityContract;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Testing\TestResponse;
use Tests\Feature\Modules\InscripcionesAtenciones\PaquetesSesionesTestCase;
use Tests\Support\FakePayableEntityContract;

abstract class PagosTestCase extends PaquetesSesionesTestCase
{
    /**
     * Inserta un paquete real con monto y estado dados (sin pasar por 06).
     *
     * @param  array<string, mixed>  $escenario
     * @param  array<string, mixed>  $overrides
     */
    protected function paqueteFila(array $escenario, array $overrides = []): int
    {
        return (int) DB::table('paquete_sesiones')->insertGetId(array_merge([
            'paciente_id' => $escenario['paciente']->id,
            'tutor_responsable_id' => $escenario['tutor']->id,
            'sucursal_id' => $escenario['branch']->id,
            'servicio_id' => $escenario['servicio']->id,
            'profesional_id' => null,
            'tarifa_id' => $escenario['tarifa']->id,
            'categoria_tarifa_id' => $escenario['categoria']->id,
            'cantidad_sesiones' => 10,
            'dias_semana' => '1,3',
            'hora' => '08:00',
            'duracion_minutos' => 50,
            'fecha_inicio' => '2026-11-26',
            'fecha_fin' => '2026-12-25',
            'monto_esperado' => '500.00',
            'estado' => 'activo',
            'created_at' => now(),
            'updated_at' => now(),
        ], $overrides));
    }

    protected function usarEntidadesFake(): void
    {
        $this->app->instance(PayableEntityContract::class, new FakePayableEntityContract);
    }

    /**
     * @param  list<array<string, mixed>>  $lineas
     * @param  array<string, mixed>  $overrides
     * @return array<string, mixed>
     */
    protected function payloadPago(int $paqueteId, array $lineas, array $overrides = []): array
    {
        return array_merge([
            'tipo_entidad' => 'paquete_sesiones',
            'entidad_id' => $paqueteId,
            'clave_idempotencia' => 'clave-'.Str::lower(Str::random(16)),
            'lineas' => $lineas,
        ], $overrides);
    }

    /**
     * @param  list<array<string, mixed>>  $lineas
     * @param  array<string, mixed>  $overrides
     */
    protected function registrar(int $paqueteId, array $lineas, array $overrides = []): TestResponse
    {
        return $this->postJson('/api/pagos', $this->payloadPago($paqueteId, $lineas, $overrides));
    }

    protected function efectivo(string $monto): array
    {
        return ['medio_pago' => 'efectivo', 'monto' => $monto];
    }

    protected function qr(string $monto, string $referencia = 'QR-REF-0001'): array
    {
        return ['medio_pago' => 'qr', 'monto' => $monto, 'referencia' => $referencia];
    }

    protected function bitacoraPagos(): Collection
    {
        return DB::table('bitacora')
            ->where('modulo', 'pagos')
            ->where('entidad', 'pago')
            ->orderBy('id')
            ->get();
    }

    protected function usuarioAdministracion(array $escenario): void
    {
        $admin = $this->actingAsRole('administracion');
        $this->withBranch($admin, $escenario['branch']);
    }
}
