<?php

namespace Tests\Feature\Modules\Pagos;

use App\Modules\Pagos\Models\Pago;
use Illuminate\Support\Facades\DB;

final class PagoRegistroTest extends PagosTestCase
{
    public function test_ca001_total_payment_closes_balance_and_is_audited(): void
    {
        $escenario = $this->escenarioPaquete('recepcion');
        $this->usarEntidadesFake();
        $paquete = $this->paqueteFila($escenario);

        $this->registrar($paquete, [$this->efectivo('500')])
            ->assertCreated()
            ->assertJsonPath('data.saldo_resultante', '0.00')
            ->assertJsonPath('data.replay', false)
            ->assertJsonPath('data.lineas.0.monto', '500.00')
            ->assertJsonPath('data.lineas.0.medio_pago', 'efectivo')
            ->assertJsonPath('data.lineas.0.numero_correlativo', 'TC-000001')
            ->assertJsonPath('data.lineas.0.numero_comprobante', $escenario['branch']->codigo.'-TC-000001');

        $this->assertDatabaseHas('pago', [
            'paquete_sesiones_id' => $paquete,
            'monto' => '500.00',
            'saldo_resultante' => '0.00',
            'estado' => 'vigente',
        ]);

        $bitacora = $this->bitacoraPagos();
        $this->assertCount(1, $bitacora);
        $this->assertSame('CREAR', $bitacora->first()->accion);
        $this->assertFalse((bool) $bitacora->first()->critico);
        $this->assertSame($escenario['branch']->id, $bitacora->first()->sucursal_id);
        $this->assertSame($escenario['user']->id, $bitacora->first()->usuario_id);
    }

    public function test_ca002_partial_payment_leaves_pending_balance(): void
    {
        $escenario = $this->escenarioPaquete('recepcion');
        $this->usarEntidadesFake();
        $paquete = $this->paqueteFila($escenario);

        $this->registrar($paquete, [$this->qr('200', 'BANCO-998877')])
            ->assertCreated()
            ->assertJsonPath('data.saldo_resultante', '300.00')
            ->assertJsonPath('data.lineas.0.referencia_pago', 'BANCO-998877');

        $this->assertDatabaseHas('pago', ['medio_pago' => 'qr', 'referencia_pago' => 'BANCO-998877']);
    }

    public function test_ca003_payment_above_balance_is_blocked_with_maximum(): void
    {
        $escenario = $this->escenarioPaquete('recepcion');
        $this->usarEntidadesFake();
        $paquete = $this->paqueteFila($escenario);

        $this->registrar($paquete, [$this->efectivo('600')])
            ->assertStatus(409)
            ->assertJsonPath('code', 'PAGO_SALDO_EXCEDIDO')
            ->assertJsonPath('saldo_maximo', '500.00');

        $this->assertSame(0, Pago::query()->count());
    }

    public function test_ca004_numbers_are_sequential_per_branch_and_unique(): void
    {
        $escenario = $this->escenarioPaquete('recepcion');
        $this->usarEntidadesFake();
        $paquete = $this->paqueteFila($escenario);

        $this->registrar($paquete, [$this->efectivo('100')])->assertJsonPath('data.lineas.0.numero_correlativo', 'TC-000001');
        $this->registrar($paquete, [$this->efectivo('100')])->assertJsonPath('data.lineas.0.numero_correlativo', 'TC-000002');
        $this->registrar($paquete, [$this->efectivo('100')])->assertJsonPath('data.lineas.0.numero_correlativo', 'TC-000003');

        $this->assertSame(3, Pago::query()->where('sucursal_id', $escenario['branch']->id)->distinct()->count('numero_correlativo'));
        $this->assertDatabaseHas('secuencia_pago', ['sucursal_id' => $escenario['branch']->id, 'ultimo_numero' => 3]);
    }

    public function test_combined_payment_creates_linked_lines_with_one_group_and_one_total(): void
    {
        $escenario = $this->escenarioPaquete('recepcion');
        $this->usarEntidadesFake();
        $paquete = $this->paqueteFila($escenario);

        $respuesta = $this->registrar($paquete, [
            $this->efectivo('150.50'),
            $this->qr('349.50', 'QR-COMBINADO-1'),
        ])->assertCreated()
            ->assertJsonPath('data.saldo_resultante', '0.00')
            ->assertJsonCount(2, 'data.lineas');

        $grupo = $respuesta->json('data.grupo_pago_id');
        $this->assertNotEmpty($grupo);
        $this->assertSame(2, Pago::query()->where('grupo_pago_id', $grupo)->count());
        $this->assertSame(
            ['TC-000001', 'TC-000002'],
            Pago::query()->where('grupo_pago_id', $grupo)->orderBy('id')->pluck('numero_correlativo')->all(),
        );
        $this->assertSame(2, $this->bitacoraPagos()->count());
    }

