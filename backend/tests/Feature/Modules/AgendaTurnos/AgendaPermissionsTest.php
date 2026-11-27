<?php

namespace Tests\Feature\Modules\AgendaTurnos;

use App\Models\User;
use Laravel\Sanctum\Sanctum;

final class AgendaPermissionsTest extends AgendaSemanalTestCase
{
    public function test_reception_can_view_but_not_export(): void
    {
        $branch = $this->branch();
        $this->actingAsRole('recepcion', [$branch->id]);

        $this->getJson($this->weekUrl($branch->id))->assertOk();

        $this->getJson('/api/agenda-turnos/agenda-semanal/exportar?'.http_build_query([
            'sucursal_id' => $branch->id,
            'semana' => '2026-11-23',
        ]))->assertStatus(403);
    }

    public function test_administration_can_view_and_has_export_permission(): void
    {
        $branch = $this->branch();
        $this->actingAsRole('administracion', [$branch->id]);

        $this->getJson($this->weekUrl($branch->id))->assertOk();

        $this->getJson('/api/agenda-turnos/agenda-semanal/exportar?'.http_build_query([
            'sucursal_id' => $branch->id,
            'semana' => '2026-11-23',
        ]))
            ->assertStatus(503)
            ->assertJsonPath('code', 'EXPORTACION_NO_DISPONIBLE');
    }

    public function test_route_requires_the_view_permission(): void
    {
        $branch = $this->branch();
        $user = User::factory()->create();
        $user->branches()->sync([$branch->id]);
        Sanctum::actingAs($user);

        $this->getJson($this->weekUrl($branch->id))->assertStatus(403);
    }

    public function test_route_requires_authentication(): void
    {
        $this->getJson('/api/agenda-turnos/agenda-semanal')->assertStatus(401);
    }
}
