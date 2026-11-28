<?php

namespace App\Modules\AgendaTurnos\Support;

use App\Modules\CatalogoServicios\Contracts\AreaResumen;
use App\Modules\CatalogoServicios\Contracts\CatalogoServiciosDirectoryContract;
use App\Modules\CatalogoServicios\Contracts\ServicioResumen;
use App\Modules\Organizacion\Contracts\SalaDirectoryContract;
use App\Modules\Pacientes\Contracts\PatientDirectoryContract;
use App\Modules\Profesionales\Contracts\ProfessionalDirectoryContract;
use Illuminate\Support\Facades\Log;
use Throwable;

/**
 * Resuelve nombres operativos una sola vez por request.
 *
 * Los contratos son fail-open para la agenda: si uno falla, se registra el
 * dominio como no disponible y el campo afectado queda en null. Nunca se
 * inventan nombres ni se completa con datos de ejemplo.
 */
final class DatosOperativosResolver
{
    public function __construct(
        private readonly PatientDirectoryContract $pacientes,
        private readonly ProfessionalDirectoryContract $profesionales,
        private readonly SalaDirectoryContract $salas,
        private readonly CatalogoServiciosDirectoryContract $catalogo,
    ) {}

    /**
     * @param  list<int>  $pacienteIds
     * @param  list<int>  $profesionalIds
     * @param  list<int>  $salaIds
     */
    public function resolver(
        array $pacienteIds,
        array $profesionalIds,
        array $salaIds,
        bool $soloActivosCatalogo = false,
    ): DatosOperativos {
        $catalogo = $this->catalogo($soloActivosCatalogo);
        [$pacientes, $pacientesDisponibles] = $this->mapaPacientes($pacienteIds);
        [$profesionales, $profesionalesDisponibles] = $this->mapaProfesionales($profesionalIds);
        [$salas, $salasDisponibles] = $this->mapaSalas($salaIds);

        return new DatosOperativos(
            pacientes: $pacientes,
            profesionales: $profesionales,
            salas: $salas,
            servicios: $catalogo['servicios'],
            areas: $catalogo['areas'],
            pacientesDisponibles: $pacientesDisponibles,
            profesionalesDisponibles: $profesionalesDisponibles,
            salasDisponibles: $salasDisponibles,
            serviciosDisponibles: $catalogo['servicios_disponibles'],
            areasDisponibles: $catalogo['areas_disponibles'],
        );
    }

    /**
     * @return array{
     *     servicios: array<int, ServicioResumen>,
     *     areas: array<int, AreaResumen>,
     *     servicios_disponibles: bool,
     *     areas_disponibles: bool
     * }
     */
    public function catalogo(bool $soloActivos = true): array
    {
        $servicios = [];
        $areas = [];
        $serviciosDisponibles = true;
        $areasDisponibles = true;

        try {
            foreach ($this->catalogo->servicios(null, null, $soloActivos) as $servicio) {
                $servicios[$servicio->id] = $servicio;
            }
        } catch (Throwable $exception) {
            $serviciosDisponibles = false;
            Log::warning('Agenda: no se pudieron resolver los servicios del catálogo.', [
                'error' => $exception->getMessage(),
            ]);
        }

        try {
            foreach ($this->catalogo->areas($soloActivos) as $area) {
                $areas[$area->id] = $area;
            }
        } catch (Throwable $exception) {
            $areasDisponibles = false;
            Log::warning('Agenda: no se pudieron resolver las áreas del catálogo.', [
                'error' => $exception->getMessage(),
            ]);
        }

        return [
            'servicios' => $servicios,
            'areas' => $areas,
            'servicios_disponibles' => $serviciosDisponibles,
            'areas_disponibles' => $areasDisponibles,
        ];
    }

