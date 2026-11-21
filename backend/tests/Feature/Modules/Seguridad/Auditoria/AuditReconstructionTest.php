<?php

namespace Tests\Feature\Modules\Seguridad\Auditoria;

use Laravel\Sanctum\Sanctum;
use Tests\Feature\Modules\Seguridad\SeguridadTestCase;

final class AuditReconstructionTest extends SeguridadTestCase
{
    public function test_reconstructs_entity_state_at_date(): void
    {
        $branch = $this->branch(['codigo' => 'SUC-A']);
        $old = $this->auditRow([
            'usuario_id' => null,
            'sucursal_id' => $branch->id,
            'accion' => 'CREAR',
            'entidad' => 'sala',
            'entidad_id' => 10,
            'datos_nuevos' => ['nombre' => 'Sala Antigua'],
            'modulo' => 'organizacion',
            'resultado' => 'exito',
            'critico' => false,
        ], now()->subDays(2));

        $new = $this->auditRow([
            'usuario_id' => null,
            'sucursal_id' => $branch->id,
            'accion' => 'EDITAR',
            'entidad' => 'sala',
            'entidad_id' => 10,
            'datos_anteriores' => ['nombre' => 'Sala Antigua'],
            'datos_nuevos' => ['nombre' => 'Sala Nueva'],
            'modulo' => 'organizacion',
            'resultado' => 'exito',
            'critico' => false,
        ], now());

        Sanctum::actingAs($this->superadmin());

        $twoDaysAgo = now()->subDays(2)->format('Y-m-d');
        $today = now()->format('Y-m-d');

        $this->getJson("/api/seguridad/bitacora/reconstruir?entidad=sala&entidad_id=10&fecha={$twoDaysAgo}")
            ->assertOk()
            ->assertJsonPath('data.encontrado', true)
            ->assertJsonPath('data.estado.nombre', 'Sala Antigua')
            ->assertJsonPath('data.accion', 'CREAR');

        $this->getJson("/api/seguridad/bitacora/reconstruir?entidad=sala&entidad_id=10&fecha={$today}")
            ->assertOk()
            ->assertJsonPath('data.encontrado', true)
            ->assertJsonPath('data.estado.nombre', 'Sala Nueva')
            ->assertJsonPath('data.accion', 'EDITAR');

        $this->getJson('/api/seguridad/bitacora/reconstruir?entidad=sala&entidad_id=999999&fecha='.$today)
            ->assertOk()
            ->assertJsonPath('data.encontrado', false);
    }

    public function test_reconstruction_is_scoped_by_branch(): void
    {
        $branchA = $this->branch(['codigo' => 'SUC-A']);
        $branchB = $this->branch(['codigo' => 'SUC-B']);
        $adminA = $this->managedUser('administracion', $branchA);
        $adminB = $this->managedUser('administracion', $branchB);

        $this->auditRow([
            'usuario_id' => $adminA->id,
            'sucursal_id' => $branchA->id,
            'accion' => 'CREAR',
            'entidad' => 'sala',
            'entidad_id' => 20,
            'datos_nuevos' => ['nombre' => 'Sala A'],
            'modulo' => 'organizacion',
            'resultado' => 'exito',
            'critico' => false,
        ], now());

        $today = now()->format('Y-m-d');

        Sanctum::actingAs($adminA);
        $this->getJson("/api/seguridad/bitacora/reconstruir?entidad=sala&entidad_id=20&fecha={$today}")
            ->assertOk()
            ->assertJsonPath('data.estado.nombre', 'Sala A');

        Sanctum::actingAs($adminB);
        $this->getJson("/api/seguridad/bitacora/reconstruir?entidad=sala&entidad_id=20&fecha={$today}")
            ->assertOk()
            ->assertJsonPath('data.encontrado', false);
    }

    public function test_failed_event_is_not_used_as_snapshot(): void
    {
        $branch = $this->branch(['codigo' => 'SUC-A']);

        $this->auditRow([
            'usuario_id' => null,
            'sucursal_id' => $branch->id,
            'accion' => 'CREAR',
            'entidad' => 'sala',
            'entidad_id' => 30,
            'datos_nuevos' => ['nombre' => 'Sala Válida'],
            'modulo' => 'organizacion',
            'resultado' => 'exito',
            'critico' => false,
        ], now()->subDay());

        $this->auditRow([
            'usuario_id' => null,
            'sucursal_id' => $branch->id,
            'accion' => 'EDITAR',
            'entidad' => 'sala',
            'entidad_id' => 30,
            'datos_anteriores' => ['nombre' => 'Sala Válida'],
            'datos_nuevos' => null,
            'modulo' => 'organizacion',
            'resultado' => 'fallo',
            'critico' => false,
        ], now());

        Sanctum::actingAs($this->superadmin());

        $this->getJson('/api/seguridad/bitacora/reconstruir?entidad=sala&entidad_id=30&fecha='.now()->format('Y-m-d'))
            ->assertOk()
            ->assertJsonPath('data.encontrado', true)
            ->assertJsonPath('data.accion', 'CREAR')
            ->assertJsonPath('data.estado.nombre', 'Sala Válida');
    }

    public function test_non_snapshot_event_is_ignored(): void
    {
        $branch = $this->branch(['codigo' => 'SUC-A']);

        $this->auditRow([
            'usuario_id' => null,
            'sucursal_id' => $branch->id,
            'accion' => 'CREAR',
            'entidad' => 'usuario',
            'entidad_id' => 40,
            'datos_nuevos' => ['nombre_completo' => 'Usuario Válido'],
            'modulo' => 'usuarios',
            'resultado' => 'exito',
            'critico' => false,
        ], now()->subDay());

        $this->auditRow([
            'usuario_id' => null,
            'sucursal_id' => $branch->id,
            'accion' => 'CAMBIAR_PASSWORD',
            'entidad' => 'usuario',
            'entidad_id' => 40,
            'datos_nuevos' => ['password_actualizada_en' => now()->toIso8601String()],
            'modulo' => 'autenticacion',
            'resultado' => 'exito',
            'critico' => false,
        ], now());

        Sanctum::actingAs($this->superadmin());

        $this->getJson('/api/seguridad/bitacora/reconstruir?entidad=usuario&entidad_id=40&fecha='.now()->format('Y-m-d'))
            ->assertOk()
            ->assertJsonPath('data.encontrado', true)
            ->assertJsonPath('data.accion', 'CREAR')
            ->assertJsonPath('data.estado.nombre_completo', 'Usuario Válido');
    }

    public function test_reconstruction_validates_input(): void
    {
        Sanctum::actingAs($this->superadmin());

        $this->getJson('/api/seguridad/bitacora/reconstruir?entidad=sala&entidad_id=0&fecha=20-01-2026')
            ->assertStatus(422)
            ->assertJsonValidationErrors(['entidad_id', 'fecha']);
    }
}
