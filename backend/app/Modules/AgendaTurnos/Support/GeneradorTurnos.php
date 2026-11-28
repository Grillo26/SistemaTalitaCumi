<?php

namespace App\Modules\AgendaTurnos\Support;

use App\Modules\AgendaTurnos\Enums\SeveridadHallazgo;
use App\Modules\AgendaTurnos\Rules\CantidadRestanteRule;
use App\Modules\AgendaTurnos\Rules\GeneradorFechasRule;
use App\Modules\AgendaTurnos\Rules\ResumenGeneracionRule;
use App\Modules\AgendaTurnos\Rules\SesionGrupalRule;
use App\Modules\AgendaTurnos\Rules\TurnoSolapamientoRule;

/**
 * Cálculo compartido por la previsualización (Query) y la confirmación
 * (Action). No accede a la base de datos: recibe la entidad origen, los
 * turnos existentes y los feriados ya resueltos.
 */
final class GeneradorTurnos
{
    public function __construct(
        private readonly GeneradorFechasRule $fechas,
        private readonly TurnoSolapamientoRule $solapamiento,
        private readonly ResumenGeneracionRule $resumen,
        private readonly CantidadRestanteRule $cantidadRestante,
    ) {}

    /**
     * @param  list<TurnoExistente>  $existentes
     * @param  array<string, string>  $feriados
     * @param  list<int>  $excluirTurnoIds
     */
    public function calcular(
        OrigenProgramacion $origen,
        string $hoy,
        string $horaActual,
        array $existentes = [],
        array $feriados = [],
        bool $confirmarAdvertencias = false,
        array $excluirTurnoIds = [],
        ?int $cantidadObjetivo = null,
    ): GeneracionCalculada {
        $patron = $origen->patron;
        $hallazgos = [];

        if ($patron === null) {
            $hallazgos[] = new Hallazgo(
                tipo: 'patron_no_disponible',
                severidad: SeveridadHallazgo::BLOQUEO,
                mensaje: $origen->patronNoDisponibleMotivo
                    ?? 'La entidad origen no tiene un patrón de días y hora configurado.',
            );

            return new GeneracionCalculada(
                filas: [],
                resumen: $this->resumen->resumir([], $confirmarAdvertencias),
                hallazgos: $hallazgos,
                cantidadObjetivo: $cantidadObjetivo ?? $origen->cantidadSesiones,
                cantidadGenerable: 0,
            );
        }

        $objetivo = $cantidadObjetivo ?? $origen->cantidadSesiones;
        $horaFin = $patron->horaFin();

        $fechas = $this->fechas->generar(
            diasSemana: $patron->diasSemana,
            fechaInicio: $origen->fechaInicio,
            fechaFin: $origen->fechaFin,
            hoy: $hoy,
            horaActual: $horaActual,
            horaInicio: $patron->horaInicio,
            cantidadMaxima: $objetivo,
            feriados: $feriados,
        );

        $filas = [];
        $disponibles = 0;

        foreach ($fechas as $fecha) {
            $fila = new FilaPreview(
                fecha: $fecha->fecha,
                horaInicio: $patron->horaInicio,
                horaFin: $horaFin,
                estado: $fecha->estado,
                generable: $fecha->esGenerable(),
            );

            if ($fecha->motivo !== null) {
                $fila->agregarHallazgo(new Hallazgo(
                    tipo: $fecha->estado,
                    severidad: SeveridadHallazgo::INFORMATIVO,
                    mensaje: $fecha->motivo,
                    fecha: $fecha->fecha,
                    horaInicio: $patron->horaInicio,
                ));
            }

            if ($fecha->esGenerable()) {
                $disponibles++;

                $candidato = new TurnoCandidato(
                    pacienteId: $origen->pacienteId,
                    profesionalId: $patron->profesionalId,
                    salaId: $origen->salaId,
                    servicioId: $origen->servicioId,
                    fecha: $fecha->fecha,
                    horaInicio: $patron->horaInicio,
                    horaFin: $horaFin,
                    sesionKey: SesionGrupalRule::clave(
                        $origen->salaId,
                        $fecha->fecha,
                        $patron->horaInicio,
                        $horaFin,
                        $origen->servicioId,
                    ),
                );

                foreach ($this->solapamiento->evaluar($candidato, $existentes, $excluirTurnoIds) as $hallazgo) {
                    $fila->agregarHallazgo($hallazgo);
                }
            }

            $filas[] = $fila;
        }

        $faltantes = $objetivo === null
            ? 0
            : $this->cantidadRestante->faltantes($objetivo, $disponibles);

        if ($faltantes > 0) {
            $hallazgos[] = new Hallazgo(
                tipo: 'faltantes',
                severidad: SeveridadHallazgo::ADVERTENCIA,
                mensaje: "El período solo permite generar {$disponibles} de {$objetivo} sesiones. Faltan {$faltantes}.",
            );
        }

        return new GeneracionCalculada(
            filas: $filas,
            resumen: $this->resumen->resumir($filas, $confirmarAdvertencias, $faltantes),
            hallazgos: $hallazgos,
            cantidadObjetivo: $objetivo,
            cantidadGenerable: $disponibles,
        );
    }
}
