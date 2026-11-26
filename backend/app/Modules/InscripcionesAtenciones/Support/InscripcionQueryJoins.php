<?php

namespace App\Modules\InscripcionesAtenciones\Support;

use Illuminate\Database\Eloquent\Builder;

/**
 * Joins de solo lectura para mostrar datos de otros módulos sin N+1.
 *
 * No escribe en Pacientes, Organización ni Catálogo. Las validaciones de
 * negocio siguen delegadas en sus Contracts.
 */
final class InscripcionQueryJoins
{
    public static function apply(Builder $query): Builder
    {
        return $query
            ->select([
                'inscripcion_sala.*',
                'paciente.codigo as paciente_codigo',
                'paciente.nombre as paciente_nombre',
                'paciente.apellido_paterno as paciente_apellido_paterno',
                'paciente.apellido_materno as paciente_apellido_materno',
                'paciente.estado as paciente_estado',
                'tutor.nombre_completo as tutor_nombre',
                'sucursal.nombre as sucursal_nombre',
                'sala.nombre as sala_nombre',
                'tipo_sala.nombre as tipo_sala_nombre',
                'turno_catalogo.nombre as turno_nombre',
                'servicio.nombre as servicio_nombre',
                'tipo_servicio.nombre as tipo_servicio_nombre',
                'categoria_tarifa.nombre as categoria_tarifa_nombre',
            ])
            ->leftJoin('paciente', 'paciente.id', '=', 'inscripcion_sala.paciente_id')
            ->leftJoin('tutor', 'tutor.id', '=', 'inscripcion_sala.tutor_responsable_id')
            ->leftJoin('sucursal', 'sucursal.id', '=', 'inscripcion_sala.sucursal_id')
            ->leftJoin('sala', 'sala.id', '=', 'inscripcion_sala.sala_id')
            ->leftJoin('tipo_sala', 'tipo_sala.id', '=', 'sala.tipo_sala_id')
            ->leftJoin('turno_catalogo', 'turno_catalogo.id', '=', 'sala.turno_catalogo_id')
            ->leftJoin('servicio', 'servicio.id', '=', 'inscripcion_sala.servicio_id')
            ->leftJoin('tipo_servicio', 'tipo_servicio.id', '=', 'servicio.tipo_servicio_id')
            ->leftJoin('categoria_tarifa', 'categoria_tarifa.id', '=', 'inscripcion_sala.categoria_tarifa_id');
    }
}
