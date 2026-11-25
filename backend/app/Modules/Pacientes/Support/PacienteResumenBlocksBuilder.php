<?php

namespace App\Modules\Pacientes\Support;

use App\Models\User;
use App\Modules\Pacientes\Contracts\PatientAttendanceSummaryContract;
use App\Modules\Pacientes\Contracts\PatientPaymentAlertContract;
use App\Modules\Pacientes\Contracts\PatientPaymentStatusContract;
use App\Modules\Pacientes\Contracts\PatientServicesSummaryContract;
use App\Modules\Pacientes\Models\Paciente;
use Illuminate\Support\Facades\Log;
use Throwable;

/**
 * Construye los bloques de indicadores de la ficha resumen (04.04 Fase B).
 *
 * Cada contrato se consulta solo si `disponible()` es true. Un error de un
 * módulo de origen degrada su bloque a `no_disponible/ERROR`; nunca produce
 * un 500 ni un cero inventado. No se cachean indicadores (04.04/RT-001).
 */
final class PacienteResumenBlocksBuilder
{
    public function __construct(
        private readonly PatientServicesSummaryContract $services,
        private readonly PatientPaymentStatusContract $payments,
        private readonly PatientAttendanceSummaryContract $attendance,
        private readonly PatientPaymentAlertContract $alerts,
    ) {}

    /**
     * @param  list<int>|null  $sucursalesAccesibles
     * @return array{bloques: array<string, array<string, mixed>>, fecha_corte: ?string}
     */
    public function indicators(
        Paciente $paciente,
        User $actor,
        ?array $sucursalesAccesibles,
        ?int $sucursalContexto,
    ): array {
        $servicios = $this->servicios($paciente->id, $sucursalesAccesibles, $sucursalContexto);
        $pago = $this->pago($paciente->id, $sucursalesAccesibles, $sucursalContexto);

        return [
            'bloques' => [
                'servicios_vigentes' => $servicios['servicios']->toArray(),
                'sucursales' => $servicios['sucursales']->toArray(),
                'estado_pago' => $pago['estado']->toArray(),
                'proximo_vencimiento' => $pago['vencimiento']->toArray(),
                'asistencia' => $this->asistencia($paciente->id, $sucursalesAccesibles, $sucursalContexto)->toArray(),
                'alerta_pago' => $this->alerta($paciente->id, $actor, $sucursalesAccesibles)->toArray(),
            ],
            'fecha_corte' => $pago['fecha_corte'],
        ];
    }

    /**
     * @param  list<int>|null  $sucursalesAccesibles
     * @return array{servicios: PacienteResumenBlock, sucursales: PacienteResumenBlock}
     */
    private function servicios(
        int $pacienteId,
        ?array $sucursalesAccesibles,
        ?int $sucursalContexto,
    ): array {
        if (! $this->services->disponible()) {
            return [
                'servicios' => PacienteResumenBlock::noDisponible(
                    PacienteResumenMotivo::MODULO_NO_IMPLEMENTADO,
                    'Los servicios vigentes estarán disponibles con Inscripciones (módulo 06).',
                ),
                'sucursales' => PacienteResumenBlock::noDisponible(
                    PacienteResumenMotivo::MODULO_NO_IMPLEMENTADO,
                    'Las sucursales con servicios estarán disponibles con Inscripciones (módulo 06).',
                ),
            ];
        }

        try {
            $items = $this->services->serviciosVigentes(
                $pacienteId,
                $sucursalesAccesibles,
                $sucursalContexto,
            );
        } catch (Throwable $exception) {
            $this->logError('servicios_vigentes', $exception);

            return [
                'servicios' => PacienteResumenBlock::noDisponible(
                    PacienteResumenMotivo::ERROR,
                    'No se pudieron obtener los servicios vigentes.',
                ),
                'sucursales' => PacienteResumenBlock::noDisponible(
                    PacienteResumenMotivo::ERROR,
                    'No se pudieron obtener las sucursales con servicios.',
                ),
            ];
        }

        if ($items === []) {
            return [
                'servicios' => PacienteResumenBlock::noDisponible(
                    PacienteResumenMotivo::SIN_SERVICIOS_VIGENTES,
                    'El paciente no tiene servicios vigentes.',
                ),
                'sucursales' => PacienteResumenBlock::noDisponible(
                    PacienteResumenMotivo::SIN_SERVICIOS_VIGENTES,
                    'El paciente no tiene sucursales con servicios vigentes.',
                ),
            ];
        }

        $sucursales = [];

        foreach ($items as $item) {
            $sucursales[$item->sucursalId] = [
                'id' => $item->sucursalId,
                'nombre' => $item->sucursalNombre,
            ];
        }

        return [
            'servicios' => PacienteResumenBlock::disponible(
                'Servicios vigentes.',
                ['items' => array_map(static fn ($item): array => $item->toArray(), $items)],
            ),
            'sucursales' => PacienteResumenBlock::disponible(
                'Sucursales con servicios vigentes.',
                ['items' => array_values($sucursales)],
            ),
        ];
    }

