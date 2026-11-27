<?php

namespace App\Modules\InscripcionesAtenciones\Support;

use Illuminate\Database\Eloquent\Builder;

/**
 * Joins de solo lectura para listados y fichas de atenciones.
 */
final class AtencionQueryJoins
{
    public static function entrevistas(Builder $query): Builder
    {
        return $query
            ->select([
                'atencion_entrevista.*',
                'paciente.codigo as paciente_codigo',
                'paciente.nombre as paciente_nombre',
                'paciente.apellido_paterno as paciente_apellido_paterno',
                'paciente.apellido_materno as paciente_apellido_materno',
                'paciente.estado as paciente_estado',
                'tutor.nombre_completo as tutor_nombre',
                'sucursal.nombre as sucursal_nombre',
                'servicio.nombre as servicio_nombre',
                'tipo_servicio.nombre as tipo_servicio_nombre',
                'categoria_tarifa.nombre as categoria_tarifa_nombre',
            ])
            ->leftJoin('paciente', 'paciente.id', '=', 'atencion_entrevista.paciente_id')
            ->leftJoin('tutor', 'tutor.id', '=', 'atencion_entrevista.tutor_id')
            ->leftJoin('sucursal', 'sucursal.id', '=', 'atencion_entrevista.sucursal_id')
            ->leftJoin('servicio', 'servicio.id', '=', 'atencion_entrevista.servicio_id')
            ->leftJoin('tipo_servicio', 'tipo_servicio.id', '=', 'servicio.tipo_servicio_id')
            ->leftJoin('categoria_tarifa', 'categoria_tarifa.id', '=', 'atencion_entrevista.categoria_tarifa_id');
    }

    public static function diagnosticos(Builder $query): Builder
    {
        return $query
            ->select([
                'atencion_diagnostico.*',
                'paciente.codigo as paciente_codigo',
                'paciente.nombre as paciente_nombre',
                'paciente.apellido_paterno as paciente_apellido_paterno',
                'paciente.apellido_materno as paciente_apellido_materno',
                'paciente.estado as paciente_estado',
                'sucursal.nombre as sucursal_nombre',
                'servicio.nombre as servicio_nombre',
                'tipo_servicio.nombre as tipo_servicio_nombre',
                'categoria_tarifa.nombre as categoria_tarifa_nombre',
                'tipo_diagnostico.nombre as tipo_diagnostico_nombre',
                'tipo_diagnostico.dias_estimados as tipo_diagnostico_dias',
                'area.id as area_id',
                'area.nombre as area_nombre',
                'area.color as area_color',
            ])
            ->leftJoin('paciente', 'paciente.id', '=', 'atencion_diagnostico.paciente_id')
            ->leftJoin('sucursal', 'sucursal.id', '=', 'atencion_diagnostico.sucursal_id')
            ->leftJoin('servicio', 'servicio.id', '=', 'atencion_diagnostico.servicio_id')
            ->leftJoin('tipo_servicio', 'tipo_servicio.id', '=', 'servicio.tipo_servicio_id')
            ->leftJoin('area', 'area.id', '=', 'servicio.area_id')
            ->leftJoin('categoria_tarifa', 'categoria_tarifa.id', '=', 'atencion_diagnostico.categoria_tarifa_id')
            ->leftJoin('tipo_diagnostico', 'tipo_diagnostico.id', '=', 'atencion_diagnostico.tipo_diagnostico_id');
    }
}
