<?php

namespace App\Modules\InscripcionesAtenciones\Support;

use App\Modules\CatalogoServicios\Exceptions\ServicioInactivoException;
use App\Modules\CatalogoServicios\Exceptions\TipoDiagnosticoNotFoundException;
use App\Modules\CatalogoServicios\Rules\TipoDiagnosticoServicioRule;
use App\Modules\InscripcionesAtenciones\Exceptions\TipoDiagnosticoInactivoException;
use Illuminate\Support\Facades\DB;

/**
 * Lectura puntual del tipo de diagnóstico y su servicio.
 *
 * El directorio del Catálogo no expone el servicio ni el código de tipo de
 * servicio; esta lectura de solo lectura se limita a resolverlos sin
 * modificar el módulo 05.
 */
final class DiagnosticoCatalogoGuard
{
    /**
     * @return array{
     *     id: int,
     *     nombre: string,
     *     descripcion: ?string,
     *     dias_estimados: int,
     *     activo: bool,
     *     servicio_id: int,
     *     servicio_nombre: string,
     *     servicio_activo: bool,
     *     tipo_servicio_codigo: ?string,
     *     area_id: ?int,
     *     area_nombre: ?string,
     *     area_color: ?string
     * }
     */
    public function resumen(int $tipoDiagnosticoId): array
    {
        $row = DB::table('tipo_diagnostico')
            ->join('servicio', 'servicio.id', '=', 'tipo_diagnostico.servicio_id')
            ->join('tipo_servicio', 'tipo_servicio.id', '=', 'servicio.tipo_servicio_id')
            ->leftJoin('area', 'area.id', '=', 'servicio.area_id')
            ->where('tipo_diagnostico.id', $tipoDiagnosticoId)
            ->select([
                'tipo_diagnostico.id',
                'tipo_diagnostico.nombre',
                'tipo_diagnostico.descripcion',
                'tipo_diagnostico.dias_estimados',
                'tipo_diagnostico.activo',
                'servicio.id as servicio_id',
                'servicio.nombre as servicio_nombre',
                'servicio.activo as servicio_activo',
                'tipo_servicio.codigo as tipo_servicio_codigo',
                'area.id as area_id',
                'area.nombre as area_nombre',
                'area.color as area_color',
            ])
            ->first();

        if ($row === null) {
            throw new TipoDiagnosticoNotFoundException($tipoDiagnosticoId);
        }

        if (! (bool) $row->activo) {
            throw new TipoDiagnosticoInactivoException($tipoDiagnosticoId);
        }

        if (! (bool) $row->servicio_activo) {
            throw new ServicioInactivoException((int) $row->servicio_id);
        }

        TipoDiagnosticoServicioRule::assertEsDiagnostico(
            $row->tipo_servicio_codigo !== null ? (string) $row->tipo_servicio_codigo : null,
            (string) $row->servicio_nombre,
        );

        return [
            'id' => (int) $row->id,
            'nombre' => (string) $row->nombre,
            'descripcion' => $row->descripcion !== null ? (string) $row->descripcion : null,
            'dias_estimados' => (int) $row->dias_estimados,
            'activo' => (bool) $row->activo,
            'servicio_id' => (int) $row->servicio_id,
            'servicio_nombre' => (string) $row->servicio_nombre,
            'servicio_activo' => (bool) $row->servicio_activo,
            'tipo_servicio_codigo' => $row->tipo_servicio_codigo !== null
                ? (string) $row->tipo_servicio_codigo
                : null,
            'area_id' => $row->area_id !== null ? (int) $row->area_id : null,
            'area_nombre' => $row->area_nombre !== null ? (string) $row->area_nombre : null,
            'area_color' => $row->area_color !== null ? (string) $row->area_color : null,
        ];
    }

    /**
     * @param  list<int>  $especialidadIds
     * @return array<int, array{nombre: string, activo: bool}>
     */
    public function especialidadesPorIds(array $especialidadIds): array
    {
        if ($especialidadIds === []) {
            return [];
        }

        return DB::table('especialidad')
            ->whereIn('id', $especialidadIds)
            ->get(['id', 'nombre', 'activo'])
            ->mapWithKeys(static fn (object $row): array => [
                (int) $row->id => [
                    'nombre' => (string) $row->nombre,
                    'activo' => (bool) $row->activo,
                ],
            ])
            ->all();
    }
}
