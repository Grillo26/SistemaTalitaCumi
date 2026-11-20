<?php

namespace Tests\Feature\Modules\Organizacion\Configuracion;

use App\Modules\Organizacion\Models\Parametro;

final class ParameterListTest extends ConfiguracionTestCase
{
    public function test_superadmin_sees_all_parameters_and_can_edit(): void
    {
        $this->actingAsSuperadmin();

        $response = $this->getJson('/api/organizacion/configuracion/parametros')
            ->assertOk()
            ->assertJsonPath('can.view_all', true)
            ->assertJsonPath('can.update', true)
            ->assertJsonPath('can.restore', true)
            ->assertJsonPath('can.export', false);

        $this->assertCount(Parametro::query()->count(), $response->json('data'));
        $this->assertContains('nombre_centro', collect($response->json('data'))->pluck('clave')->all());
    }

    public function test_administracion_sees_all_parameters_but_cannot_edit(): void
    {
        $this->actingAsRole('administracion');

        $response = $this->getJson('/api/organizacion/configuracion/parametros')
            ->assertOk()
            ->assertJsonPath('can.view_all', true)
            ->assertJsonPath('can.update', false)
            ->assertJsonPath('can.restore', false)
            ->assertJsonPath('can.export', false);

        $this->assertCount(Parametro::query()->count(), $response->json('data'));
    }

    public function test_recepcion_only_sees_operational_visible_parameters(): void
    {
        $this->actingAsRole('recepcion');

        $response = $this->getJson('/api/organizacion/configuracion/parametros')
            ->assertOk()
            ->assertJsonPath('can.view_all', false)
            ->assertJsonPath('can.view_operatives', true)
            ->assertJsonPath('can.update', false);

        $claves = collect($response->json('data'))->pluck('clave')->all();

        $this->assertContains('duracion_sesion_minutos', $claves);
        $this->assertContains('moneda', $claves);
        $this->assertNotContains('zona_horaria', $claves);
        $this->assertNotContains('comprobante.formato', $claves);
        $this->assertNotContains('nombre_centro', $claves);
    }

    public function test_search_and_category_filter_are_applied_in_backend(): void
    {
        $this->actingAsSuperadmin();

        $search = $this->getJson('/api/organizacion/configuracion/parametros?search=sesion')
            ->assertOk()
            ->json('data');

        $this->assertNotEmpty($search);
        $this->assertTrue(collect($search)->every(
            fn (array $item): bool => str_contains(mb_strtolower($item['clave'].' '.$item['descripcion']), 'sesion'),
        ));

        $categoria = $this->getJson('/api/organizacion/configuracion/parametros?categoria=agenda')
            ->assertOk()
            ->json('data');

        $this->assertNotEmpty($categoria);
        $this->assertTrue(collect($categoria)->every(
            fn (array $item): bool => $item['categoria'] === 'agenda',
        ));
    }

    public function test_show_hidden_parameter_is_forbidden_for_recepcion(): void
    {
        $this->actingAsRole('recepcion');

        $this->getJson('/api/organizacion/configuracion/parametros/'.$this->parametro('zona_horaria')->id)
            ->assertForbidden()
            ->assertJsonPath('code', 'PARAMETRO_ACCESS_DENIED');

        $this->getJson('/api/organizacion/configuracion/parametros/'.$this->parametro('duracion_sesion_minutos')->id)
            ->assertOk()
            ->assertJsonPath('data.clave', 'duracion_sesion_minutos');
    }

    public function test_missing_parameter_returns_not_found(): void
    {
        $this->actingAsSuperadmin();

        $this->getJson('/api/organizacion/configuracion/parametros/999999')
            ->assertNotFound()
            ->assertJsonPath('code', 'PARAMETRO_NOT_FOUND');
    }

    public function test_unauthenticated_request_is_rejected(): void
    {
        $this->getJson('/api/organizacion/configuracion/parametros')
            ->assertUnauthorized()
            ->assertJsonPath('code', 'UNAUTHENTICATED');
    }
}
