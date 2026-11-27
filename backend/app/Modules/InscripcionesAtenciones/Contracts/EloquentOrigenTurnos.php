<?php

namespace App\Modules\InscripcionesAtenciones\Contracts;

use App\Modules\InscripcionesAtenciones\Models\InscripcionSala;
use App\Modules\InscripcionesAtenciones\Models\PaqueteSesiones;
use App\Modules\InscripcionesAtenciones\Support\EstadoInscripcionResolver;
use InvalidArgumentException;

/**
 * Implementación real para Agenda: lee la entidad y resuelve su estado
 * efectivo (activa, en pausa, vencida, renovada o retirada).
 */
final class EloquentOrigenTurnos implements OrigenTurnosContract
{
    public const SALA = 'inscripcion_sala';

    public const PAQUETE = 'paquete_sesiones';

    public function obtener(string $tipo, int $id): ?OrigenTurnosResumen
    {
        return match ($tipo) {
            self::SALA => $this->sala($id),
            self::PAQUETE => $this->paquete($id),
            default => throw new InvalidArgumentException("Tipo de origen de turno desconocido: {$tipo}."),
        };
    }

    private function sala(int $id): ?OrigenTurnosResumen
    {
        $inscripcion = InscripcionSala::query()->find($id);

        if ($inscripcion === null) {
            return null;
        }

        $estado = EstadoInscripcionResolver::efectivoSala($inscripcion);

        return new OrigenTurnosResumen(
            tipo: self::SALA,
            id: (int) $inscripcion->id,
            sucursalId: (int) $inscripcion->sucursal_id,
            pacienteId: (int) $inscripcion->paciente_id,
            servicioId: (int) $inscripcion->servicio_id,
            salaId: (int) $inscripcion->sala_id,
            profesionalId: null,
            fechaInicio: $inscripcion->fecha_inicio->toDateString(),
            fechaFin: $inscripcion->fecha_fin->toDateString(),
            cantidadSesiones: null,
            estadoEfectivo: $estado->value,
        );
    }

    private function paquete(int $id): ?OrigenTurnosResumen
    {
        $paquete = PaqueteSesiones::query()->find($id);

        if ($paquete === null) {
            return null;
        }

        $estado = EstadoInscripcionResolver::efectivoPaquete($paquete);

        return new OrigenTurnosResumen(
            tipo: self::PAQUETE,
            id: (int) $paquete->id,
            sucursalId: (int) $paquete->sucursal_id,
            pacienteId: (int) $paquete->paciente_id,
            servicioId: (int) $paquete->servicio_id,
            salaId: null,
            profesionalId: $paquete->profesional_id === null
                ? null
                : (int) $paquete->profesional_id,
            fechaInicio: $paquete->fecha_inicio->toDateString(),
            fechaFin: $paquete->fecha_fin->toDateString(),
            cantidadSesiones: (int) $paquete->cantidad_sesiones,
            estadoEfectivo: $estado->value,
            diasSemana: $paquete->dias_semana,
            horaInicio: (string) $paquete->hora,
            duracionMinutos: (int) $paquete->duracion_minutos,
        );
    }
}
