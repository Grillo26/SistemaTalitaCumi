<?php

namespace Tests\Feature\Modules\AgendaTurnos;

use App\Models\User;
use App\Modules\CatalogoServicios\Models\Area;
use App\Modules\CatalogoServicios\Models\CategoriaTarifa;
use App\Modules\CatalogoServicios\Models\Servicio;
use App\Modules\CatalogoServicios\Models\Tarifa;
use App\Modules\CatalogoServicios\Models\TipoServicio;
use App\Modules\InscripcionesAtenciones\Models\InscripcionSala;
use App\Modules\InscripcionesAtenciones\Models\PaqueteSesiones;
use App\Modules\Organizacion\Models\Branch;
use App\Modules\Organizacion\Models\Sala;
use App\Modules\Pacientes\Models\Paciente;
use App\Modules\Pacientes\Models\PacienteTutor;
use App\Modules\Pacientes\Models\Tutor;
use App\Modules\Profesionales\Models\Profesional;
use App\Modules\Profesionales\Models\ProfesionalSucursal;
use Carbon\Carbon;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Laravel\Sanctum\Sanctum;
use Spatie\Permission\PermissionRegistrar;
use Tests\TestCase;

abstract class ProgramacionTurnosTestCase extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Carbon::setTestNow(Carbon::parse('2025-11-30 10:00:00', 'America/La_Paz'));
        CarbonImmutable::setTestNow(
            CarbonImmutable::parse('2025-11-30 10:00:00', 'America/La_Paz'),
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

    protected function branch(array $overrides = []): Branch
    {
        return Branch::factory()->create($overrides);
    }

    protected function paciente(Branch $branch): Paciente
    {
        return Paciente::factory()->create([
            'sucursal_registro_id' => $branch->id,
            'estado' => 'activo',
        ]);
    }

    protected function tutor(Branch $branch): Tutor
    {
        return Tutor::factory()->create([
            'sucursal_registro_id' => $branch->id,
            'activo' => true,
        ]);
    }

    protected function asociarTutor(Paciente $paciente, Tutor $tutor): void
    {
        PacienteTutor::query()->create([
            'paciente_id' => $paciente->id,
            'tutor_id' => $tutor->id,
            'parentesco_id' => null,
            'es_principal' => true,
        ]);
    }

    protected function servicio(string $tipo = 'terapia_individual'): Servicio
    {
        $tipoServicio = TipoServicio::query()->where('codigo', $tipo)->firstOrFail();

        return Servicio::factory()->create([
            'area_id' => Area::factory()->create()->id,
            'tipo_servicio_id' => $tipoServicio->id,
            'nombre' => 'Servicio de programación '.fake()->unique()->word(),
            'duracion_minutos' => 50,
            'activo' => true,
        ]);
    }

    protected function categoriaTarifa(): CategoriaTarifa
    {
        return CategoriaTarifa::factory()->create();
    }

    protected function tarifa(Servicio $servicio, Branch $branch, CategoriaTarifa $categoria): Tarifa
    {
        return Tarifa::factory()->create([
            'servicio_id' => $servicio->id,
            'categoria_tarifa_id' => $categoria->id,
            'sucursal_id' => $branch->id,
            'monto' => '500.00',
            'vigente_desde' => '2025-01-01',
            'vigente_hasta' => null,
            'activo' => true,
        ]);
    }

    protected function profesional(Branch $branch): Profesional
    {
        $profesional = Profesional::factory()->create([
            'nombre_completo' => 'Profesional de programación',
            'activo' => true,
        ]);

        ProfesionalSucursal::factory()->create([
            'profesional_id' => $profesional->id,
            'sucursal_id' => $branch->id,
            'activo' => true,
        ]);

        return $profesional;
    }

    protected function sala(Branch $branch): Sala
    {
        return Sala::factory()->create([
            'sucursal_id' => $branch->id,
            'nombre' => 'Sala de programación',
            'activo' => true,
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    protected function escenario(): array
    {
        $branch = $this->branch();
        $user = $this->actingAsWithPermissions([
            'turno.ver',
            'turno.generar',
            'turno.regenerar',
            'turno.asignar_profesional',
            'turno.feriados',
            'inscripcion.ver',
            'paquete.ver',
            'paquete.crear',
        ], [$branch->id]);

        $paciente = $this->paciente($branch);
        $tutor = $this->tutor($branch);
        $this->asociarTutor($paciente, $tutor);
        $servicio = $this->servicio();
        $categoria = $this->categoriaTarifa();
        $tarifa = $this->tarifa($servicio, $branch, $categoria);
        $profesional = $this->profesional($branch);
        $sala = $this->sala($branch);

        return compact(
            'branch',
            'user',
            'paciente',
            'tutor',
            'servicio',
            'categoria',
            'tarifa',
            'profesional',
            'sala',
        );
    }

    /**
     * @param  array<string, mixed>  $escenario
     * @param  array<string, mixed>  $overrides
     */
    protected function crearPaquete(array $escenario, array $overrides = []): PaqueteSesiones
    {
        return PaqueteSesiones::query()->create(array_merge([
            'paciente_id' => $escenario['paciente']->id,
            'tutor_responsable_id' => $escenario['tutor']->id,
            'sucursal_id' => $escenario['branch']->id,
            'servicio_id' => $escenario['servicio']->id,
            'profesional_id' => null,
            'tarifa_id' => $escenario['tarifa']->id,
            'categoria_tarifa_id' => $escenario['categoria']->id,
            'cantidad_sesiones' => 10,
            'dias_semana' => [2, 4],
            'hora' => '09:00',
            'duracion_minutos' => 50,
            'fecha_inicio' => '2025-12-01',
            'fecha_fin' => '2025-12-31',
            'monto_esperado' => '500.00',
            'estado' => 'activo',
            'observaciones' => null,
        ], $overrides));
    }

    /**
     * @param  array<string, mixed>  $escenario
     * @param  array<string, mixed>  $overrides
     */
    protected function crearInscripcionSala(array $escenario, array $overrides = []): InscripcionSala
    {
        return InscripcionSala::query()->create(array_merge([
            'paciente_id' => $escenario['paciente']->id,
            'tutor_responsable_id' => $escenario['tutor']->id,
            'sala_id' => $escenario['sala']->id,
            'sucursal_id' => $escenario['branch']->id,
            'servicio_id' => $escenario['servicio']->id,
            'tarifa_id' => $escenario['tarifa']->id,
            'categoria_tarifa_id' => $escenario['categoria']->id,
            'fecha_inicio' => '2025-12-01',
            'fecha_fin' => '2025-12-31',
            'monto_esperado' => '500.00',
            'estado' => 'activa',
            'observaciones' => null,
        ], $overrides));
    }

    protected function url(string $tipo, int $id, string $sufijo = ''): string
    {
        return "/api/agenda-turnos/programacion-turnos/origenes/{$tipo}/{$id}{$sufijo}";
    }
}
