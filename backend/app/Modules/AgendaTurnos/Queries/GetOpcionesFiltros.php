<?php

namespace App\Modules\AgendaTurnos\Queries;

use App\Models\User;
use App\Modules\AgendaTurnos\Enums\EstadoTurno;
use App\Modules\AgendaTurnos\Rules\ContrasteColorRule;
use App\Modules\AgendaTurnos\Support\AgendaAccessScope;
use App\Modules\AgendaTurnos\Support\AgendaTurnosPermission;
use App\Modules\AgendaTurnos\Support\DatosOperativosResolver;
use App\Modules\CatalogoServicios\Contracts\AreaResumen;
use App\Modules\CatalogoServicios\Contracts\ServicioResumen;
use App\Modules\Seguridad\Contracts\PermissionEvaluatorContract;

/**
 * [Agenda Semanal] RF-005, RF-011, RN-001, RN-004.
 *
 * Áreas, servicios, salas y profesionales disponibles para poblar los
 * filtros de la sucursal. Nunca consulta turnos.
 */
final class GetOpcionesFiltros
{
    public function __construct(
        private readonly AgendaAccessScope $access,
        private readonly DatosOperativosResolver $datos,
        private readonly ContrasteColorRule $contraste,
        private readonly PermissionEvaluatorContract $permissions,
    ) {}

    /**
     * @return array<string, mixed>
     */
    public function __invoke(User $actor, ?int $sucursalId): array
    {
        $branchId = $this->access->resolverSucursal($actor, $sucursalId);
        $catalogo = $this->datos->catalogo(true);
        $profesionales = $this->datos->profesionalesDeSucursal($branchId);
        $salas = $this->datos->salasDeSucursal($branchId);

        $areas = array_map(
            fn (AreaResumen $area): array => [
                'id' => $area->id,
                'nombre' => $area->nombre,
                'color' => $area->color,
                'color_texto' => ($this->contraste)($area->color),
            ],
            array_values($catalogo['areas']),
        );

        $servicios = array_map(
            static fn (ServicioResumen $servicio): array => [
                'id' => $servicio->id,
                'nombre' => $servicio->nombre,
                'area_id' => $servicio->areaId,
            ],
            array_values($catalogo['servicios']),
        );

        usort($areas, static fn (array $a, array $b): int => $a['nombre'] <=> $b['nombre']);
        usort($servicios, static fn (array $a, array $b): int => $a['nombre'] <=> $b['nombre']);

        return [
            'data' => [
                'areas' => $areas,
                'servicios' => $servicios,
                'salas' => $this->opcionesDesdeMapa($salas['map']),
                'profesionales' => $this->profesionalesDesdeMapa($profesionales['map']),
                'estados' => array_map(
                    static fn (EstadoTurno $estado): array => [
                        'value' => $estado->value,
                        'label' => $estado->label(),
                    ],
                    EstadoTurno::cases(),
                ),
                'especiales' => [
                    'profesional_sin_asignar' => [
                        'value' => 'sin_asignar',
                        'label' => 'Sin asignar',
                    ],
                    'sala_sin_asignar' => [
                        'value' => 'sin_asignar',
                        'label' => 'Sin sala',
                    ],
                ],
            ],
            'disponibilidad' => [
                'profesionales' => $profesionales['disponible'],
                'salas' => $salas['disponible'],
                'servicios' => $catalogo['servicios_disponibles'],
                'areas' => $catalogo['areas_disponibles'],
            ],
            'can' => [
                'ver' => $this->permissions->puede(
                    $actor->id,
                    AgendaTurnosPermission::VER,
                    $branchId,
                ),
                'exportar' => $this->permissions->puede(
                    $actor->id,
                    AgendaTurnosPermission::EXPORTAR,
                    $branchId,
                ),
            ],
        ];
    }

    /**
     * @param  array<int, string>  $mapa
     * @return list<array{id: int, nombre: string}>
     */
    private function opcionesDesdeMapa(array $mapa): array
    {
        $items = [];

        foreach ($mapa as $id => $nombre) {
            $items[] = ['id' => (int) $id, 'nombre' => (string) $nombre];
        }

        usort($items, static fn (array $a, array $b): int => $a['nombre'] <=> $b['nombre']);

        return $items;
    }

    /**
     * @param  array<int, string>  $mapa
     * @return list<array{id: int, nombre_completo: string}>
     */
    private function profesionalesDesdeMapa(array $mapa): array
    {
        $items = [];

        foreach ($mapa as $id => $nombre) {
            $items[] = ['id' => (int) $id, 'nombre_completo' => (string) $nombre];
        }

        usort($items, static fn (array $a, array $b): int => $a['nombre_completo'] <=> $b['nombre_completo']);

        return $items;
    }
}
