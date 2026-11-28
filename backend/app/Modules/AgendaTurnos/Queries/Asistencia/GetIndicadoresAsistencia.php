<?php

namespace App\Modules\AgendaTurnos\Queries\Asistencia;

use App\Models\User;
use App\Modules\AgendaTurnos\Enums\EstadoTurno;
use App\Modules\AgendaTurnos\Models\Turno;
use App\Modules\AgendaTurnos\Rules\PorcentajeAsistenciaRule;
use App\Modules\AgendaTurnos\Rules\RangoIndicadoresRule;
use App\Modules\AgendaTurnos\Support\AgendaAccessScope;
use App\Modules\AgendaTurnos\Support\DirectoriosAgenda;
use App\Modules\Organizacion\Contracts\BranchDirectoryContract;
use App\Modules\Organizacion\Exceptions\BranchAccessDeniedException;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/**
 * 07.04/RF-009, 07.04/RF-015, 07.04/CU-03, 07.04/CA-008.
 *
 * Indicadores agregados por paciente, sala, servicio, profesional, sucursal
 * o día. Solo sucursales accesibles. No expone datos clínicos.
 */
final class GetIndicadoresAsistencia
{
    private const COLUMNAS = [
        'paciente' => 'paciente_id',
        'sala' => 'sala_id',
        'servicio' => 'servicio_id',
        'profesional' => 'profesional_id',
        'sucursal' => 'sucursal_id',
        'dia' => 'fecha',
    ];

    public function __construct(
        private readonly AgendaAccessScope $scope,
        private readonly DirectoriosAgenda $nombres,
        private readonly BranchDirectoryContract $branches,
        private readonly RangoIndicadoresRule $rango,
        private readonly PorcentajeAsistenciaRule $porcentaje,
    ) {}

    /**
     * @param  array<string, mixed>  $filtros
     * @return array<string, mixed>
     */
    public function __invoke(
        array $filtros,
        User $actor,
        int $page = 1,
        int $perPage = 25,
    ): array {
        $resultado = $this->construir($filtros, $actor);
        $filas = $resultado['filas'];
        $total = count($filas);
        $perPage = max(1, min($perPage, 100));
        $lastPage = max(1, (int) ceil($total / $perPage));
        $page = max(1, min($page, $lastPage));
        $items = array_slice($filas, ($page - 1) * $perPage, $perPage);

        return [
            ...$resultado,
            'filas' => $items,
            'meta' => [
                'current_page' => $page,
                'last_page' => $lastPage,
                'per_page' => $perPage,
                'total' => $total,
                'from' => $total === 0 ? null : (($page - 1) * $perPage) + 1,
                'to' => $total === 0 ? null : min($page * $perPage, $total),
            ],
        ];
    }

    /**
     * @param  array<string, mixed>  $filtros
     * @return array<string, mixed>
     */
    public function todas(array $filtros, User $actor): array
    {
        return $this->construir($filtros, $actor);
    }

