<?php

namespace Tests\Feature\Modules\CatalogoServicios;

use App\Models\User;
use App\Modules\CatalogoServicios\Models\Area;
use App\Modules\CatalogoServicios\Models\Servicio;
use App\Modules\CatalogoServicios\Models\TipoServicio;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Spatie\Permission\PermissionRegistrar;
use Tests\TestCase;

abstract class CatalogoServiciosTestCase extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed();
        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }

    protected function userWithRole(string $role): User
    {
        $user = User::factory()->create();
        $user->assignRole($role);

        return $user;
    }

    protected function actingAsRole(string $role): User
    {
        $user = $this->userWithRole($role);
        Sanctum::actingAs($user);

        return $user;
    }

    protected function actingAsSuperadmin(): User
    {
        return $this->actingAsRole('superadmin');
    }

    protected function area(string $name = 'Área de Prueba'): Area
    {
        return Area::factory()->create([
            'nombre' => $name,
            'color' => '#671C61',
            'activo' => true,
            'version' => 1,
        ]);
    }

    protected function tipoServicio(string $name = 'Tipo de prueba'): TipoServicio
    {
        return TipoServicio::factory()->create([
            'nombre' => $name,
            'activo' => true,
            'version' => 1,
        ]);
    }

    protected function servicio(Area $area, TipoServicio $tipo, string $name = 'Terapia de lenguaje'): Servicio
    {
        return Servicio::factory()->create([
            'area_id' => $area->id,
            'tipo_servicio_id' => $tipo->id,
            'nombre' => $name,
            'activo' => true,
            'version' => 1,
        ]);
    }

    /**
     * @param  array<string, mixed>  $overrides
     * @return array<string, mixed>
     */
    protected function areaPayload(array $overrides = []): array
    {
        return array_merge([
            'nombre' => 'Área Nueva',
            'descripcion' => 'Descripción del área',
            'color' => '#671c61',
            'activo' => true,
        ], $overrides);
    }

    /**
     * @param  array<string, mixed>  $overrides
     * @return array<string, mixed>
     */
    protected function servicioPayload(Area $area, TipoServicio $tipo, array $overrides = []): array
    {
        return array_merge([
            'area_id' => $area->id,
            'tipo_servicio_id' => $tipo->id,
            'nombre' => 'Servicio Nuevo',
            'descripcion' => 'Descripción del servicio',
            'duracion_minutos' => 50,
            'activo' => true,
        ], $overrides);
    }
}