    public function test_combined_total_above_balance_creates_nothing(): void
    {
        $escenario = $this->escenarioPaquete('recepcion');
        $this->usarEntidadesFake();
        $paquete = $this->paqueteFila($escenario);

        $this->registrar($paquete, [$this->efectivo('300'), $this->qr('250', 'QR-EXCESO')])
            ->assertStatus(409)
            ->assertJsonPath('saldo_maximo', '500.00');

        $this->assertSame(0, Pago::query()->count());
        $this->assertSame(0, $this->bitacoraPagos()->count());
    }

    public function test_same_key_and_same_content_creates_a_single_payment(): void
    {
        $escenario = $this->escenarioPaquete('recepcion');
        $this->usarEntidadesFake();
        $paquete = $this->paqueteFila($escenario);
        $payload = $this->payloadPago($paquete, [$this->efectivo('200')], ['clave_idempotencia' => 'doble-clic-0001']);

        $primera = $this->postJson('/api/pagos', $payload)->assertCreated();
        $segunda = $this->postJson('/api/pagos', $payload)
            ->assertOk()
            ->assertJsonPath('data.replay', true);

        $this->assertSame($primera->json('data.grupo_pago_id'), $segunda->json('data.grupo_pago_id'));
        $this->assertSame(1, Pago::query()->count());
        $this->assertDatabaseHas('secuencia_pago', ['sucursal_id' => $escenario['branch']->id, 'ultimo_numero' => 1]);
    }

    public function test_same_key_with_different_content_is_a_conflict(): void
    {
        $escenario = $this->escenarioPaquete('recepcion');
        $this->usarEntidadesFake();
        $paquete = $this->paqueteFila($escenario);

        $this->postJson('/api/pagos', $this->payloadPago($paquete, [$this->efectivo('200')], ['clave_idempotencia' => 'clave-reutilizada']))
            ->assertCreated();

        $this->postJson('/api/pagos', $this->payloadPago($paquete, [$this->efectivo('250')], ['clave_idempotencia' => 'clave-reutilizada']))
            ->assertStatus(409)
            ->assertJsonPath('code', 'PAGO_IDEMPOTENCIA_CONFLICTO');

        $this->assertSame(1, Pago::query()->count());
    }

    public function test_audit_failure_rolls_back_payment_and_correlative_without_gaps(): void
    {
        $escenario = $this->escenarioPaquete('recepcion');
        $this->usarEntidadesFake();
        $paquete = $this->paqueteFila($escenario);

        $this->crearTriggerFalloBitacora();

        $this->registrar($paquete, [$this->efectivo('100')])
            ->assertStatus(503)
            ->assertJsonPath('code', 'AUDIT_UNAVAILABLE');

        $this->assertSame(0, Pago::query()->count());
        $this->assertSame(0, DB::table('pago_idempotencia')->count());
        $this->assertSame(0, (int) DB::table('secuencia_pago')->where('sucursal_id', $escenario['branch']->id)->value('ultimo_numero'));

        $this->quitarTriggerFalloBitacora();

        $this->registrar($paquete, [$this->efectivo('100')])
            ->assertCreated()
            ->assertJsonPath('data.lineas.0.numero_correlativo', 'TC-000001');
    }

    public function test_future_date_requires_authorization_and_reason_and_is_critical(): void
    {
        $escenario = $this->escenarioPaquete('recepcion');
        $this->usarEntidadesFake();
        $paquete = $this->paqueteFila($escenario);
        $futuro = ['fecha_pago' => '2026-11-26'];

        $this->registrar($paquete, [$this->efectivo('100')], $futuro + ['motivo_fecha_futura' => 'Cobro adelantado'])
            ->assertForbidden()
            ->assertJsonPath('code', 'PAGO_FECHA_FUTURA_NO_AUTORIZADA');

        $this->usarAdministracion($escenario);

        $this->registrar($paquete, [$this->efectivo('100')], $futuro)
            ->assertStatus(422)
            ->assertJsonPath('code', 'PAGO_FECHA_INVALIDA');

        $this->registrar($paquete, [$this->efectivo('100')], $futuro + ['motivo_fecha_futura' => 'Familia viaja y paga antes'])
            ->assertCreated()
            ->assertJsonPath('data.lineas.0.fecha_futura_autorizada', true);

        $this->assertTrue((bool) $this->bitacoraPagos()->last()->critico);
    }

