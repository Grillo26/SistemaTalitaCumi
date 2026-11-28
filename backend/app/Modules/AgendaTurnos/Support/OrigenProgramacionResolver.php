<?php

namespace App\Modules\AgendaTurnos\Support;

use App\Modules\AgendaTurnos\Enums\EstadoOrigenProgramacion;
use App\Modules\AgendaTurnos\Enums\TipoOrigenTurno;
use App\Modules\AgendaTurnos\Exceptions\ConfiguracionAgendaIncompletaException;
use App\Modules\AgendaTurnos\Exceptions\OrigenNoEncontradoException;
use App\Modules\AgendaTurnos\Rules\DuracionTurnoRule;
use App\Modules\CatalogoServicios\Contracts\CatalogoServiciosDirectoryContract;
use App\Modules\InscripcionesAtenciones\Contracts\OrigenTurnosContract;
use App\Modules\Organizacion\Contracts\ParameterReaderContract;
use App\Modules\Organizacion\Contracts\SalaPatronContract;
use App\Modules\Organizacion\Exceptions\ParameterNotFoundException;
use LogicException;
use Throwable;

/**
 * Resuelve la entidad origen y su patrón para el cálculo de turnos.
 *
 * Los paquetes traen días, hora y duración propios. Las inscripciones a sala
 * dependen de `SalaPatronContract`; mientras Salas no lo exponga, el origen
 * queda con `patron` null y un motivo explícito (RF-001 pendiente).
 */
final class OrigenProgramacionResolver
{
    public const DURACION_SESION_MINUTOS = 'duracion_sesion_minutos';

    public function __construct(
        private readonly OrigenTurnosContract $origenes,
        private readonly SalaPatronContract $salas,
        private readonly CatalogoServiciosDirectoryContract $catalogo,
        private readonly ParameterReaderContract $parametros,
        private readonly DuracionTurnoRule $duracion,
    ) {}

    public function resolver(TipoOrigenTurno $tipo, int $id): OrigenProgramacion
    {
        $resumen = $this->origenes->obtener($tipo->value, $id);

        if ($resumen === null) {
            throw new OrigenNoEncontradoException($tipo, $id);
        }

        $patron = null;
        $motivo = null;

        if ($tipo === TipoOrigenTurno::PAQUETE_SESIONES) {
            $patron = new PatronProgramacion(
                diasSemana: $resumen->diasSemana ?? [],
                horaInicio: $this->hora($resumen->horaInicio),
                duracionMinutos: $this->resolverDuracion($resumen->duracionMinutos, $resumen->servicioId),
                profesionalId: $resumen->profesionalId,
            );
        } else {
            try {
                $patronSala = $resumen->salaId === null
                    ? null
                    : $this->salas->patron($resumen->salaId);
            } catch (LogicException) {
                $patronSala = null;
                $motivo = 'El patrón de días y hora de la sala todavía no está configurado.';
            }

            if ($patronSala === null) {
                $motivo ??= 'La sala no tiene un patrón de días y hora configurado.';
            } else {
                $patron = new PatronProgramacion(
                    diasSemana: $patronSala->diasSemana,
                    horaInicio: $this->hora($patronSala->horaInicio),
                    duracionMinutos: $this->resolverDuracion(
                        $patronSala->duracionMinutos,
                        $resumen->servicioId,
                    ),
                    profesionalId: $patronSala->profesionalId,
                );
            }
        }

        return new OrigenProgramacion(
            tipo: $tipo,
            id: $resumen->id,
            sucursalId: $resumen->sucursalId,
            pacienteId: $resumen->pacienteId,
            servicioId: $resumen->servicioId,
            salaId: $resumen->salaId,
            fechaInicio: $resumen->fechaInicio,
            fechaFin: $resumen->fechaFin,
            cantidadSesiones: $resumen->cantidadSesiones,
            estadoEfectivo: EstadoOrigenProgramacion::desdePersistido($resumen->estadoEfectivo),
            patron: $patron,
            patronNoDisponibleMotivo: $motivo,
        );
    }

    private function hora(?string $hora): string
    {
        if ($hora === null || $hora === '') {
            return '00:00';
        }

        return substr($hora, 0, 5);
    }

    private function resolverDuracion(?int $entidad, int $servicioId): int
    {
        $servicio = null;

        try {
            $servicio = $this->catalogo->servicio($servicioId)?->duracionMinutos;
        } catch (Throwable) {
            $servicio = null;
        }

        $configuracion = null;

        try {
            $configuracion = (int) $this->parametros->obtener(self::DURACION_SESION_MINUTOS)->valor;
        } catch (ParameterNotFoundException) {
            $configuracion = null;
        }

        $duracion = $this->duracion->resolver($entidad, $servicio, $configuracion);

        if ($duracion === null) {
            throw new ConfiguracionAgendaIncompletaException(
                'No hay duración configurada para la sesión (paquete, servicio ni parámetro del sistema).',
            );
        }

        return $duracion;
    }
}
