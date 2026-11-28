<?php

namespace Tests\Feature\Modules\Pagos;

use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;

/**
 * Garantías de PostgreSQL (RN-006, RN-007, RF-005, RT-004). Se omiten en SQLite.
 */
final class PagoConstraintTest extends PagosTestCase
{
    public function test_postgresql_trigger_blocks_delete_and_financial_updates(): void
    {
        if (DB::getDriverName() !== 'pgsql') {
            $this->markTestSkipped('Trigger y CHECK específicos de PostgreSQL.');
        }

        $escenario = $this->escenarioPaquete('recepcion');
        $this->usarEntidadesFake();
        $paquete = $this->paqueteFila($escenario);
        $pagoId = $this->registrar($paquete, [$this->efectivo('100')])->assertCreated()->json('data.lineas.0.id');

        $this->expectException(QueryException::class);
        DB::table('pago')->where('id', $pagoId)->update(['monto' => '1.00']);
    }

    public function test_postgresql_trigger_blocks_delete(): void
    {
        if (DB::getDriverName() !== 'pgsql') {
            $this->markTestSkipped('Trigger específico de PostgreSQL.');
        }

        $escenario = $this->escenarioPaquete('recepcion');
        $this->usarEntidadesFake();
        $paquete = $this->paqueteFila($escenario);
        $pagoId = $this->registrar($paquete, [$this->efectivo('100')])->assertCreated()->json('data.lineas.0.id');

        try {
            DB::table('pago')->where('id', $pagoId)->delete();
            $this->fail('PostgreSQL permitió borrar un pago.');
        } catch (QueryException $exception) {
            $this->assertStringContainsString('no se eliminan', $exception->getMessage());
        }
    }

    public function test_postgresql_allows_only_cancellation_fields_to_change(): void
    {
        if (DB::getDriverName() !== 'pgsql') {
            $this->markTestSkipped('Trigger específico de PostgreSQL.');
        }

        $escenario = $this->escenarioPaquete('recepcion');
        $this->usarEntidadesFake();
        $paquete = $this->paqueteFila($escenario);
        $pagoId = $this->registrar($paquete, [$this->efectivo('100')])->assertCreated()->json('data.lineas.0.id');

        $afectadas = DB::table('pago')->where('id', $pagoId)->update([
            'estado' => 'anulado',
            'anulado_en' => now(),
            'anulado_por' => $escenario['user']->id,
            'motivo_anulacion' => 'Error de monto',
        ]);

        $this->assertSame(1, $afectadas);
    }

    public function test_postgresql_rejects_unknown_medium_and_qr_without_reference(): void
    {
        if (DB::getDriverName() !== 'pgsql') {
            $this->markTestSkipped('FK y CHECK específicos de PostgreSQL.');
        }

        $escenario = $this->escenarioPaquete('recepcion');
        $this->usarEntidadesFake();
        $paquete = $this->paqueteFila($escenario);
        $base = [
            'numero_correlativo' => 'TC-000900',
            'sucursal_id' => $escenario['branch']->id,
            'sucursal_codigo_snapshot' => 'C',
            'sucursal_nombre_snapshot' => 'C',
            'paciente_id' => $escenario['paciente']->id,
            'paciente_nombre_snapshot' => 'P',
            'tutor_id' => $escenario['tutor']->id,
            'tutor_nombre_snapshot' => 'T',
            'paquete_sesiones_id' => $paquete,
            'entidad_referencia_snapshot' => 'R',
            'monto_esperado_snapshot' => '500.00',
            'monto' => '10.00',
            'saldo_resultante' => '490.00',
            'grupo_pago_id' => '00000000-0000-4000-8000-000000000042',
            'fecha_pago' => '2026-11-25',
            'estado' => 'vigente',
            'usuario_registro_id' => $escenario['user']->id,
            'created_at' => now(),
            'updated_at' => now(),
        ];

        try {
            DB::table('pago')->insert($base + ['medio_pago' => 'tarjeta']);
            $this->fail('Se aceptó un medio fuera del catálogo.');
        } catch (QueryException) {
            $this->addToAssertionCount(1);
        }

        try {
            DB::table('pago')->insert($base + ['medio_pago' => 'qr', 'referencia_pago' => null]);
            $this->fail('Se aceptó QR sin referencia.');
        } catch (QueryException) {
            $this->addToAssertionCount(1);
        }
    }

    public function test_postgresql_requires_exactly_one_linked_entity(): void
    {
        if (DB::getDriverName() !== 'pgsql') {
            $this->markTestSkipped('CHECK específico de PostgreSQL.');
        }

        $escenario = $this->escenarioPaquete('recepcion');
        $this->usarEntidadesFake();
        $paquete = $this->paqueteFila($escenario);

        try {
            DB::table('pago')->insert([
                'numero_correlativo' => 'TC-000901',
                'sucursal_id' => $escenario['branch']->id,
                'sucursal_codigo_snapshot' => 'C',
                'sucursal_nombre_snapshot' => 'C',
                'paciente_id' => $escenario['paciente']->id,
                'paciente_nombre_snapshot' => 'P',
                'tutor_id' => $escenario['tutor']->id,
                'tutor_nombre_snapshot' => 'T',
                'entidad_referencia_snapshot' => 'R',
                'monto_esperado_snapshot' => '500.00',
                'monto' => '10.00',
                'saldo_resultante' => '490.00',
                'medio_pago' => 'efectivo',
                'grupo_pago_id' => '00000000-0000-4000-8000-000000000043',
                'fecha_pago' => '2026-11-25',
                'estado' => 'vigente',
                'usuario_registro_id' => $escenario['user']->id,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
            $this->fail('Se aceptó un pago sin entidad.');
        } catch (QueryException) {
            $this->addToAssertionCount(1);
        }

        $this->assertNotNull($paquete);
    }
}
