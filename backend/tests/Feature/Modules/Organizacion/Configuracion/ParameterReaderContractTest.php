<?php

namespace Tests\Feature\Modules\Organizacion\Configuracion;

use App\Modules\Organizacion\Contracts\ParameterReaderContract;
use App\Modules\Organizacion\Exceptions\ParameterNotFoundException;
use App\Modules\Organizacion\Models\Parametro;
use App\Modules\Organizacion\Support\ParameterCacheKeys;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

final class ParameterReaderContractTest extends ConfiguracionTestCase
{
    public function test_reader_returns_typed_values_and_categories(): void
    {
        $reader = app(ParameterReaderContract::class);

        $duracion = $reader->obtener('duracion_sesion_minutos');

        $this->assertSame('duracion_sesion_minutos', $duracion->clave);
        $this->assertSame(50, $duracion->valor);
        $this->assertSame('minutos', $duracion->unidad);
        $this->assertTrue($duracion->sensible);
        $this->assertTrue($duracion->visibleRecepcion);
        $this->assertNotNull($duracion->actualizadoEn);

        $varios = $reader->obtenerVarios(['moneda', 'zona_horaria']);

        $this->assertSame('BOB', $varios['moneda']->valor);
        $this->assertSame('America/La_Paz', $varios['zona_horaria']->valor);

        $agenda = $reader->porCategoria('agenda');

        $this->assertArrayHasKey('duracion_sesion_minutos', $agenda);
        $this->assertArrayHasKey('agenda.turno_manana.inicio', $agenda);
        $this->assertSame(50, $agenda['duracion_sesion_minutos']->valor);
    }

    public function test_reader_casts_boolean_and_decimal_values(): void
    {
        Parametro::factory()->create([
            'clave' => 'prueba.booleano',
            'categoria' => 'operacion',
            'tipo' => 'booleano',
            'valor' => '1',
            'valor_defecto' => '0',
        ]);
        Parametro::factory()->create([
            'clave' => 'prueba.decimal',
            'categoria' => 'operacion',
            'tipo' => 'decimal',
            'valor' => '10.25',
            'valor_defecto' => '10.25',
        ]);

        $reader = app(ParameterReaderContract::class);

        $this->assertTrue($reader->obtener('prueba.booleano')->valor);
        $this->assertSame('10.25', $reader->obtener('prueba.decimal')->valor);
    }

    public function test_missing_key_throws_controlled_exception_and_logs_alert(): void
    {
        Log::spy();

        $reader = app(ParameterReaderContract::class);

        try {
            $reader->obtener('clave.inexistente');
            $this->fail('Se esperaba ParameterNotFoundException.');
        } catch (ParameterNotFoundException $exception) {
            $this->assertStringContainsString('clave.inexistente', $exception->getMessage());
            $this->assertSame(404, $exception->status());
            $this->assertSame('PARAMETRO_NOT_FOUND', $exception->errorCode());
        }

        Log::shouldHaveReceived('warning')->once();
    }

    public function test_cache_is_invalidated_after_update_and_restore(): void
    {
        $reader = app(ParameterReaderContract::class);
        $parametro = $this->parametro('duracion_sesion_minutos');

        $this->assertSame(50, $reader->obtener('duracion_sesion_minutos')->valor);
        $reader->porCategoria('agenda');

        $this->assertTrue(Cache::has(ParameterCacheKeys::forParameter('duracion_sesion_minutos')));
        $this->assertTrue(Cache::has(ParameterCacheKeys::forCategory('agenda')));

        $this->actingAsSuperadmin();

        $this->patchJson(
            "/api/organizacion/configuracion/parametros/{$parametro->id}",
            $this->payload('duracion_sesion_minutos', 45, [
                'confirmado' => true,
                'motivo' => 'Ajuste de agenda',
            ]),
        )->assertOk();

        $this->assertFalse(Cache::has(ParameterCacheKeys::forParameter('duracion_sesion_minutos')));
        $this->assertFalse(Cache::has(ParameterCacheKeys::forCategory('agenda')));
        $this->assertSame(45, $reader->obtener('duracion_sesion_minutos')->valor);
        $this->assertSame(45, $reader->porCategoria('agenda')['duracion_sesion_minutos']->valor);

        $parametro->refresh();

        $this->postJson(
            "/api/organizacion/configuracion/parametros/{$parametro->id}/restaurar",
            [
                'version' => $parametro->version,
                'confirmado' => true,
                'motivo' => 'Restaurar valor acordado',
            ],
        )->assertOk();

        $this->assertFalse(Cache::has(ParameterCacheKeys::forParameter('duracion_sesion_minutos')));
        $this->assertSame(50, $reader->obtener('duracion_sesion_minutos')->valor);
    }
}
