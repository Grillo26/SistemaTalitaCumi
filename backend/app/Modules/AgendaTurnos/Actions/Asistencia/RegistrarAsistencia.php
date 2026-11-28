<?php

namespace App\Modules\AgendaTurnos\Actions\Asistencia;

use App\Models\User;
use App\Modules\AgendaTurnos\Enums\EstadoTurno;
use App\Modules\AgendaTurnos\Events\AsistenciaRegistrada;
use App\Modules\AgendaTurnos\Exceptions\AsistenciaYaRegistradaException;
use App\Modules\AgendaTurnos\Exceptions\TurnoNoAsistibleException;
use App\Modules\AgendaTurnos\Exceptions\TurnoVersionConflictoException;
use App\Modules\AgendaTurnos\Models\Turno;
use App\Modules\AgendaTurnos\Rules\TransicionAsistenciaRule;
use App\Modules\AgendaTurnos\Support\AsistenciaAcceso;
use App\Modules\AgendaTurnos\Support\AsistenciaCalculadora;
use App\Modules\AgendaTurnos\Support\AsistenciaClock;
use App\Modules\AgendaTurnos\Support\DiagnosticoEstadoSincronizador;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\DB;

/**
 * 07.04/RF-001, 07.04/RF-002, 07.04/RF-003, 07.04/RF-007, 07.04/RF-008,
 * 07.04/RF-011, 07.04/RF-012, 07.04/RN-001, 07.04/RN-002, 07.04/RN-005,
 * 07.04/RN-007, 07.04/CU-01, 07.04/CA-001, 07.04/CA-002, 07.04/CA-003,
 * 07.04/CA-004, 07.04/CA-010.
 *
 * Una transacción: lock del turno, estado esperado, elegibilidad, cambio de
 * estado, evento y bitácora fail-closed. El saldo es derivado: no se escribe
 * ningún contador de 06.02.
 */
final class RegistrarAsistencia
{
    public function __construct(
        private readonly AsistenciaAcceso $acceso,
        private readonly AsistenciaCalculadora $calculadora,
        private readonly TransicionAsistenciaRule $transicion,
        private readonly AsistenciaClock $clock,
        private readonly DiagnosticoEstadoSincronizador $diagnosticos,
    ) {}

    /**
     * @return array<string, mixed>
     */
    public function __invoke(
        int $turnoId,
        EstadoTurno $destino,
        User $actor,
        ?int $version = null,
        ?string $ip = null,
    ): array {
        return DB::transaction(function () use ($turnoId, $destino, $actor, $version, $ip): array {
            $turno = $this->acceso->turnoBloqueado($turnoId, $actor);

            if ($this->transicion->esNoOp($turno->estado, $destino)) {
                return $this->resultado($turno, true);
            }

            if ($turno->estado === EstadoTurno::ASISTIO || $turno->estado === EstadoTurno::FALTO) {
                throw new AsistenciaYaRegistradaException(
                    $turno->estado,
                    (int) ($turno->version ?? 1),
                );
            }

            if ($version !== null && $version !== (int) ($turno->version ?? 1)) {
                throw new TurnoVersionConflictoException(
                    $turno->estado,
                    (int) ($turno->version ?? 1),
                );
            }

            $erroresTransicion = $this->transicion->errores($turno->estado, $destino, false);

            if ($erroresTransicion !== []) {
                throw new TurnoNoAsistibleException($turno->estado, $erroresTransicion[0]);
            }

            $errores = $this->calculadora->erroresRegistro($turno);

            if ($errores !== []) {
                throw new TurnoNoAsistibleException($turno->estado, $errores[0]);
            }

            $anterior = $turno->estado;

            $registradaEn = $turno->asistencia_registrada_en
                ?? $this->clock->ahora();

            $turno->forceFill([
                'estado' => $destino->value,
                'usuario_asistencia_id' => $turno->usuario_asistencia_id ?? $actor->id,
                'asistencia_registrada_en' => $registradaEn,
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

            event(new AsistenciaRegistrada(
                turnoId: (int) $turno->id,
                sucursalId: (int) $turno->sucursal_id,
                pacienteId: (int) $turno->paciente_id,
                estadoAnterior: $anterior,
                estadoNuevo: $destino,
                origenTipo: $origenTipo,
                actorId: (int) $actor->id,
                ip: $ip,
                version: (int) $turno->version,
                asistenciaRegistradaEn: CarbonImmutable::instance($registradaEn)->toIso8601String(),
            ));

            return $this->resultado($turno, false);
        });
    }

    /**
     * @return array<string, mixed>
     */
    private function resultado(Turno $turno, bool $sinCambios): array
    {
        return [
            'turno' => $this->calculadora->datos($turno),
            'sin_cambios' => $sinCambios,
            'accion' => $sinCambios ? 'sin_cambios' : 'registro',
        ];
    }
}
