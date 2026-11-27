<?php

namespace App\Modules\InscripcionesAtenciones\Support;

use Illuminate\Database\Eloquent\Builder;

/**
 * Joins de solo lectura para mostrar datos de otros módulos sin N+1.
 */
final class PaqueteQueryJoins
{
    public static function apply(Builder $query): Builder
    {
        return $query
            ->select([
                'paquete_sesiones.*',
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
                'profesional.nombre_completo as profesional_nombre',
            ])
            ->leftJoin('paciente', 'paciente.id', '=', 'paquete_sesiones.paciente_id')
            ->leftJoin('tutor', 'tutor.id', '=', 'paquete_sesiones.tutor_responsable_id')
            ->leftJoin('sucursal', 'sucursal.id', '=', 'paquete_sesiones.sucursal_id')
            ->leftJoin('servicio', 'servicio.id', '=', 'paquete_sesiones.servicio_id')
            ->leftJoin('tipo_servicio', 'tipo_servicio.id', '=', 'servicio.tipo_servicio_id')
            ->leftJoin('categoria_tarifa', 'categoria_tarifa.id', '=', 'paquete_sesiones.categoria_tarifa_id')
            ->leftJoin('profesional', 'profesional.id', '=', 'paquete_sesiones.profesional_id');
    }
}