    /**
     * @param  array<string, mixed>  $filtros
     * @return array<string, mixed>
     */
    private function construir(array $filtros, User $actor): array
    {
        $errores = $this->rango->errores(
            (string) ($filtros['periodo_desde'] ?? ''),
            (string) ($filtros['periodo_hasta'] ?? ''),
        );

        if ($errores !== []) {
            throw ValidationException::withMessages(['periodo_desde' => $errores]);
        }

        $accesibles = $this->scope->accesibles($actor);
        $sucursalId = (int) $filtros['sucursal_id'];

        if (! in_array($sucursalId, $accesibles, true)) {
            throw new BranchAccessDeniedException;
        }

        $agrupacion = (string) $filtros['agrupacion'];
        $columna = self::COLUMNAS[$agrupacion];

        $query = Turno::query()
            ->where('sucursal_id', $sucursalId)
            ->whereBetween('fecha', [
                (string) $filtros['periodo_desde'],
                (string) $filtros['periodo_hasta'],
            ]);

        foreach (['sala_id' => 'sala_id', 'servicio_id' => 'servicio_id', 'profesional_id' => 'profesional_id', 'paciente_id' => 'paciente_id'] as $filtro => $campo) {
            if (isset($filtros[$filtro])) {
                $query->where($campo, (int) $filtros[$filtro]);
            }
        }

        $rows = $query
            ->select([
                $columna.' as clave',
                DB::raw('COUNT(*) as total'),
                DB::raw("SUM(CASE WHEN estado = '".EstadoTurno::ASISTIO->value."' THEN 1 ELSE 0 END) as asistidos"),
                DB::raw("SUM(CASE WHEN estado = '".EstadoTurno::FALTO->value."' THEN 1 ELSE 0 END) as faltos"),
                DB::raw("SUM(CASE WHEN estado = '".EstadoTurno::CANCELADO->value."' THEN 1 ELSE 0 END) as cancelados"),
                DB::raw("SUM(CASE WHEN estado = '".EstadoTurno::PROGRAMADO->value."' THEN 1 ELSE 0 END) as pendientes"),
                DB::raw("SUM(CASE WHEN estado = '".EstadoTurno::REPROGRAMADO->value."' THEN 1 ELSE 0 END) as reprogramados"),
            ])
            ->groupBy($columna)
            ->orderBy($columna)
            ->get();

        $etiquetas = $this->etiquetas($agrupacion, $rows->pluck('clave')->all());

        $filas = $rows->map(function ($row) use ($agrupacion, $etiquetas): array {
            $clave = $row->clave;
            $id = is_numeric($clave) ? (int) $clave : null;
            $asistidos = (int) $row->asistidos;
            $faltos = (int) $row->faltos;

            return [
                'clave' => $agrupacion === 'dia' ? (string) $clave : $id,
                'etiqueta' => $agrupacion === 'dia'
                    ? (string) $clave
                    : ($etiquetas[$id] ?? $this->fallback($agrupacion)),
                'total' => (int) $row->total,
                'asistidos' => $asistidos,
                'faltos' => $faltos,
                'cancelados' => (int) $row->cancelados,
                'pendientes' => (int) $row->pendientes,
                'reprogramados' => (int) $row->reprogramados,
                'porcentaje' => $this->porcentaje->calcular($asistidos, $faltos),
            ];
        })->values()->all();

        $totales = [
            'total' => array_sum(array_column($filas, 'total')),
            'asistidos' => array_sum(array_column($filas, 'asistidos')),
            'faltos' => array_sum(array_column($filas, 'faltos')),
            'cancelados' => array_sum(array_column($filas, 'cancelados')),
            'pendientes' => array_sum(array_column($filas, 'pendientes')),
            'reprogramados' => array_sum(array_column($filas, 'reprogramados')),
        ];
        $totales['porcentaje'] = $this->porcentaje->calcular(
            $totales['asistidos'],
            $totales['faltos'],
        );

        return [
            'periodo' => [
                'desde' => (string) $filtros['periodo_desde'],
                'hasta' => (string) $filtros['periodo_hasta'],
            ],
            'sucursal' => [
                'id' => $sucursalId,
                'nombre' => $this->sucursales([$sucursalId])[$sucursalId]
                    ?? DirectoriosAgenda::NO_DISPONIBLE,
            ],
            'sucursal_id' => $sucursalId,
            'agrupacion' => $agrupacion,
            'filas' => $filas,
            'totales' => $totales,
            'filtros' => $filtros,
        ];
    }

    /**
     * @param  list<mixed>  $claves
     * @return array<int|string, string>
     */
    private function etiquetas(string $agrupacion, array $claves): array
    {
        $ids = array_values(array_filter(
            array_map(static fn (mixed $clave): int => (int) $clave, $claves),
            static fn (int $id): bool => $id > 0,
        ));

        return match ($agrupacion) {
            'paciente' => $this->nombres->pacientes($ids),
            'sala' => $this->nombres->salas($ids),
            'servicio' => $this->nombres->servicios($ids),
            'profesional' => $this->nombres->profesionales($ids),
            'sucursal' => $this->sucursales($ids),
            default => [],
        };
    }

    /**
     * @param  list<int>  $ids
     * @return array<int, string>
     */
    private function sucursales(array $ids): array
    {
        $resumenes = $this->branches->resumenes($ids);
        $resultado = [];

        foreach ($ids as $id) {
            $resultado[$id] = isset($resumenes[$id])
                ? $resumenes[$id]->nombre
                : DirectoriosAgenda::NO_DISPONIBLE;
        }

        return $resultado;
    }

    private function fallback(string $agrupacion): string
    {
        return match ($agrupacion) {
            'sala' => 'Sin sala',
            'profesional' => 'Sin asignar',
            default => DirectoriosAgenda::NO_DISPONIBLE,
        };
    }
}