    public function test_past_dates_are_allowed_without_authorization(): void
    {
        $escenario = $this->escenarioPaquete('recepcion');
        $this->usarEntidadesFake();
        $paquete = $this->paqueteFila($escenario);

        $this->registrar($paquete, [$this->efectivo('100')], ['fecha_pago' => '2026-11-20'])
            ->assertCreated()
            ->assertJsonPath('data.lineas.0.fecha_pago', '2026-11-20');
    }

    public function test_tutor_must_be_active_and_linked_to_the_patient(): void
    {
        $escenario = $this->escenarioPaquete('recepcion');
        $this->usarEntidadesFake();
        $paquete = $this->paqueteFila($escenario);
        $ajeno = $this->tutor($escenario['branch']);

        $this->registrar($paquete, [$this->efectivo('100')], ['tutor_id' => $ajeno->id])
            ->assertStatus(422)
            ->assertJsonPath('code', 'PAGO_TUTOR_INVALIDO');

        $this->registrar($paquete, [$this->efectivo('100')], ['tutor_id' => $escenario['tutor']->id])
            ->assertCreated()
            ->assertJsonPath('data.lineas.0.tutor_id', $escenario['tutor']->id);
    }

    public function test_qr_without_reference_and_unknown_medium_are_rejected(): void
    {
        $escenario = $this->escenarioPaquete('recepcion');
        $this->usarEntidadesFake();
        $paquete = $this->paqueteFila($escenario);

        $this->registrar($paquete, [['medio_pago' => 'qr', 'monto' => '100']])
            ->assertStatus(422)
            ->assertJsonPath('code', 'PAGO_LINEAS_INVALIDAS');

        $this->registrar($paquete, [['medio_pago' => 'tarjeta', 'monto' => '100']])
            ->assertStatus(422)
            ->assertJsonPath('code', 'PAGO_LINEAS_INVALIDAS');

        $this->assertSame(0, Pago::query()->count());
    }

    public function test_amount_rules_reject_rounding_zero_and_floats(): void
    {
        $escenario = $this->escenarioPaquete('recepcion');
        $this->usarEntidadesFake();
        $paquete = $this->paqueteFila($escenario);

        foreach (['100.123', '0', '0.00', '-5', 'abc'] as $monto) {
            $this->registrar($paquete, [$this->efectivo($monto)])
                ->assertStatus(422)
                ->assertJsonPath('code', 'PAGO_MONTO_INVALIDO');
        }

        $this->registrar($paquete, [['medio_pago' => 'efectivo', 'monto' => 12.5]])
            ->assertStatus(422)
            ->assertJsonPath('code', 'PAGO_MONTO_INVALIDO');

        $this->assertSame(0, Pago::query()->count());
    }

    public function test_retired_entity_cannot_be_paid(): void
    {
        $escenario = $this->escenarioPaquete('recepcion');
        $this->usarEntidadesFake();
        $paquete = $this->paqueteFila($escenario, ['estado' => 'retirado']);

        $this->registrar($paquete, [$this->efectivo('100')])
            ->assertStatus(409)
            ->assertJsonPath('code', 'ENTIDAD_NO_PAGABLE');
    }

    public function test_entity_from_another_branch_is_not_found_for_recepcion(): void
    {
        $escenario = $this->escenarioPaquete('recepcion');
        $this->usarEntidadesFake();

        $otra = $this->branch();
        $paciente = $this->paciente($otra);
        $tutor = $this->tutor($otra);
        $this->asociarTutor($paciente, $tutor);
        $ajeno = $this->paqueteFila([
            'paciente' => $paciente,
            'tutor' => $tutor,
            'branch' => $otra,
            'servicio' => $escenario['servicio'],
            'tarifa' => $escenario['tarifa'],
            'categoria' => $escenario['categoria'],
        ]);

        $this->registrar($ajeno, [$this->efectivo('100')])
            ->assertNotFound()
            ->assertJsonPath('code', 'ENTIDAD_NOT_FOUND');
    }

