<?php

namespace App\Modules\AgendaTurnos\Actions\Diagnosticos;

use App\Models\User;
use App\Modules\AgendaTurnos\Enums\EstadoGeneralDiagnostico;
use App\Modules\AgendaTurnos\Events\EstadoGeneralDiagnosticoCambiado;
use App\Modules\AgendaTurnos\Exceptions\CierreDiagnosticoIncompletoException;
use App\Modules\AgendaTurnos\Exceptions\DiagnosticoNoCoordinableException;
use App\Modules\AgendaTurnos\Exceptions\DiagnosticoNotFoundException;
use App\Modules\AgendaTurnos\Exceptions\DiagnosticoYaCanceladoException;
use App\Modules\AgendaTurnos\Exceptions\EstadoGeneralInvalidoException;
use App\Modules\AgendaTurnos\Rules\EstadoGeneralDiagnosticoRule;
use App\Modules\AgendaTurnos\Support\AgendaAccessScope;
use App\Modules\AgendaTurnos\Support\DiagnosticoTurnosResolver;
use App\Modules\InscripcionesAtenciones\Contracts\DiagnosticoAtencionContract;
use App\Modules\InscripcionesAtenciones\Contracts\DiagnosticoAtencionResumen;
use Illuminate\Support\Facades\DB;

/**
 * 07.05/RF-005 / 07.05/RF-007 / 07.05/RF-009 / 07.05/RF-012 /
 * 07.05/RN-004 / 07.05/RN-005 / 07.05/CU-02 / 07.05/CA-003.
 *
 * Actualización manual del estado general. No permite contradecir la realidad
 * de los turnos: "completo" exige todas las asistencias, "en_proceso" exige
 * todos los puestos cubiertos e "incompleto" exige al menos uno descubierto.
 * La cancelación tiene su propia Action.
 */
final class ActualizarEstadoGeneralDiagnostico
{
    public function __construct(
        private readonly DiagnosticoAtencionContract $diagnosticos,
        private readonly AgendaAccessScope $access,
        private readonly EstadoGeneralDiagnosticoRule $rule,
        private readonly DiagnosticoTurnosResolver $turnos,
    ) {}

    /**
     * @return array<string, mixed>
     */
    public function __invoke(
        int $diagnosticoId,
        EstadoGeneralDiagnostico $solicitado,
        string $motivo,
        User $actor,
        ?string $ip = null,
    ): array {
        return DB::transaction(function () use ($diagnosticoId, $solicitado, $motivo, $actor, $ip): array {
            $resumen = $this->diagnosticos->resumenParaActualizar($diagnosticoId);

            if ($resumen === null || ! in_array($resumen->sucursalId, $this->access->accesibles($actor), true)) {
                throw new DiagnosticoNotFoundException($diagnosticoId);
            }

            $this->assertCoordinable($resumen);

            $turnos = $this->turnos->delDiagnostico($diagnosticoId);
            $resultado = $this->rule->evaluar(
                $resumen->requisitos,
                $this->turnos->paraRegla($turnos),
                $this->turnos->especialidadesPorProfesional($resumen),
            );

            $this->assertPermitido($solicitado, $resultado->estado, $resultado->faltantes);

            $anterior = EstadoGeneralDiagnostico::tryFrom($resumen->estadoGeneral)
                ?? EstadoGeneralDiagnostico::INCOMPLETO;

            if ($anterior === $solicitado) {
                return [
                    'diagnostico_id' => $diagnosticoId,
                    'estado_general' => $solicitado->value,
                    'estado_general_label' => $solicitado->label(),
                    'sin_cambios' => true,
                    'faltantes' => $resultado->faltantes,
                ];
            }

            $this->diagnosticos->actualizarEstadoGeneral($diagnosticoId, $solicitado->value);

            event(new EstadoGeneralDiagnosticoCambiado(
                diagnosticoId: $diagnosticoId,
                sucursalId: $resumen->sucursalId,
                estadoAnterior: $anterior,
                estadoNuevo: $solicitado,
                faltantes: $resultado->faltantes,
                actorId: (int) $actor->id,
                ip: $ip,
                motivo: $motivo,
            ));

            return [
                'diagnostico_id' => $diagnosticoId,
                'estado_general' => $solicitado->value,
                'estado_general_label' => $solicitado->label(),
                'sin_cambios' => false,
                'faltantes' => $resultado->faltantes,
            ];
        });
    }

    private function assertCoordinable(DiagnosticoAtencionResumen $resumen): void
    {
        if ($resumen->estadoGeneral === EstadoGeneralDiagnostico::CANCELADO->value) {
            throw new DiagnosticoYaCanceladoException;
        }

        if ($resumen->estado !== 'activa') {
            throw new DiagnosticoNoCoordinableException($resumen->estado, $resumen->estadoGeneral);
        }
    }

    /**
     * @param  list<array{tipo: string, especialidad_id: int, mensaje: string}>  $faltantes
     */
    private function assertPermitido(
        EstadoGeneralDiagnostico $solicitado,
        EstadoGeneralDiagnostico $calculado,
        array $faltantes,
    ): void {
        if ($solicitado === EstadoGeneralDiagnostico::COMPLETO && $calculado !== EstadoGeneralDiagnostico::COMPLETO) {
            throw new CierreDiagnosticoIncompletoException($faltantes);
        }

        if ($solicitado === EstadoGeneralDiagnostico::EN_PROCESO
            && $calculado !== EstadoGeneralDiagnostico::EN_PROCESO) {
            throw new EstadoGeneralInvalidoException(
                $solicitado->value,
                $calculado->value,
                $faltantes,
            );
        }

        if ($solicitado === EstadoGeneralDiagnostico::INCOMPLETO
            && $calculado !== EstadoGeneralDiagnostico::INCOMPLETO) {
            throw new EstadoGeneralInvalidoException(
                $solicitado->value,
                $calculado->value,
                $faltantes,
            );
        }
    }
}
