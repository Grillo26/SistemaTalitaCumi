<?php

namespace App\Modules\AgendaTurnos\Rules;

use App\Modules\AgendaTurnos\Enums\EstadoGeneralDiagnostico;
use App\Modules\AgendaTurnos\Support\EstadoGeneralResultado;

/**
 * 07.05/RF-005 / 07.05/RN-004 / 07.05/RN-005.
 *
 * Regla pura: recibe requisitos, turnos y especialidades ya materializados.
 *
 * - cancelado: solo se decide fuera de la regla (cancelación manual).
 * - completo: todos los puestos requeridos tienen al menos un turno `asistio`.
 * - en_proceso: todos los puestos tienen turno `programado` o `asistio`, pero
 *   no todos asistieron.
 * - incompleto: al menos un puesto no tiene turno vigente. Un turno `falto`,
 *   `cancelado` o `reprogramado` deja el puesto descubierto.
 *
 * Un puesto se cubre con profesionales distintos que tengan la especialidad
 * requerida. La cantidad mínima se respeta por especialidad.
 */
final class EstadoGeneralDiagnosticoRule
{
    private const ESTADOS_VIGENTES = ['programado', 'asistio'];

    /**
     * @param  list<array{especialidad_id: int, especialidad_nombre: string, cantidad_minima: int}>  $requisitos
     * @param  list<array{id: int, profesional_id: int|null, estado: string}>  $turnos
     * @param  array<int, list<int>>  $especialidadesPorProfesional
     */
    public function evaluar(
        array $requisitos,
        array $turnos,
        array $especialidadesPorProfesional,
    ): EstadoGeneralResultado {
        if ($requisitos === []) {
            return new EstadoGeneralResultado(
                estado: EstadoGeneralDiagnostico::INCOMPLETO,
                puestos: [],
                faltantes: [[
                    'tipo' => 'sin_requisitos',
                    'especialidad_id' => 0,
                    'mensaje' => 'El tipo de diagnóstico no tiene especialidades requeridas; no se puede calcular la cobertura.',
                ]],
            );
        }

        $vigentes = [];
        $asistidos = [];

        foreach ($turnos as $turno) {
            $profesionalId = $turno['profesional_id'];

            if ($profesionalId === null || ! in_array($turno['estado'], self::ESTADOS_VIGENTES, true)) {
                continue;
            }

            foreach ($especialidadesPorProfesional[$profesionalId] ?? [] as $especialidadId) {
                $vigentes[$especialidadId][$profesionalId] = true;

                if ($turno['estado'] === 'asistio') {
                    $asistidos[$especialidadId][$profesionalId] = true;
                }
            }
        }

        $puestos = [];
        $faltantes = [];
        $todosVigentes = true;
        $todosAsistidos = true;

        foreach ($requisitos as $requisito) {
            $especialidadId = (int) $requisito['especialidad_id'];
            $requeridos = max(1, (int) $requisito['cantidad_minima']);
            $vigentesEspecialidad = count($vigentes[$especialidadId] ?? []);
            $asistidosEspecialidad = count($asistidos[$especialidadId] ?? []);
            $cubierto = $vigentesEspecialidad >= $requeridos;

            $todosVigentes = $todosVigentes && $cubierto;
            $todosAsistidos = $todosAsistidos && $asistidosEspecialidad >= $requeridos;

            if (! $cubierto) {
                $faltantes[] = [
                    'tipo' => 'sin_turno',
                    'especialidad_id' => $especialidadId,
                    'mensaje' => sprintf(
                        'Falta turno de %s: %d de %d cubiertos.',
                        $requisito['especialidad_nombre'],
                        $vigentesEspecialidad,
                        $requeridos,
                    ),
                ];
            } elseif ($asistidosEspecialidad < $requeridos) {
                $faltantes[] = [
                    'tipo' => 'sin_asistencia',
                    'especialidad_id' => $especialidadId,
                    'mensaje' => sprintf(
                        'Falta registrar asistencia de %s: %d de %d asistieron.',
                        $requisito['especialidad_nombre'],
                        $asistidosEspecialidad,
                        $requeridos,
                    ),
                ];
            }

            $puestos[] = [
                'especialidad_id' => $especialidadId,
                'especialidad_nombre' => (string) $requisito['especialidad_nombre'],
                'cantidad_minima' => $requeridos,
                'asistidos' => $asistidosEspecialidad,
                'vigentes' => $vigentesEspecialidad,
                'cubierto' => $cubierto,
            ];
        }

        $estado = $todosAsistidos
            ? EstadoGeneralDiagnostico::COMPLETO
            : ($todosVigentes
                ? EstadoGeneralDiagnostico::EN_PROCESO
                : EstadoGeneralDiagnostico::INCOMPLETO);

        return new EstadoGeneralResultado($estado, $puestos, $faltantes);
    }
}
