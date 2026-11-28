<?php

namespace App\Modules\AgendaTurnos\Support;

use App\Modules\CatalogoServicios\Contracts\CatalogoServiciosDirectoryContract;
use App\Modules\Organizacion\Contracts\SalaDirectoryContract;
use App\Modules\Pacientes\Contracts\PatientDirectoryContract;
use App\Modules\Profesionales\Contracts\ProfessionalDirectoryContract;
use Throwable;

/**
 * Nombres para la previsualización y el resumen (RNF-005).
 *
 * Si un directorio falla, devuelve "No disponible" sin romper el cálculo de
 * fechas ni inventar datos.
 */
final class DirectoriosAgenda
{
    public const NO_DISPONIBLE = 'No disponible';

    public function __construct(
        private readonly PatientDirectoryContract $pacientes,
        private readonly ProfessionalDirectoryContract $profesionales,
        private readonly SalaDirectoryContract $salas,
        private readonly CatalogoServiciosDirectoryContract $catalogo,
    ) {}

    public function paciente(int $pacienteId): string
    {
        return $this->pacientes([$pacienteId])[$pacienteId] ?? self::NO_DISPONIBLE;
    }

    /**
     * @param  list<int>  $pacienteIds
     * @return array<int, string>
     */
    public function pacientes(array $pacienteIds): array
    {
        $ids = $this->ids($pacienteIds);

        if ($ids === []) {
            return [];
        }

        try {
            $nombres = $this->pacientes->nombres($ids);
        } catch (Throwable) {
            $nombres = [];
        }

        return $this->completar($ids, $nombres);
    }

    public function profesional(?int $profesionalId): ?string
    {
        if ($profesionalId === null) {
            return null;
        }

        return $this->profesionales([$profesionalId])[$profesionalId] ?? self::NO_DISPONIBLE;
    }

    /**
     * @param  list<int>  $profesionalIds
     * @return array<int, string>
     */
    public function profesionales(array $profesionalIds): array
    {
        $ids = $this->ids($profesionalIds);

        if ($ids === []) {
            return [];
        }

        try {
            $nombres = $this->profesionales->nombres($ids);
        } catch (Throwable) {
            $nombres = [];
        }

        return $this->completar($ids, $nombres);
    }

    public function sala(?int $salaId): ?string
    {
        if ($salaId === null) {
            return null;
        }

        return $this->salas([$salaId])[$salaId] ?? self::NO_DISPONIBLE;
    }

    /**
     * @param  list<int>  $salaIds
     * @return array<int, string>
     */
    public function salas(array $salaIds): array
    {
        $ids = $this->ids($salaIds);

        if ($ids === []) {
            return [];
        }

        try {
            $nombres = $this->salas->nombres($ids);
        } catch (Throwable) {
            $nombres = [];
        }

        return $this->completar($ids, $nombres);
    }

    /**
     * @param  list<int>  $servicioIds
     * @return array<int, string>
     */
    public function servicios(array $servicioIds): array
    {
        $ids = $this->ids($servicioIds);
        $resultado = [];

        foreach ($ids as $id) {
            $resultado[$id] = $this->servicio($id);
        }

        return $resultado;
    }

    public function servicio(int $servicioId): string
    {
        try {
            return $this->catalogo->servicio($servicioId)?->nombre ?? self::NO_DISPONIBLE;
        } catch (Throwable) {
            return self::NO_DISPONIBLE;
        }
    }

    /**
     * @param  list<int>  $ids
     * @return list<int>
     */
    private function ids(array $ids): array
    {
        return array_values(array_unique(array_filter(
            array_map(static fn (mixed $id): int => (int) $id, $ids),
            static fn (int $id): bool => $id > 0,
        )));
    }

    /**
     * @param  list<int>  $ids
     * @param  array<int, string>  $nombres
     * @return array<int, string>
     */
    private function completar(array $ids, array $nombres): array
    {
        $resultado = [];

        foreach ($ids as $id) {
            $resultado[$id] = $nombres[$id] ?? self::NO_DISPONIBLE;
        }

        return $resultado;
    }
}
