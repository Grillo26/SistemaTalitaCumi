<?php

namespace Tests\Feature\Modules\Pagos;

use App\Modules\Pagos\Contracts\PagosDelDiaContract;
use App\Modules\Pagos\Enums\TipoEntidadPago;
use Illuminate\Support\Facades\DB;

final class PagoListadoTest extends PagosTestCase
{
    public function test_listing_is_scoped_and_filtering_another_branch_returns_empty(): void
    {
        $escenario = $this->escenarioPaquete('recepcion');
        $this->usarEntidadesFake();
        $paquete = $this->paqueteFila($escenario);
        $this->registrar($paquete, [$this->efectivo('100')])->assertCreated();

        $otra = $this->branch();

        $this->getJson('/api/pagos')
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.numero_correlativo', 'TC-000001')
            ->assertJsonPath('can.register', true)
            ->assertJsonPath('can.export', false);

        $this->getJson('/api/pagos?sucursal_id='.$otra->id)
            ->assertOk()
            ->assertJsonCount(0, 'data');
    }

    public function test_pagination_and_sort_are_bounded_by_whitelist(): void
    {
        $escenario = $this->escenarioPaquete('recepcion');
        $this->usarEntidadesFake();

        $this->getJson('/api/pagos?per_page=51')->assertStatus(422);
        $this->getJson('/api/pagos?sort=monto;drop')->assertStatus(422);
        $this->getJson('/api/pagos?sort=monto&direction=asc&per_page=50')->assertOk();
    }