    public function test_inactive_branch_blocks_registration(): void
    {
        $escenario = $this->escenarioPaquete('recepcion');
        $this->usarEntidadesFake();
        $paquete = $this->paqueteFila($escenario);

        DB::table('sucursal')->where('id', $escenario['branch']->id)->update(['activo' => false]);

        // Una sucursal inactiva deja de ser accesible (UserBranchAccessContract, fail-closed),
        // por eso responde 404 antes de llegar a asegurarActiva().
        $this->registrar($paquete, [$this->efectivo('100')])
            ->assertNotFound()
            ->assertJsonPath('code', 'ENTIDAD_NOT_FOUND');

        $this->assertSame(0, Pago::query()->count());
    }

    public function test_without_a_connected_payable_module_the_api_returns_503_and_creates_nothing(): void
    {
        $escenario = $this->escenarioPaquete('recepcion');
        $paquete = $this->paqueteFila($escenario);

        $this->registrar($paquete, [$this->efectivo('100')])
            ->assertStatus(503)
            ->assertJsonPath('code', 'MODULO_NO_DISPONIBLE');

        $this->postJson('/api/pagos', $this->payloadPago($paquete, [$this->efectivo('100')], [
            'tipo_entidad' => 'venta_material',
        ]))->assertStatus(503)->assertJsonPath('code', 'MODULO_NO_DISPONIBLE');

        $this->assertSame(0, Pago::query()->count());
    }

    public function test_idempotency_key_is_required_and_bounded(): void
    {
        $escenario = $this->escenarioPaquete('recepcion');
        $this->usarEntidadesFake();
        $paquete = $this->paqueteFila($escenario);

        $this->postJson('/api/pagos', $this->payloadPago($paquete, [$this->efectivo('100')], ['clave_idempotencia' => '']))
            ->assertStatus(422);
        $this->postJson('/api/pagos', $this->payloadPago($paquete, [$this->efectivo('100')], ['clave_idempotencia' => 'corta']))
            ->assertStatus(422);
    }

    public function test_recepcion_cannot_export_or_delete_and_delete_is_always_409(): void
    {
        $escenario = $this->escenarioPaquete('recepcion');
        $this->usarEntidadesFake();
        $paquete = $this->paqueteFila($escenario);
        $pago = $this->registrar($paquete, [$this->efectivo('100')])->assertCreated();

        $this->getJson('/api/pagos/exportar')->assertForbidden();

        $this->deleteJson('/api/pagos/'.$pago->json('data.lineas.0.id'))
            ->assertStatus(409)
            ->assertJsonPath('code', 'PAGO_DELETE_NOT_ALLOWED');

        $this->assertSame(1, Pago::query()->count());
    }

    private function usarAdministracion(array $escenario): void
    {
        $this->usuarioAdministracion($escenario);
    }

    private function crearTriggerFalloBitacora(): void
    {
        if (DB::getDriverName() === 'pgsql') {
            DB::unprepared(<<<'SQL'
                CREATE OR REPLACE FUNCTION fn_test_fallo_bitacora_pagos() RETURNS trigger AS $$
                BEGIN RAISE EXCEPTION 'bitácora no disponible (prueba)'; END;
                $$ LANGUAGE plpgsql;
                CREATE TRIGGER tg_test_fallo_bitacora_pagos BEFORE INSERT ON bitacora
                FOR EACH ROW WHEN (NEW.modulo = 'pagos') EXECUTE FUNCTION fn_test_fallo_bitacora_pagos();
            SQL);

            return;
        }

        DB::unprepared("CREATE TRIGGER tg_test_fallo_bitacora_pagos BEFORE INSERT ON bitacora WHEN NEW.modulo = 'pagos' BEGIN SELECT RAISE(ABORT, 'bitacora no disponible (prueba)'); END;");
    }

    private function quitarTriggerFalloBitacora(): void
    {
        if (DB::getDriverName() === 'pgsql') {
            DB::unprepared('DROP TRIGGER IF EXISTS tg_test_fallo_bitacora_pagos ON bitacora');
            DB::unprepared('DROP FUNCTION IF EXISTS fn_test_fallo_bitacora_pagos()');

            return;
        }

        DB::unprepared('DROP TRIGGER IF EXISTS tg_test_fallo_bitacora_pagos');
    }
}
