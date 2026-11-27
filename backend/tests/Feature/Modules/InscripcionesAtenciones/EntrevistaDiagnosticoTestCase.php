<?php

namespace Tests\Feature\Modules\InscripcionesAtenciones;

use App\Modules\CatalogoServicios\Models\Servicio;
use App\Modules\CatalogoServicios\Models\TipoDiagnostico;
use App\Modules\CatalogoServicios\Models\TipoServicio;
use App\Modules\Organizacion\Models\Branch;
use App\Modules\Profesionales\Models\Especialidad;
use App\Modules\Profesionales\Models\Profesional;
use App\Modules\Profesionales\Models\ProfesionalEspecialidad;
use App\Modules\Profesionales\Models\ProfesionalSucursal;

abstract class EntrevistaDiagnosticoTestCase extends InscripcionesAtencionesTestCase
{
    protected function servicioEntrevista(array $overrides = []): Servicio
    {
        $tipo = TipoServicio::query()->where('codigo', 'entrevista')->firstOrFail();

        return Servicio::factory()->create(array_merge([
            'tipo_servicio_id' => $tipo->id,
            'nombre' => 'Entrevista familiar',
            'activo' => true,
        ], $overrides));
    }

    protected function servicioDiagnostico(array $overrides = []): Servicio
    {
        $tipo = TipoServicio::query()->where('codigo', 'diagnostico_evaluacion')->firstOrFail();

        return Servicio::factory()->create(array_merge([
            'tipo_servicio_id' => $tipo->id,
            'nombre' => 'Evaluación integral',
            'activo' => true,
        ], $overrides));
    }

    protected function especialidad(string $nombre): Especialidad
    {
        return Especialidad::query()->create([
            'nombre' => $nombre.' '.uniqid(),
            'descripcion' => null,
            'activo' => true,
            'version' => 1,
        ]);
    }

    /**
     * @param  list<array{especialidad_id: int, cantidad_minima: int}>  $requisitos
     */
    protected function tipoDiagnostico(Servicio $servicio, array $requisitos): TipoDiagnostico
    {
        $tipo = TipoDiagnostico::query()->create([
            'servicio_id' => $servicio->id,
            'nombre' => 'Evaluación '.uniqid(),
            'descripcion' => null,
            'dias_estimados' => 2,
            'activo' => true,
            'version' => 1,
        ]);

        foreach ($requisitos as $requisito) {
            $tipo->especialidades()->attach($requisito['especialidad_id'], [
                'cantidad_minima' => $requisito['cantidad_minima'],
            ]);
        }

        return $tipo;
    }

    /**
     * @param  list<int>  $especialidadIds
     */
    protected function profesional(Branch $branch, array $especialidadIds): Profesional
    {
        $profesional = Profesional::query()->create([
            'nombre_completo' => 'Profesional '.uniqid(),
            'ci' => (string) random_int(10000000, 99999999),
            'activo' => true,
            'version' => 1,
        ]);

        foreach ($especialidadIds as $index => $especialidadId) {
            ProfesionalEspecialidad::query()->create([
                'profesional_id' => $profesional->id,
                'especialidad_id' => $especialidadId,
                'tipo' => $index === 0 ? 'principal' : 'secundaria',
                'activo' => true,
            ]);
        }

        ProfesionalSucursal::query()->create([
            'profesional_id' => $profesional->id,
            'sucursal_id' => $branch->id,
            'activo' => true,
            'version' => 1,
        ]);

        return $profesional;
    }

    /**
     * @param  array<string, mixed>  $overrides
     * @return array<string, mixed>
     */
    protected function payloadEntrevista(
        Branch $branch,
        $paciente,
        $tutor,
        Servicio $servicio,
        $categoria,
        array $overrides = [],
    ): array {
        return array_merge([
            'paciente_id' => $paciente->id,
            'tutor_id' => $tutor->id,
            'sucursal_id' => $branch->id,
            'servicio_id' => $servicio->id,
            'categoria_tarifa_id' => $categoria->id,
            'fecha' => '2026-11-25',
            'notas_admin' => null,
        ], $overrides);
    }

    /**
     * @param  array<string, mixed>  $overrides
     * @return array<string, mixed>
     */
    protected function payloadDiagnostico(
        Branch $branch,
        $paciente,
        TipoDiagnostico $tipo,
        $categoria,
        array $overrides = [],
    ): array {
        return array_merge([
            'paciente_id' => $paciente->id,
            'sucursal_id' => $branch->id,
            'tipo_diagnostico_id' => $tipo->id,
            'categoria_tarifa_id' => $categoria->id,
            'fecha_inicio' => '2026-12-01',
            'fecha_fin' => '2026-12-03',
            'profesionales' => [],
            'omitio_entrevista' => true,
            'motivo_omision_entrevista' => null,
            'notas_admin' => null,
        ], $overrides);
    }
}
