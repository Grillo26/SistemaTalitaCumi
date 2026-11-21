<?php

namespace App\Modules\Profesionales\Contracts;

use App\Modules\Organizacion\Contracts\BranchDirectoryContract;
use App\Modules\Profesionales\Exceptions\ProfesionalNotAssignableException;
use App\Modules\Profesionales\Models\Especialidad;
use App\Modules\Profesionales\Models\Profesional;
use App\Modules\Profesionales\Models\ProfesionalEspecialidad;
use App\Modules\Profesionales\Models\ProfesionalSucursal;
use App\Modules\Profesionales\Support\ProfesionalValidatorCacheKeys;
use Illuminate\Support\Facades\Cache;

/**
 * Implementación cacheada del validador (03/RN-07, 03/RN-08, 03/RN-09).
 *
 * El estado del profesional, la especialidad y la matriz se cachean con una
 * época de invalidación explícita. La sucursal activa se consulta en vivo
 * para no quedar obsoleta si el módulo 01 la desactiva.
 */
final class EloquentProfessionalAssignmentValidator implements ProfessionalAssignmentValidatorContract
{
    public function __construct(private readonly BranchDirectoryContract $branches) {}

    public function puedeAsignar(
        int $profesionalId,
        int $especialidadId,
        int $sucursalId,
    ): ResultadoAsignacion {
        $data = $this->data($profesionalId, $especialidadId, $sucursalId);

        if (! $data['profesional_existe']) {
            return ResultadoAsignacion::rechazo(
                'PROFESIONAL_NO_ENCONTRADO',
                'No se encontró el profesional indicado.',
            );
        }

        if (! $data['profesional_activo']) {
            return ResultadoAsignacion::rechazo(
                'PROFESIONAL_INACTIVO',
                'El profesional está inactivo y no puede asignarse a nuevos turnos o diagnósticos.',
            );
        }

        if (! $data['especialidad_existe']) {
            return ResultadoAsignacion::rechazo(
                'ESPECIALIDAD_NO_ENCONTRADA',
                'No se encontró la especialidad indicada.',
            );
        }

        if (! $data['especialidad_activa']) {
            return ResultadoAsignacion::rechazo(
                'ESPECIALIDAD_INACTIVA',
                'La especialidad está inactiva y no puede asignarse a nuevos turnos o diagnósticos.',
            );
        }

        if (! $data['especialidad_asignada']) {
            return ResultadoAsignacion::rechazo(
                'ESPECIALIDAD_NO_ASIGNADA',
                'El profesional no cubre la especialidad indicada.',
            );
        }

        if (! $this->branches->estaActiva($sucursalId)) {
            return ResultadoAsignacion::rechazo(
                'SUCURSAL_INACTIVA',
                'La sucursal no existe o está inactiva.',
            );
        }

        if (! $data['sucursal_asignada']) {
            return ResultadoAsignacion::rechazo(
                'SUCURSAL_NO_ASIGNADA',
                'El profesional no trabaja en la sucursal indicada.',
            );
        }

        return ResultadoAsignacion::valida();
    }

    public function asegurarAsignable(
        int $profesionalId,
        int $especialidadId,
        int $sucursalId,
    ): void {
        $resultado = $this->puedeAsignar($profesionalId, $especialidadId, $sucursalId);

        if (! $resultado->puede) {
            throw new ProfesionalNotAssignableException($resultado);
        }
    }

    /**
     * @return array{
     *     profesional_existe: bool,
     *     profesional_activo: bool,
     *     especialidad_existe: bool,
     *     especialidad_activa: bool,
     *     especialidad_asignada: bool,
     *     sucursal_asignada: bool
     * }
     */
    private function data(int $profesionalId, int $especialidadId, int $sucursalId): array
    {
        $epoch = ProfesionalValidatorCacheKeys::epoch();
        $key = ProfesionalValidatorCacheKeys::forTriple($epoch, $profesionalId, $especialidadId, $sucursalId);

        return Cache::remember($key, ProfesionalValidatorCacheKeys::TTL_SECONDS, static function () use ($profesionalId, $especialidadId, $sucursalId): array {
            $profesional = Profesional::query()
                ->select(['id', 'activo'])
                ->find($profesionalId);

            $especialidad = Especialidad::query()
                ->select(['id', 'activo'])
                ->find($especialidadId);

            $especialidadAsignada = ProfesionalEspecialidad::query()
                ->where('profesional_id', $profesionalId)
                ->where('especialidad_id', $especialidadId)
                ->where('activo', true)
                ->exists();

            $sucursalAsignada = ProfesionalSucursal::query()
                ->where('profesional_id', $profesionalId)
                ->where('sucursal_id', $sucursalId)
                ->where('activo', true)
                ->exists();

            return [
                'profesional_existe' => $profesional !== null,
                'profesional_activo' => $profesional?->activo === true,
                'especialidad_existe' => $especialidad !== null,
                'especialidad_activa' => $especialidad?->activo === true,
                'especialidad_asignada' => $especialidadAsignada,
                'sucursal_asignada' => $sucursalAsignada,
            ];
        });
    }
}
