<?php

namespace App\Modules\InscripcionesAtenciones\Support;

use App\Modules\CatalogoServicios\Exceptions\ServicioInactivoException;
use App\Modules\CatalogoServicios\Exceptions\ServicioNotFoundException;
use App\Modules\InscripcionesAtenciones\Exceptions\ServicioNoEsTerapiaIndividualException;
use Illuminate\Support\Facades\DB;

/**
 * Verifica que el servicio sea activo y del tipo terapia_individual.
 *
 * El contrato de lectura del Catálogo no expone tipo_servicio.codigo; esta
 * lectura puntual no modifica el módulo 05.
 */
final class TerapiaIndividualServicioGuard
{
    /**
     * @return array{id: int, nombre: string, tipo_servicio_id: int, tipo_servicio_codigo: ?string}
     */
    public function assertServicioValido(int $servicioId): array
    {
        $row = DB::table('servicio')
            ->join('tipo_servicio', 'tipo_servicio.id', '=', 'servicio.tipo_servicio_id')
            ->where('servicio.id', $servicioId)
            ->select([
                'servicio.id',
                'servicio.nombre',
                'servicio.activo',
                'servicio.tipo_servicio_id',
                'tipo_servicio.codigo as tipo_servicio_codigo',
            ])
            ->first();

        if ($row === null) {
            throw new ServicioNotFoundException($servicioId);
        }

        if (! (bool) $row->activo) {
            throw new ServicioInactivoException($servicioId);
        }

        if ((string) $row->tipo_servicio_codigo !== 'terapia_individual') {
            throw new ServicioNoEsTerapiaIndividualException((string) $row->nombre);
        }

        return [
            'id' => (int) $row->id,
            'nombre' => (string) $row->nombre,
            'tipo_servicio_id' => (int) $row->tipo_servicio_id,
            'tipo_servicio_codigo' => $row->tipo_servicio_codigo !== null
                ? (string) $row->tipo_servicio_codigo
                : null,
        ];
    }
}
