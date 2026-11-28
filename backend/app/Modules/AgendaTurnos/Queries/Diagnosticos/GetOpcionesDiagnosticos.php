<?php

namespace App\Modules\AgendaTurnos\Queries\Diagnosticos;

use App\Models\User;
use App\Modules\AgendaTurnos\Enums\EstadoGeneralDiagnostico;
use App\Modules\AgendaTurnos\Support\AgendaAccessScope;
use App\Modules\AgendaTurnos\Support\DiagnosticosPermission;
use App\Modules\InscripcionesAtenciones\Contracts\DiagnosticoAtencionContract;
use App\Modules\Organizacion\Contracts\BranchDirectoryContract;
use App\Modules\Organizacion\Contracts\BranchResumen;
use App\Modules\Profesionales\Contracts\ProfessionalDirectoryContract;
use Throwable;

/**
 * 07.05/RF-006 / 07.05/RF-008.
 *
 * Opciones de filtros del listado/reporte, restringidas al alcance del actor.
 */
final class GetOpcionesDiagnosticos
{
    public function __construct(
        private readonly DiagnosticoAtencionContract $diagnosticos,
        private readonly AgendaAccessScope $access,
        private readonly BranchDirectoryContract $sucursales,
        private readonly ProfessionalDirectoryContract $profesionales,
    ) {}

    /**
     * @return array<string, mixed>
     */
    public function __invoke(User $actor, ?int $sucursalId = null): array
    {
        $accesibles = $this->access->accesibles($actor);
        $branchId = $this->access->resolverSucursal($actor, $sucursalId);

        try {
            $resumenes = $this->sucursales->resumenes($accesibles);
        } catch (Throwable) {
            $resumenes = [];
        }

        $branches = [];

        foreach ($resumenes as $resumen) {
            /** @var BranchResumen $resumen */
            $branches[] = [
                'id' => $resumen->id,
                'nombre' => $resumen->nombre,
            ];
        }

        usort($branches, static fn (array $a, array $b): int => $a['nombre'] <=> $b['nombre']);

        $profesionales = [];

        try {
            foreach ($this->profesionales->nombresPorSucursal($branchId, false) as $id => $nombre) {
                $profesionales[] = [
                    'id' => (int) $id,
                    'nombre_completo' => (string) $nombre,
                ];
            }
        } catch (Throwable) {
            $profesionales = [];
        }

        usort(
            $profesionales,
            static fn (array $a, array $b): int => $a['nombre_completo'] <=> $b['nombre_completo'],
        );

        return [
            'data' => [
                'sucursal_id' => $branchId,
                'sucursales' => $branches,
                'tipos_diagnostico' => $this->diagnosticos->tiposDiagnostico(),
                'profesionales' => $profesionales,
                'estados' => EstadoGeneralDiagnostico::options(),
            ],
            'can' => [
                'ver' => $actor->can(DiagnosticosPermission::VER),
                'exportar' => $actor->can(DiagnosticosPermission::EXPORTAR),
            ],
        ];
    }
}
