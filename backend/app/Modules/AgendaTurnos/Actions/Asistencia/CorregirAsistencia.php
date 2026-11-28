<?php

namespace App\Modules\AgendaTurnos\Actions\Asistencia;

use App\Models\User;
use App\Modules\AgendaTurnos\Enums\AsistenciaEvento;
use App\Modules\AgendaTurnos\Enums\EstadoTurno;
use App\Modules\AgendaTurnos\Events\AsistenciaCorregida;
use App\Modules\AgendaTurnos\Exceptions\AsistenciaFueraDeVentanaException;
use App\Modules\AgendaTurnos\Exceptions\MotivoAsistenciaRequeridoException;
use App\Modules\AgendaTurnos\Exceptions\TurnoNoAsistibleException;
use App\Modules\AgendaTurnos\Exceptions\TurnoVersionConflictoException;
use App\Modules\AgendaTurnos\Rules\MotivoAsistenciaRule;
use App\Modules\AgendaTurnos\Support\AsistenciaAcceso;
use App\Modules\AgendaTurnos\Support\AsistenciaCalculadora;
use App\Modules\AgendaTurnos\Support\AsistenciaTurnosPermission;
use App\Modules\AgendaTurnos\Support\DiagnosticoEstadoSincronizador;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\DB;

/**
 * 07.04/RF-004, 07.04/RF-005, 07.04/RF-006, 07.04/RF-013,
 * 07.04/RN-004, 07.04/RN-006, 07.04/CU-02, 07.04/CA-005, 07.04/CA-006.
 *
 * Corrección dentro de ventana con el permiso normal; fuera de ventana con
 * `asistencia.corregir_fuera_ventana` y motivo obligatorio. La ventana corre
 * desde el primer registro y no se reinicia con cada corrección.
 */
final class CorregirAsistencia
{
    public function __construct(
        private readonly AsistenciaAcceso $acceso,
        private readonly AsistenciaCalculadora $calculadora,
        private readonly MotivoAsistenciaRule $motivo,
        private readonly DiagnosticoEstadoSincronizador $diagnosticos,
    ) {}

    /**
     * @return array<string, mixed>
     */
    public function __invoke(
        int $turnoId,
        EstadoTurno $destino,
        User $actor,
        int $version,
        ?string $motivo = null,
        ?string $ip = null,
    ): array {
        return DB::transaction(function () use (
            $turnoId,
            $destino,
            $actor,
            $version,
            $motivo,
            $ip,
        ): array {
            $turno = $this->acceso->turnoBloqueado($turnoId, $actor);

            $erroresCorreccion = $this->calculadora->erroresCorreccion($turno, $destino);

            if ($erroresCorreccion !== []) {
                throw new TurnoNoAsistibleException(
                    $turno->estado,
                    $erroresCorreccion[0],
                );
            }

            if ($turno->estado === $destino) {
                return [
                    'turno' => $this->calculadora->datos($turno),
                    'sin_cambios' => true,
                    'accion' => 'sin_cambios',
                    'fuera_ventana' => false,
                ];
            }

            if ($version !== (int) ($turno->version ?? 1)) {
                throw new TurnoVersionConflictoException(
                    $turno->estado,
                    (int) ($turno->version ?? 1),
                );
            }

            if (! in_array($turno->estado, [EstadoTurno::ASISTIO, EstadoTurno::FALTO], true)) {
                throw new TurnoNoAsistibleException(
                    $turno->estado,
                    'El turno todavía no tiene asistencia registrada.',
                );
            }

            $ventana = $this->calculadora->ventana($turno);
            $fueraVentana = ! $ventana['dentro'];

            if ($fueraVentana && ! $actor->can(AsistenciaTurnosPermission::CORREGIR_FUERA_VENTANA)) {
                throw new AsistenciaFueraDeVentanaException(
                    $ventana['horas'],
                    $ventana['vence_en'],
                );
            }

            $erroresMotivo = $this->motivo->errores($fueraVentana, $motivo);

            if ($erroresMotivo !== []) {
                throw new MotivoAsistenciaRequeridoException;
            }

            $motivoNormalizado = $this->motivo->normalizar($motivo);
            $anterior = $turno->estado;
            $evento = $fueraVentana
                ? AsistenciaEvento::CORRECCION_FUERA_VENTANA
                : AsistenciaEvento::CORRECCION;

            $turno->forceFill([
                'estado' => $destino->value,
                'version' => ((int) ($turno->version ?? 1)) + 1,
            ])->save();

            if ($turno->atencion_diagnostico_id !== null) {
                $this->diagnosticos->sincronizar(
                    (int) $turno->atencion_diagnostico_id,
                    $actor,
                    $ip,
                );
            }

            $origen = $this->calculadora->origen($turno);
            $origenTipo = $origen?->tipo
                ?? ($this->calculadora->esDiagnostico($turno) ? 'diagnostico' : null);

            $registradaEn = $turno->asistencia_registrada_en
                ?? CarbonImmutable::now();

            event(new AsistenciaCorregida(
                turnoId: (int) $turno->id,
                sucursalId: (int) $turno->sucursal_id,
                pacienteId: (int) $turno->paciente_id,
                estadoAnterior: $anterior,
                estadoNuevo: $destino,
                origenTipo: $origenTipo,
                evento: $evento,
                fueraVentana: $fueraVentana,
                motivo: $motivoNormalizado,
                actorId: (int) $actor->id,
                ip: $ip,
                version: (int) $turno->version,
                asistenciaRegistradaEn: CarbonImmutable::instance($registradaEn)->toIso8601String(),
            ));

            return [
                'turno' => $this->calculadora->datos($turno),
                'sin_cambios' => false,
                'accion' => 'correccion',
                'fuera_ventana' => $fueraVentana,
            ];
        });
    }
}
