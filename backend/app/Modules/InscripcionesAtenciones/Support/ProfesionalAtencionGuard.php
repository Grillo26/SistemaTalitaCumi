<?php

namespace App\Modules\InscripcionesAtenciones\Support;

use App\Modules\InscripcionesAtenciones\Exceptions\ProfesionalFueraDeSucursalException;
use App\Modules\Profesionales\Contracts\ProfessionalDirectoryContract;
use Illuminate\Support\Facades\DB;

/**
 * Carga los profesionales asignados a un diagnóstico con sus especialidades.
 *
 * Si no hay profesionales (`[]`) no toca el contrato de Profesionales: un
 * diagnóstico "Sin asignar" debe funcionar aunque el módulo 03 no esté
 * disponible. Con profesionales, valida activo y sucursal.
 */
final class ProfesionalAtencionGuard
{
    public function __construct(private readonly ProfessionalDirectoryContract $professionals) {}

    /**
     * @param  list<int>  $profesionalIds
     * @return list<array{id: int, nombre_completo: string, especialidades: list<array{id: int, nombre: string}>}>
     */
    public function cargar(array $profesionalIds, int $sucursalId): array
    {
        $ids = array_values(array_unique(array_map('intval', $profesionalIds)));

        if ($ids === []) {
            return [];
        }

        $resultado = [];

        foreach ($ids as $profesionalId) {
            $this->professionals->asegurarActivo($profesionalId);

            $trabajaEnSucursal = DB::table('profesional_sucursal')
                ->where('profesional_id', $profesionalId)
                ->where('sucursal_id', $sucursalId)
                ->where('activo', true)
                ->exists();

            if (! $trabajaEnSucursal) {
                throw new ProfesionalFueraDeSucursalException($profesionalId, $sucursalId);
            }

            $profesional = DB::table('profesional')
                ->where('id', $profesionalId)
                ->first(['id', 'nombre_completo']);

            if ($profesional === null) {
                throw new ProfesionalFueraDeSucursalException(
                    $profesionalId,
                    $sucursalId,
                    'No se encontró el profesional indicado.',
                );
            }

            $especialidades = DB::table('profesional_especialidad')
                ->join('especialidad', 'especialidad.id', '=', 'profesional_especialidad.especialidad_id')
                ->where('profesional_especialidad.profesional_id', $profesionalId)
                ->where('profesional_especialidad.activo', true)
                ->where('especialidad.activo', true)
                ->orderBy('especialidad.nombre')
                ->get(['especialidad.id', 'especialidad.nombre'])
                ->map(static fn (object $row): array => [
                    'id' => (int) $row->id,
                    'nombre' => (string) $row->nombre,
                ])
                ->values()
                ->all();

            $resultado[] = [
                'id' => (int) $profesional->id,
                'nombre_completo' => (string) $profesional->nombre_completo,
                'especialidades' => $especialidades,
            ];
        }

        return $resultado;
    }
}
