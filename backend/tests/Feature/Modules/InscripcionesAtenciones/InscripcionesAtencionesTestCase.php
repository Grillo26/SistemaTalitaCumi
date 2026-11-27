<?php

namespace Tests\Feature\Modules\InscripcionesAtenciones;

use App\Models\User;
use App\Modules\CatalogoServicios\Models\Area as CatalogoArea;
use App\Modules\CatalogoServicios\Models\CategoriaTarifa;
use App\Modules\CatalogoServicios\Models\Servicio;
use App\Modules\CatalogoServicios\Models\Tarifa;
use App\Modules\CatalogoServicios\Models\TipoServicio;
use App\Modules\Organizacion\Models\Branch;
use App\Modules\Organizacion\Models\Sala;
use App\Modules\Pacientes\Models\Paciente;
use App\Modules\Pacientes\Models\PacienteTutor;
use App\Modules\Pacientes\Models\Tutor;
use Carbon\Carbon;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Testing\TestResponse;
use Laravel\Sanctum\Sanctum;
use Spatie\Permission\PermissionRegistrar;
use Tests\TestCase;

abstract class InscripcionesAtencionesTestCase extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Carbon::setTestNow(Carbon::parse('2026-11-25 10:00:00'));
        CarbonImmutable::setTestNow(CarbonImmutable::parse('2026-11-25 10:00:00'));
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

    /**
     * @param  list<string>  $permissions
     */
    protected function actingAsWithPermissions(array $permissions, Branch $branch): User
    {
        $user = User::factory()->create();
        $user->givePermissionTo($permissions);
        $this->withBranch($user, $branch);
        Sanctum::actingAs($user);

        return $user;
    }

    protected function branch(array $overrides = []): Branch
    {
        return Branch::factory()->create($overrides);
    }

    protected function paciente(Branch $branch, array $overrides = []): Paciente
    {
        return Paciente::factory()->create(array_merge([
            'sucursal_registro_id' => $branch->id,
            'estado' => 'activo',
        ], $overrides));
    }

    protected function tutor(Branch $branch, array $overrides = []): Tutor
    {
        return Tutor::factory()->create(array_merge([
            'sucursal_registro_id' => $branch->id,
            'activo' => true,
        ], $overrides));
    }

    protected function asociarTutor(Paciente $paciente, Tutor $tutor): PacienteTutor
    {
        return PacienteTutor::query()->create([
            'paciente_id' => $paciente->id,
            'tutor_id' => $tutor->id,
            'parentesco_id' => null,
            'es_principal' => true,
        ]);
    }

    protected function sala(Branch $branch, array $overrides = []): Sala
    {
        return Sala::factory()->create(array_merge([
            'sucursal_id' => $branch->id,
            'activo' => true,
        ], $overrides));
    }

    protected function servicioSalaGrupal(array $overrides = []): Servicio
    {
        $tipo = TipoServicio::query()->where('codigo', 'sala_grupal')->firstOrFail();

        return Servicio::factory()->create(array_merge([
            'area_id' => CatalogoArea::factory()->create()->id,
            'tipo_servicio_id' => $tipo->id,
            'nombre' => 'Sala grupal de prueba',
            'activo' => true,
        ], $overrides));
    }

    protected function categoriaTarifa(array $overrides = []): CategoriaTarifa
    {
        return CategoriaTarifa::factory()->create($overrides);
    }

    protected function tarifa(
        Servicio $servicio,
        Branch $branch,
        CategoriaTarifa $categoria,
        string $monto = '500.00',
        ?string $desde = null,
    ): Tarifa {
        return Tarifa::factory()->create([
            'servicio_id' => $servicio->id,
            'categoria_tarifa_id' => $categoria->id,
            'sucursal_id' => $branch->id,
            'monto' => $monto,
            'vigente_desde' => $desde ?? '2026-01-01',
            'vigente_hasta' => null,
            'activo' => true,
        ]);
    }

    protected function withBranch(User $user, Branch $branch): void
    {
        $user->branches()->syncWithoutDetaching([$branch->id]);
    }

    /**
     * @return array<string, mixed>
     */
    protected function scenario(string $role = 'recepcion'): array
    {
        $branch = $this->branch();
        $user = $this->actingAsRole($role);
        $this->withBranch($user, $branch);

        $paciente = $this->paciente($branch);
        $tutor = $this->tutor($branch);
        $this->asociarTutor($paciente, $tutor);
        $sala = $this->sala($branch);
        $servicio = $this->servicioSalaGrupal();
        $categoria = $this->categoriaTarifa();
        $tarifa = $this->tarifa($servicio, $branch, $categoria, '500.00');

        return compact(
            'branch',
            'user',
            'paciente',
            'tutor',
            'sala',
            'servicio',
            'categoria',
            'tarifa',
        );
    }

    /**
     * @param  array<string, mixed>  $scenario
     * @param  array<string, mixed>  $overrides
     */
    protected function crearInscripcion(array $scenario, array $overrides = []): TestResponse
    {
        return $this->postJson(
            '/api/inscripciones-atenciones/salas-grupales',
            $this->payload(
                $scenario['paciente'],
                $scenario['tutor'],
                $scenario['branch'],
                $scenario['sala'],
                $scenario['servicio'],
                $scenario['categoria'],
                $overrides,
            ),
        );
    }

    /**
     * @return array<string, mixed>
     */
    protected function payload(
        Paciente $paciente,
        Tutor $tutor,
        Branch $branch,
        Sala $sala,
        Servicio $servicio,
        CategoriaTarifa $categoria,
        array $overrides = [],
    ): array {
        return array_merge([
            'paciente_id' => $paciente->id,
            'tutor_responsable_id' => $tutor->id,
            'sucursal_id' => $branch->id,
            'sala_id' => $sala->id,
            'servicio_id' => $servicio->id,
            'categoria_tarifa_id' => $categoria->id,
            'fecha_inicio' => '2026-11-01',
            'fecha_fin' => '2026-11-30',
            'observaciones' => null,
        ], $overrides);
    }
}
