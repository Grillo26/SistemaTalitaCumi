<?php

namespace Tests\Feature\Modules\AgendaTurnos;

use App\Models\User;
use App\Modules\AgendaTurnos\Enums\EstadoTurno;
use App\Modules\AgendaTurnos\Models\Turno;
use App\Modules\CatalogoServicios\Models\Area;
use App\Modules\CatalogoServicios\Models\Servicio;
use App\Modules\Organizacion\Models\Branch;
use App\Modules\Organizacion\Models\Sala;
use App\Modules\Pacientes\Models\Paciente;
use App\Modules\Profesionales\Models\Profesional;
use App\Modules\Profesionales\Models\ProfesionalSucursal;
use Carbon\Carbon;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Laravel\Sanctum\Sanctum;
use Spatie\Permission\PermissionRegistrar;
use Tests\TestCase;

abstract class AgendaSemanalTestCase extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Carbon::setTestNow(Carbon::parse('2026-11-26 10:00:00', 'America/La_Paz'));
        CarbonImmutable::setTestNow(
            CarbonImmutable::parse('2026-11-26 10:00:00', 'America/La_Paz'),
        );

        $this->seed();
        Cache::flush();
        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();
        CarbonImmutable::setTestNow();

        parent::tearDown();
    }

    /**
     * @param  list<string>  $permissions
     * @param  list<int>  $branchIds
     */
    protected function actingAsWithPermissions(array $permissions, array $branchIds): User
    {
        $user = User::factory()->create();
        $user->givePermissionTo($permissions);
        $user->branches()->sync($branchIds);
        Sanctum::actingAs($user);

        return $user;
    }

    protected function actingAsRole(string $role, array $branchIds = []): User
    {
        $user = User::factory()->create();
        $user->assignRole($role);
        $user->branches()->sync($branchIds);
        Sanctum::actingAs($user);

        return $user;
    }

    protected function branch(array $overrides = []): Branch
    {
        return Branch::factory()->create($overrides);
    }

    protected function area(string $color = '#F9733F'): Area
    {
        return Area::factory()->create([
            'nombre' => 'Área '.fake()->unique()->word(),
            'color' => $color,
            'activo' => true,
        ]);
    }

    protected function servicio(Area $area, string $nombre = 'Servicio de agenda'): Servicio
    {
        return Servicio::factory()->create([
            'area_id' => $area->id,
            'nombre' => $nombre,
            'activo' => true,
        ]);
    }

    protected function sala(Branch $branch, Area $area, string $nombre = 'Sala de agenda'): Sala
    {
        return Sala::factory()->create([
            'sucursal_id' => $branch->id,
            'area_id' => $area->id,
            'nombre' => $nombre,
            'activo' => true,
        ]);
    }

    protected function paciente(Branch $branch): Paciente
    {
        return Paciente::factory()->create([
            'sucursal_registro_id' => $branch->id,
            'estado' => 'activo',
        ]);
    }

    protected function profesional(Branch $branch, string $nombre = 'Profesional de agenda'): Profesional
    {
        $profesional = Profesional::factory()->create([
            'nombre_completo' => $nombre,
            'activo' => true,
        ]);

        ProfesionalSucursal::factory()->create([
            'profesional_id' => $profesional->id,
            'sucursal_id' => $branch->id,
            'activo' => true,
        ]);

        return $profesional;
    }

    protected function turno(
        Branch $branch,
        Sala $sala,
        Paciente $paciente,
        Servicio $servicio,
        ?Profesional $profesional = null,
        string $fecha = '2026-11-24',
        string $inicio = '08:00',
        string $fin = '08:50',
        string $estado = 'programado',
    ): Turno {
        $factory = Turno::factory()
            ->paraSala($sala)
            ->paraPaciente($paciente->id)
            ->paraServicio($servicio->id)
            ->paraFecha($fecha)
            ->paraHorario($inicio, $fin)
            ->estado(EstadoTurno::from($estado));

        if ($profesional !== null) {
            $factory = $factory->conProfesional($profesional->id);
        }

        return $factory->create([
            'sucursal_id' => $branch->id,
        ]);
    }

    /**
     * @param  array<string, mixed>  $params
     */
    protected function weekUrl(int $branchId, array $params = []): string
    {
        return '/api/agenda-turnos/agenda-semanal?'.http_build_query([
            'sucursal_id' => $branchId,
            'semana' => '2026-11-23',
            ...$params,
        ]);
    }

    protected function optionsUrl(int $branchId): string
    {
        return '/api/agenda-turnos/agenda-semanal/opciones?'.http_build_query([
            'sucursal_id' => $branchId,
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    protected function scenario(string $role = 'recepcion'): array
    {
        $branch = $this->branch();
        $user = $this->actingAsWithPermissions(
            ['turno.ver', 'turno.exportar'],
            [$branch->id],
        );
        $area = $this->area();
        $servicio = $this->servicio($area);
        $sala = $this->sala($branch, $area);
        $paciente = $this->paciente($branch);
        $profesional = $this->profesional($branch);

        return compact(
            'branch',
            'user',
            'area',
            'servicio',
            'sala',
            'paciente',
            'profesional',
        );
    }
}