    /**
     * @return array{0: array<int, string>, 1: bool}
     */
    public function nombresPacientes(array $pacienteIds): array
    {
        $ids = $this->ids($pacienteIds);

        if ($ids === []) {
            return [[], true];
        }

        try {
            return [$this->normalizarMapa($this->pacientes->nombres($ids)), true];
        } catch (Throwable $exception) {
            Log::warning('Agenda: no se pudieron resolver los nombres de pacientes.', [
                'error' => $exception->getMessage(),
            ]);

            return [[], false];
        }
    }

    /**
     * @return array{0: array<int, string>, 1: bool}
     */
    public function nombresProfesionales(array $profesionalIds): array
    {
        $ids = $this->ids($profesionalIds);

        if ($ids === []) {
            return [[], true];
        }

        try {
            return [$this->normalizarMapa($this->profesionales->nombres($ids)), true];
        } catch (Throwable $exception) {
            Log::warning('Agenda: no se pudieron resolver los nombres de profesionales.', [
                'error' => $exception->getMessage(),
            ]);

            return [[], false];
        }
    }

    /**
     * @return array{0: array<int, string>, 1: bool}
     */
    public function nombresSalas(array $salaIds): array
    {
        $ids = $this->ids($salaIds);

        if ($ids === []) {
            return [[], true];
        }

        try {
            return [$this->normalizarMapa($this->salas->nombres($ids)), true];
        } catch (Throwable $exception) {
            Log::warning('Agenda: no se pudieron resolver los nombres de salas.', [
                'error' => $exception->getMessage(),
            ]);

            return [[], false];
        }
    }

    /**
     * @return array{map: array<int, string>, disponible: bool}
     */
    public function profesionalesDeSucursal(int $sucursalId): array
    {
        try {
            return [
                'map' => $this->normalizarMapa($this->profesionales->nombresPorSucursal($sucursalId)),
                'disponible' => true,
            ];
        } catch (Throwable $exception) {
            Log::warning('Agenda: no se pudieron resolver los profesionales de la sucursal.', [
                'sucursal_id' => $sucursalId,
                'error' => $exception->getMessage(),
            ]);

            return ['map' => [], 'disponible' => false];
        }
    }

    /**
     * @return array{map: array<int, string>, disponible: bool}
     */
    public function salasDeSucursal(int $sucursalId): array
    {
        try {
            return [
                'map' => $this->normalizarMapa($this->salas->nombresPorSucursal($sucursalId)),
                'disponible' => true,
            ];
        } catch (Throwable $exception) {
            Log::warning('Agenda: no se pudieron resolver las salas de la sucursal.', [
                'sucursal_id' => $sucursalId,
                'error' => $exception->getMessage(),
            ]);

            return ['map' => [], 'disponible' => false];
        }
    }

    /**
     * @param  list<int>  $ids
     * @return array{0: array<int, string>, 1: bool}
     */
    private function mapaPacientes(array $ids): array
    {
        return $this->nombresPacientes($ids);
    }

    /**
     * @param  list<int>  $ids
     * @return array{0: array<int, string>, 1: bool}
     */
    private function mapaProfesionales(array $ids): array
    {
        return $this->nombresProfesionales($ids);
    }

    /**
     * @param  list<int>  $ids
     * @return array{0: array<int, string>, 1: bool}
     */
    private function mapaSalas(array $ids): array
    {
        return $this->nombresSalas($ids);
    }

    /**
     * @param  array<int|string, string>  $mapa
     * @return array<int, string>
     */
    private function normalizarMapa(array $mapa): array
    {
        $normalizado = [];

        foreach ($mapa as $id => $nombre) {
            $normalizado[(int) $id] = (string) $nombre;
        }

        return $normalizado;
    }

    /**
     * @param  list<int>  $ids
     * @return list<int>
     */
    private function ids(array $ids): array
    {
        return array_values(array_unique(array_map(
            static fn (mixed $id): int => (int) $id,
            array_filter($ids, static fn (mixed $id): bool => (int) $id > 0),
        )));
    }
}
