<?php

namespace Tests\Feature\Modules\InscripcionesAtenciones;

use App\Models\User;
use App\Modules\CatalogoServicios\Models\Area;
use App\Modules\CatalogoServicios\Models\Servicio;
use App\Modules\CatalogoServicios\Models\TipoServicio;
use App\Modules\InscripcionesAtenciones\Contracts\ConsumoPaqueteContract;
use App\Modules\InscripcionesAtenciones\Contracts\ConsumoPaqueteResumen;
use App\Modules\Profesionales\Models\Profesional;
use App\Modules\Profesionales\Models\ProfesionalSucursal;
use Illuminate\Testing\TestResponse;

abstract class PaquetesSesionesTestCase extends InscripcionesAtencionesTestCase
{
    protected function servicioTerapia(array $overrides = []): Servicio
    {
        $tipo = TipoServicio::query()->where('codigo', 'terapia_individual')->firstOrFail();

        return Servicio::factory()->create(array_merge([
            'area_id' => Area::factory()->create()->id,
            'tipo_servicio_id' => $tipo->id,
            'nombre' => 'Terapia individual de prueba',
            'activo' => true,
        ], $overrides));
    }

    protected function profesionalEnSucursal(int $sucursalId, array $overrides = []): Profesional
    {
        $profesional = Profesional::factory()->create(array_merge([
            'activo' => true,
        ], $overrides));

        ProfesionalSucursal::factory()->create([
            'profesional_id' => $profesional->id,
            'sucursal_id' => $sucursalId,
            'activo' => true,
        ]);

        return $profesional;
    }

    protected function withoutBranch(User $user, $branch): void
    {
        $user->branches()->detach($branch->id);
    }

    /**
     * @return array<string, mixed>
     */
    protected function escenarioPaquete(string $role = 'recepcion'): array
    {
        $branch = $this->branch();
        $user = $this->actingAsRole($role);
        $this->withBranch($user, $branch);

        $paciente = $this->paciente($branch);
        $tutor = $this->tutor($branch);
        $this->asociarTutor($paciente, $tutor);
        $servicio = $this->servicioTerapia();
        $categoria = $this->categoriaTarifa();
        $tarifa = $this->tarifa($servicio, $branch, $categoria, '50.00');
        $profesional = $this->profesionalEnSucursal($branch->id);

        return compact(
            'branch',
            'user',
            'paciente',
            'tutor',
            'servicio',
            'categoria',
            'tarifa',
            'profesional',
        );
    }

    /**
     * @param  array<string, mixed>  $escenario
     * @param  array<string, mixed>  $overrides
     */
    protected function crearPaquete(array $escenario, array $overrides = []): TestResponse
    {
        return $this->postJson(
            '/api/inscripciones-atenciones/paquetes-sesiones',
            $this->payloadPaquete($escenario, $overrides),
        );
    }

    /**
     * @param  array<string, mixed>  $escenario
     * @param  array<string, mixed>  $overrides
     * @return array<string, mixed>
     */
    protected function payloadPaquete(array $escenario, array $overrides = []): array
    {
        return array_merge([
            'paciente_id' => $escenario['paciente']->id,
            'tutor_responsable_id' => $escenario['tutor']->id,
            'sucursal_id' => $escenario['branch']->id,
            'servicio_id' => $escenario['servicio']->id,
            'profesional_id' => $escenario['profesional']->id,
            'categoria_tarifa_id' => $escenario['categoria']->id,
            'cantidad_sesiones' => 10,
            'dias_semana' => [1, 3],
            'hora' => '08:00',
            'duracion_minutos' => 50,
            'fecha_inicio' => '2026-11-26',
            'fecha_fin' => '2026-12-25',
            'observaciones' => null,
        ], $overrides);
    }

    /**
     * @param  array<string, mixed>  $escenario
     */
    protected function fakeConsumo(
        array $escenario,
        int $realizadas,
        int $faltadas,
        int $canceladas = 0,
    ): void {
        $this->app->instance(
            ConsumoPaqueteContract::class,
            new class($realizadas, $faltadas, $canceladas) implements ConsumoPaqueteContract
            {
                public function __construct(
                    private readonly int $realizadas,
                    private readonly int $faltadas,
                    private readonly int $canceladas,
                ) {}

                public function disponible(): bool
                {
                    return true;
                }

                public function resumen(int $paqueteId): ConsumoPaqueteResumen
                {
                    return new ConsumoPaqueteResumen(
                        pactadas: 10,
                        realizadas: $this->realizadas,
                        faltadas: $this->faltadas,
                        canceladas: $this->canceladas,
                    );
                }
            },
        );
    }
}
