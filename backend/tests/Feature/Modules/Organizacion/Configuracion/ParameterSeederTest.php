<?php

namespace Tests\Feature\Modules\Organizacion\Configuracion;

use App\Modules\Organizacion\Models\BitacoraEntry;
use App\Modules\Organizacion\Models\Parametro;
use Database\Seeders\OrganizacionConfiguracionCatalogSeeder;

final class ParameterSeederTest extends ConfiguracionTestCase
{
    public function test_catalog_is_seeded_with_initial_audit_entries(): void
    {
        $this->assertSame(36, Parametro::query()->count());
        $this->assertSame(36, BitacoraEntry::query()
            ->where('entidad', 'parametro')
            ->where('accion', 'CREAR')
            ->count());

        $this->assertSame(0, BitacoraEntry::query()
            ->where('entidad', 'parametro')
            ->whereNotNull('sucursal_id')
            ->count());
    }

    public function test_catalog_contains_expected_defaults_and_flags(): void
    {
        $duracion = $this->parametro('duracion_sesion_minutos');
        $this->assertSame('50', $duracion->valor);
        $this->assertSame('30', $duracion->minimo);
        $this->assertSame('90', $duracion->maximo);
        $this->assertTrue($duracion->sensible);
        $this->assertTrue($duracion->visible_recepcion);

        $moneda = $this->parametro('moneda');
        $this->assertSame('BOB', $moneda->valor);
        $this->assertFalse($moneda->editable);

        $leyenda = $this->parametro('comprobante.leyenda_no_factura');
        $this->assertSame('Este documento NO es factura', $leyenda->valor);
        $this->assertFalse($leyenda->editable);

        $zona = $this->parametro('zona_horaria');
        $this->assertSame('America/La_Paz', $zona->valor);
        $this->assertTrue($zona->sensible);
        $this->assertFalse($zona->visible_recepcion);

        $this->assertSame('08:00', $this->parametro('agenda.turno_manana.inicio')->valor);
        $this->assertSame('12:00', $this->parametro('agenda.turno_manana.fin')->valor);
        $this->assertSame('14:00', $this->parametro('agenda.turno_tarde.inicio')->valor);
        $this->assertSame('18:00', $this->parametro('agenda.turno_tarde.fin')->valor);

        $this->assertSame('10', $this->parametro('password_longitud_minima')->valor);
        $this->assertSame('5', $this->parametro('login_intentos_maximos')->valor);
        $this->assertSame('15', $this->parametro('sesion_inactividad_minutos')->valor);
        $this->assertSame('1', $this->parametro('sesion_maximas_por_usuario')->valor);
        $this->assertSame('5', $this->parametro('bitacora_retencion_anios')->valor);
    }

    public function test_seeder_is_idempotent_and_does_not_overwrite_current_values(): void
    {
        $parametro = $this->parametro('duracion_sesion_minutos');
        $parametro->update(['valor' => '45']);

        $this->seed(OrganizacionConfiguracionCatalogSeeder::class);

        $this->assertSame(36, Parametro::query()->count());
        $this->assertSame(36, BitacoraEntry::query()
            ->where('entidad', 'parametro')
            ->where('accion', 'CREAR')
            ->count());
        $this->assertSame('45', $this->parametro('duracion_sesion_minutos')->valor);
    }
}
