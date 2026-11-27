<?php

namespace App\Modules\InscripcionesAtenciones\Actions;

use App\Models\User;
use App\Modules\InscripcionesAtenciones\Contracts\PacienteEstadoContract;
use App\Modules\InscripcionesAtenciones\Contracts\PacienteEstadoResumen;
use App\Modules\InscripcionesAtenciones\Enums\AccionEstado;
use App\Modules\InscripcionesAtenciones\Enums\MotivoPausa;
use App\Modules\InscripcionesAtenciones\Exceptions\AccionEstadoNoPermitidaException;
use App\Modules\InscripcionesAtenciones\Exceptions\EstadoDesactualizadoException;
use App\Modules\InscripcionesAtenciones\Exceptions\MotivoEstadoInvalidoException;
use App\Modules\InscripcionesAtenciones\Rules\EstadoPacienteTransitionRule;
use App\Modules\InscripcionesAtenciones\Support\EstadoMotivoCatalog;
use App\Modules\Pacientes\Enums\EstadoPaciente;
use App\Modules\Pacientes\Exceptions\PacienteConcurrencyException;
use App\Modules\Pacientes\Support\PacientesPermission;

/**
 * [Estados Paciente e Inscripción] RF-003 / RF-006 / RN-002 / RN-004.
 *
 * Pausa y reactivación. El retiro se delega en RetirarPacienteConCascadaAction.
 */
final class CambiarEstadoPacienteAction
{
    public function __construct(private readonly PacienteEstadoContract $pacientes) {}

    /**
     * @param  array<string, mixed>  $data
     */
    public function __invoke(
        int $pacienteId,
        EstadoPaciente $nuevoEstado,
        array $data,
        User $actor,
        ?string $ip = null,
    ): PacienteEstadoResumen {
        if ($nuevoEstado === EstadoPaciente::RETIRADO) {
            throw new AccionEstadoNoPermitidaException;
        }

        if (! $actor->can(PacientesPermission::PATIENT_CHANGE_STATUS)) {
            throw new AccionEstadoNoPermitidaException;
        }

        $actual = $this->pacientes->resumen($pacienteId, $actor->id);
        $esperado = (string) $data['estado_esperado'];

        if ($esperado !== $actual->estado->value) {
            throw new EstadoDesactualizadoException($actual->estado->value, $esperado, [
                'version_actual' => $actual->version,
            ]);
        }

        EstadoPacienteTransitionRule::assertPermitida($actual->estado, $nuevoEstado);

        $motivo = $this->motivo($actual->estado, $nuevoEstado, $data);

        try {
            return $this->pacientes->cambiarEstado(
                pacienteId: $pacienteId,
                nuevoEstado: $nuevoEstado,
                motivo: $motivo,
                versionEsperada: $actual->version,
                actorId: $actor->id,
                ip: $ip,
            );
        } catch (PacienteConcurrencyException) {
            $fresh = $this->pacientes->resumen($pacienteId, $actor->id);

            throw new EstadoDesactualizadoException($fresh->estado->value, $esperado, [
                'version_actual' => $fresh->version,
            ]);
        }
    }

    /**
     * @param  array<string, mixed>  $data
     */
    private function motivo(EstadoPaciente $from, EstadoPaciente $to, array $data): string
    {
        $codigo = isset($data['motivo']) ? (string) $data['motivo'] : null;
        $texto = isset($data['motivo_detalle']) ? trim((string) $data['motivo_detalle']) : null;

        if ($to === EstadoPaciente::EN_PAUSA) {
            EstadoMotivoCatalog::assertValido(AccionEstado::PAUSAR, null, $codigo, $texto);

            return EstadoMotivoCatalog::compose(AccionEstado::PAUSAR, null, $codigo, $texto);
        }

        if ($from === EstadoPaciente::RETIRADO) {
            if ($texto === null || $texto === '') {
                throw new MotivoEstadoInvalidoException(
                    'Indicá el motivo de la reactivación del paciente.',
                );
            }

            return $texto;
        }

        if ($codigo !== null && $codigo !== '' && ! in_array($codigo, MotivoPausa::values(), true)) {
            throw new MotivoEstadoInvalidoException('El motivo seleccionado no es válido.');
        }

        return EstadoMotivoCatalog::compose(AccionEstado::REACTIVAR, null, null, $texto);
    }
}