    public function test_detail_shows_the_whole_group_and_is_hidden_outside_scope(): void
    {
        $escenario = $this->escenarioPaquete('recepcion');
        $this->usarEntidadesFake();
        $paquete = $this->paqueteFila($escenario);

        $grupo = $this->registrar($paquete, [$this->efectivo('100'), $this->qr('50', 'REF-DETALLE')])->assertCreated();
        $pagoId = $grupo->json('data.lineas.0.id');

        $this->getJson('/api/pagos/'.$pagoId)
            ->assertOk()
            ->assertJsonPath('data.id', $pagoId)
            ->assertJsonCount(2, 'data.lineas')
            ->assertJsonPath('data.lineas.1.referencia_pago', 'REF-DETALLE');

        $ajeno = DB::table('pago')->insertGetId([
            'numero_correlativo' => 'ZZ-000001',
            'sucursal_id' => $this->branch()->id,
            'sucursal_codigo_snapshot' => 'ZZ',
            'sucursal_nombre_snapshot' => 'Ajena',
            'paciente_id' => $escenario['paciente']->id,
            'paciente_nombre_snapshot' => 'Ajeno',
            'tutor_id' => $escenario['tutor']->id,
            'tutor_nombre_snapshot' => 'Ajeno',
            'paquete_sesiones_id' => $paquete,
            'entidad_referencia_snapshot' => 'x',
            'monto_esperado_snapshot' => '500.00',
            'monto' => '10.00',
            'saldo_resultante' => '490.00',
            'medio_pago' => 'efectivo',
            'grupo_pago_id' => '00000000-0000-4000-8000-000000000009',
            'fecha_pago' => '2026-11-25',
            'estado' => 'vigente',
            'usuario_registro_id' => $escenario['user']->id,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $this->getJson('/api/pagos/'.$ajeno)->assertNotFound()->assertJsonPath('code', 'PAGO_NOT_FOUND');
    }

    public function test_entity_history_marks_cancelled_payments_and_excludes_them_from_totals(): void
    {
        $escenario = $this->escenarioPaquete('recepcion');
        $this->usarEntidadesFake();
        $paquete = $this->paqueteFila($escenario);

        $respuesta = $this->registrar($paquete, [$this->efectivo('300'), $this->qr('200', 'REF-HIST')])->assertCreated();
        $anulado = $respuesta->json('data.lineas.1.id');

        DB::table('pago')->where('id', $anulado)->update([
            'estado' => 'anulado',
            'anulado_en' => now(),
            'anulado_por' => $escenario['user']->id,
            'motivo_anulacion' => 'Cobro revertido',
        ]);

        $this->getJson('/api/pagos/entidades/paquete_sesiones/'.$paquete.'/historial')
            ->assertOk()
            ->assertJsonPath('data.monto_esperado', '500.00')
            ->assertJsonPath('data.total_pagado', '300.00')
            ->assertJsonPath('data.saldo', '200.00')
            ->assertJsonCount(2, 'data.pagos')
            ->assertJsonPath('data.pagos.1.estado', 'anulado');

        $this->registrar($paquete, [$this->qr('200', 'REF-NUEVO')])
            ->assertCreated()
            ->assertJsonPath('data.saldo_resultante', '0.00');
    }

    public function test_daily_payments_for_cash_close_are_split_by_medium_and_exclude_cancelled(): void
    {
        $escenario = $this->escenarioPaquete('recepcion');
        $this->usarEntidadesFake();
        $paquete = $this->paqueteFila($escenario);

        $respuesta = $this->registrar($paquete, [$this->efectivo('300'), $this->qr('150', 'REF-DIA')])->assertCreated();
        DB::table('pago')->where('id', $respuesta->json('data.lineas.1.id'))->update([
            'estado' => 'anulado',
            'anulado_en' => now(),
            'anulado_por' => $escenario['user']->id,
            'motivo_anulacion' => 'Prueba',
        ]);

        $resumen = app(PagosDelDiaContract::class)->resumen($escenario['branch']->id, '2026-11-25');

        $this->assertSame('300.00', $resumen->total);
        $this->assertSame(1, $resumen->cantidad);
        $this->assertSame(['cantidad' => 1, 'total' => '300.00'], $resumen->porMedio['efectivo']);
        $this->assertSame(['cantidad' => 0, 'total' => '0.00'], $resumen->porMedio['qr']);
    }

    public function test_csv_export_requires_permission_has_bom_and_respects_row_limit(): void
    {
        $escenario = $this->escenarioPaquete('recepcion');
        $this->usarEntidadesFake();
        $paquete = $this->paqueteFila($escenario);
        $this->registrar($paquete, [$this->efectivo('100')])->assertCreated();

        $this->usuarioAdministracion($escenario);

        $contenido = $this->get('/api/pagos/exportar')
            ->assertOk()
            ->assertHeader('Content-Type', 'text/csv; charset=UTF-8')
            ->streamedContent();

        $this->assertStringStartsWith("\xEF\xBB\xBF", $contenido);
        $this->assertStringContainsString('TC-000001', $contenido);
        $this->assertStringContainsString(';', $contenido);

        config(['pagos.export_max_rows' => 0]);

        $this->getJson('/api/pagos/exportar')
            ->assertStatus(422)
            ->assertJsonPath('code', 'EXPORT_TOO_LARGE');
    }

    public function test_medium_catalog_only_exposes_active_media(): void
    {
        $this->escenarioPaquete('recepcion');

        $codigos = collect($this->getJson('/api/pagos/opciones')
            ->assertOk()
            ->json('data.medios'))->pluck('codigo')->all();

        $this->assertSame(['efectivo', 'qr'], $codigos);

        $tipos = collect($this->getJson('/api/pagos/opciones')->json('data.tipos_entidad'));
        $this->assertFalse($tipos->firstWhere('codigo', TipoEntidadPago::VENTA_MATERIAL->value)['disponible']);
    }

    public function test_entity_search_is_503_until_a_payable_module_is_connected(): void
    {
        $this->escenarioPaquete('recepcion');

        $this->getJson('/api/pagos/buscar-entidades?q=Ana')
            ->assertStatus(503)
            ->assertJsonPath('code', 'MODULO_NO_DISPONIBLE');
    }

    public function test_entity_search_is_limited_to_accessible_branches(): void
    {
        $escenario = $this->escenarioPaquete('recepcion');
        $this->usarEntidadesFake();
        $this->paqueteFila($escenario);

        $nombre = $escenario['paciente']->nombre;

        $this->getJson('/api/pagos/buscar-entidades?q='.urlencode($nombre))
            ->assertOk()
            ->assertJsonPath('data.0.tipo', 'paquete_sesiones')
            ->assertJsonPath('data.0.monto_esperado', '500.00');

        $otra = $this->branch();
        $ajenoPaciente = $this->paciente($otra, ['nombre' => 'Zulema']);
        $ajenoTutor = $this->tutor($otra);
        $this->asociarTutor($ajenoPaciente, $ajenoTutor);
        $this->paqueteFila([
            'paciente' => $ajenoPaciente,
            'tutor' => $ajenoTutor,
            'branch' => $otra,
            'servicio' => $escenario['servicio'],
            'tarifa' => $escenario['tarifa'],
            'categoria' => $escenario['categoria'],
        ]);

        $this->getJson('/api/pagos/buscar-entidades?q=Zulema')
            ->assertOk()
            ->assertJsonCount(0, 'data');
    }
}
