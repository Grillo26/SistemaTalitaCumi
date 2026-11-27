<?php

namespace App\Modules\InscripcionesAtenciones\Contracts;

use App\Modules\InscripcionesAtenciones\Enums\TipoEntidadVigencia;
use App\Modules\InscripcionesAtenciones\Models\InscripcionSala;
use App\Modules\InscripcionesAtenciones\Models\PaqueteSesiones;
use App\Modules\InscripcionesAtenciones\Rules\InscripcionAdmiteAsistenciaRule;
use App\Modules\InscripcionesAtenciones\Support\EstadoInscripcionResolver;
use DateTimeImmutable;

/**
 * Implementación real para Agenda: valida el período del turno, no "hoy".
 */
final class EloquentVigenciaEntidad implements VigenciaEntidadContract
{
    public function puedeRegistrarAsistencia(
        TipoEntidadVigencia $tipo,
        int $entidadId,
        string $fechaTurno,
    ): bool {
        if (! $this->fechaValida($fechaTurno)) {
            return false;
        }

        if ($tipo === TipoEntidadVigencia::INSCRIPCION_SALA) {
            $entidad = InscripcionSala::query()->find($entidadId);

            if ($entidad === null) {
                return false;
            }

            $efectivo = EstadoInscripcionResolver::efectivoSala($entidad, $fechaTurno);

            return InscripcionAdmiteAsistenciaRule::permite($efectivo)
                && $this->dentroDelPeriodo(
                    $entidad->fecha_inicio->toDateString(),
                    $entidad->fecha_fin->toDateString(),
                    $fechaTurno,
                );
        }

        $entidad = PaqueteSesiones::query()->find($entidadId);

        if ($entidad === null) {
            return false;
        }

        $efectivo = EstadoInscripcionResolver::efectivoPaquete($entidad, $fechaTurno);

        return InscripcionAdmiteAsistenciaRule::permite($efectivo)
            && $this->dentroDelPeriodo(
                $entidad->fecha_inicio->toDateString(),
                $entidad->fecha_fin->toDateString(),
                $fechaTurno,
            );
    }

    private function fechaValida(string $fecha): bool
    {
        $normalizada = trim($fecha);
        $date = DateTimeImmutable::createFromFormat('!Y-m-d', $normalizada);

        return $date !== false && $date->format('Y-m-d') === $normalizada;
    }

    private function dentroDelPeriodo(string $inicio, string $fin, string $fechaTurno): bool
    {
        return $fechaTurno >= $inicio && $fechaTurno <= $fin;
    }
}
