<?php

namespace Tests\Feature\Modules\CatalogoServicios\TiposDiagnostico;

use App\Modules\CatalogoServicios\Models\Servicio;
use App\Modules\CatalogoServicios\Models\TipoDiagnostico;
use App\Modules\CatalogoServicios\Models\TipoServicio;
use App\Modules\Profesionales\Models\Especialidad;
use Tests\Feature\Modules\CatalogoServicios\CatalogoServiciosTestCase;

abstract class TiposDiagnosticoTestCase extends CatalogoServiciosTestCase
{
    protected function tipoServicioDiagnostico(): TipoServicio
    {
        return TipoServicio::query()->where('codigo', 'diagnostico_evaluacion')->firstOrFail();
    }

    protected function servicioDiagnostico(string $nombre = 'Evaluación diagnóstica'): Servicio
    {
        return Servicio::factory()->create([
            'tipo_servicio_id' => $this->tipoServicioDiagnostico()->id,
            'nombre' => $nombre,
            'activo' => true,
            'version' => 1,
        ]);
    }

    protected function servicioNoDiagnostico(string $nombre = 'Entrevista inicial'): Servicio
    {
        $tipo = TipoServicio::query()->where('codigo', 'entrevista')->firstOrFail();

        return Servicio::factory()->create([
            'tipo_servicio_id' => $tipo->id,
            'nombre' => $nombre,
            'activo' => true,
            'version' => 1,
        ]);
    }

    protected function especialidad(string $nombre = 'Psicología'): Especialidad
    {
        return Especialidad::query()->firstOrCreate(
            ['nombre' => $nombre],
            [
                'descripcion' => null,
                'activo' => true,
                'version' => 1,
            ],
        );
    }

    /**
     * @param  array<string, mixed>  $overrides
     * @return array<string, mixed>
     */
    protected function tipoPayload(
        Servicio $servicio,
        array $especialidades,
        array $overrides = [],
    ): array {
        return array_merge([
            'servicio_id' => $servicio->id,
            'nombre' => 'Evaluación integral',
            'descripcion' => 'Evaluación de prueba',
            'dias_estimados' => 2,
            'activo' => true,
            'especialidades' => $especialidades,
        ], $overrides);
    }

    /**
     * @param  list<array{especialidad_id: int, cantidad_minima: int}>  $especialidades
     * @param  array<string, mixed>  $overrides
     */
    protected function tipoDiagnostico(
        Servicio $servicio,
        array $especialidades,
        array $overrides = [],
    ): TipoDiagnostico {
        $tipo = TipoDiagnostico::query()->create(array_merge([
            'servicio_id' => $servicio->id,
            'nombre' => 'Tipo de prueba',
            'descripcion' => null,
            'dias_estimados' => 1,
            'activo' => true,
            'version' => 1,
        ], $overrides));

        $sync = [];

        foreach ($especialidades as $especialidad) {
            $sync[$especialidad['especialidad_id']] = [
                'cantidad_minima' => $especialidad['cantidad_minima'],
            ];
        }

        $tipo->especialidades()->sync($sync);

        return $tipo->refresh();
    }
}