    /**
     * @param  list<int>|null  $sucursalesAccesibles
     * @return array{estado: PacienteResumenBlock, vencimiento: PacienteResumenBlock, fecha_corte: ?string}
     */
    private function pago(
        int $pacienteId,
        ?array $sucursalesAccesibles,
        ?int $sucursalContexto,
    ): array {
        if (! $this->payments->disponible()) {
            return [
                'estado' => PacienteResumenBlock::noDisponible(
                    PacienteResumenMotivo::MODULO_NO_IMPLEMENTADO,
                    'El estado de pago estará disponible con Pagos y caja (módulo 08).',
                ),
                'vencimiento' => PacienteResumenBlock::noDisponible(
                    PacienteResumenMotivo::MODULO_NO_IMPLEMENTADO,
                    'El próximo vencimiento estará disponible con Inscripciones y Pagos (06/08).',
                ),
                'fecha_corte' => null,
            ];
        }

        try {
            $estado = $this->payments->estadoPago(
                $pacienteId,
                $sucursalesAccesibles,
                $sucursalContexto,
            );
        } catch (Throwable $exception) {
            $this->logError('estado_pago', $exception);

            return [
                'estado' => PacienteResumenBlock::noDisponible(
                    PacienteResumenMotivo::ERROR,
                    'No se pudo obtener el estado de pago.',
                ),
                'vencimiento' => PacienteResumenBlock::noDisponible(
                    PacienteResumenMotivo::ERROR,
                    'No se pudo obtener el próximo vencimiento.',
                ),
                'fecha_corte' => null,
            ];
        }

        if ($estado->estado === 'sin_servicios') {
            return [
                'estado' => PacienteResumenBlock::noDisponible(
                    PacienteResumenMotivo::SIN_SERVICIOS_VIGENTES,
                    'El paciente no tiene un período vigente para calcular el pago.',
                ),
                'vencimiento' => PacienteResumenBlock::noDisponible(
                    PacienteResumenMotivo::SIN_SERVICIOS_VIGENTES,
                    'El paciente no tiene un período vigente con vencimiento.',
                ),
                'fecha_corte' => $estado->fechaCorte,
            ];
        }

        return [
            'estado' => PacienteResumenBlock::disponible('Estado de pago.', $estado->toArray()),
            'vencimiento' => $estado->proximoVencimiento === null
                ? PacienteResumenBlock::disponible('Sin vencimiento registrado.', ['fecha' => null])
                : PacienteResumenBlock::disponible('Próximo vencimiento.', [
                    'fecha' => $estado->proximoVencimiento,
                ]),
            'fecha_corte' => $estado->fechaCorte,
        ];
    }

    /**
     * @param  list<int>|null  $sucursalesAccesibles
     */
    private function asistencia(
        int $pacienteId,
        ?array $sucursalesAccesibles,
        ?int $sucursalContexto,
    ): PacienteResumenBlock {
        if (! $this->attendance->disponible()) {
            return PacienteResumenBlock::noDisponible(
                PacienteResumenMotivo::MODULO_NO_IMPLEMENTADO,
                'El resumen de asistencia estará disponible con Agenda y asistencia (módulo 07).',
            );
        }

        try {
            $resumen = $this->attendance->resumenAsistencia(
                $pacienteId,
                $sucursalesAccesibles,
                $sucursalContexto,
            );
        } catch (Throwable $exception) {
            $this->logError('asistencia', $exception);

            return PacienteResumenBlock::noDisponible(
                PacienteResumenMotivo::ERROR,
                'No se pudo obtener el resumen de asistencia.',
            );
        }

        if ($resumen->periodoInicio === null || $resumen->periodoFin === null) {
            return PacienteResumenBlock::noDisponible(
                PacienteResumenMotivo::SIN_SERVICIOS_VIGENTES,
                'El paciente no tiene un período vigente para calcular la asistencia.',
            );
        }

        return PacienteResumenBlock::disponible('Resumen de asistencia.', $resumen->toArray());
    }

    /**
     * @param  list<int>|null  $sucursalesAccesibles
     */
    private function alerta(
        int $pacienteId,
        User $actor,
        ?array $sucursalesAccesibles,
    ): PacienteResumenBlock {
        if (! $this->alerts->disponible()) {
            return PacienteResumenBlock::noDisponible(
                PacienteResumenMotivo::MODULO_NO_IMPLEMENTADO,
                'La alerta de próximo pago estará disponible con Alertas (módulo 09) y 04.02 Fase C.',
            );
        }

        if (! $actor->can(PacientesPermission::TUTOR_VIEW_CONTACT)) {
            return PacienteResumenBlock::noDisponible(
                PacienteResumenMotivo::SIN_PERMISO,
                'No tenés permiso para ver el contacto del tutor.',
            );
        }

        try {
            $alerta = $this->alerts->generarAlerta($pacienteId, $sucursalesAccesibles);
        } catch (Throwable $exception) {
            $this->logError('alerta_pago', $exception);

            return PacienteResumenBlock::noDisponible(
                PacienteResumenMotivo::ERROR,
                'No se pudo generar la alerta de próximo pago.',
            );
        }

        if (trim($alerta->telefono) === '') {
            return PacienteResumenBlock::noDisponible(
                PacienteResumenMotivo::SIN_TUTOR,
                'El tutor principal no tiene un teléfono válido.',
            );
        }

        return PacienteResumenBlock::disponible('Alerta de próximo pago.', $alerta->toArray());
    }

    private function logError(string $bloque, Throwable $exception): void
    {
        Log::warning('Paciente resumen: bloque no disponible.', [
            'bloque' => $bloque,
            'exception' => $exception::class,
        ]);
    }
}
