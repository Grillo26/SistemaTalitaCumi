<?php

namespace App\Modules\InscripcionesAtenciones\Queries;

use App\Models\User;
use App\Modules\InscripcionesAtenciones\Contracts\PacienteEstadoContract;
use App\Modules\InscripcionesAtenciones\Enums\TipoEntidadVigencia;
use App\Modules\InscripcionesAtenciones\Exceptions\RetiroFueraDeSucursalException;
use App\Modules\InscripcionesAtenciones\Models\InscripcionSala;
use App\Modules\InscripcionesAtenciones\Models\PaqueteSesiones;
use App\Modules\InscripcionesAtenciones\Rules\RetiroEnCascadaRule;
use App\Modules\InscripcionesAtenciones\Support\EstadoInscripcionResolver;
use App\Modules\InscripcionesAtenciones\Support\InscripcionQueryJoins;
use App\Modules\InscripcionesAtenciones\Support\PaqueteQueryJoins;
use App\Modules\InscripcionesAtenciones\Support\VigenciaPeriod;
use App\Modules\Organizacion\Contracts\UserBranchAccessContract;

/**
 * [Estados Paciente e Inscripción] RF-005 / CU-02 / CA-006.
 *
 * Muestra exactamente las inscripciones Activas/En pausa que se arrastrarán.
 * Vencidas, Renovadas y Retiradas no se incluyen.
 */
final class PreviewRetiroPacienteQuery
{
    public function __construct(
        private readonly PacienteEstadoContract $pacientes,
        private readonly UserBranchAccessContract $access,
    ) {}

    /**
     * @return array<string, mixed>
     */
    public function __invoke(int $pacienteId, User $actor): array
    {
        $paciente = $this->pacientes->resumen($pacienteId, $actor->id);
        $hoy = VigenciaPeriod::today();
        $inscripciones = [];
        $faltantes = [];

        $salas = InscripcionQueryJoins::apply(InscripcionSala::query())
            ->where('inscripcion_sala.paciente_id', $pacienteId)
            ->whereIn('inscripcion_sala.estado', ['activa', 'en_pausa'])
            ->get();

        foreach ($salas as $sala) {
            $efectivo = EstadoInscripcionResolver::efectivoSala($sala, $hoy);

            if (! RetiroEnCascadaRule::arrastra($efectivo)) {
                continue;
            }

            if (! $this->access->puedeAcceder($actor->id, (int) $sala->sucursal_id)) {
                $faltantes[] = (int) $sala->sucursal_id;

                continue;
            }

            $inscripciones[] = [
                'tipo_entidad' => TipoEntidadVigencia::INSCRIPCION_SALA->value,
                'tipo_entidad_label' => TipoEntidadVigencia::INSCRIPCION_SALA->label(),
                'id' => (int) $sala->id,
                'paciente' => [
                    'id' => (int) $sala->paciente_id,
                    'nombre_completo' => $this->patientName($sala),
                ],
                'sucursal' => [
                    'id' => (int) $sala->sucursal_id,
                    'nombre' => (string) ($sala->sucursal_nombre ?? ''),
                ],
                'servicio' => [
                    'id' => (int) $sala->servicio_id,
                    'nombre' => (string) ($sala->servicio_nombre ?? ''),
                ],
                'sala' => $sala->sala_nombre === null ? null : [
                    'id' => (int) $sala->sala_id,
                    'nombre' => (string) $sala->sala_nombre,
                ],
                'fecha_inicio' => $sala->fecha_inicio->toDateString(),
                'fecha_fin' => $sala->fecha_fin->toDateString(),
                'estado_efectivo' => $efectivo->value,
                'estado_efectivo_label' => $efectivo->label(),
            ];
        }

        $paquetes = PaqueteQueryJoins::apply(PaqueteSesiones::query())
            ->where('paquete_sesiones.paciente_id', $pacienteId)
            ->whereIn('paquete_sesiones.estado', ['activo', 'en_pausa'])
            ->get();

        foreach ($paquetes as $paquete) {
            $efectivo = EstadoInscripcionResolver::efectivoPaquete($paquete, $hoy);

            if (! RetiroEnCascadaRule::arrastra($efectivo)) {
                continue;
            }

            if (! $this->access->puedeAcceder($actor->id, (int) $paquete->sucursal_id)) {
                $faltantes[] = (int) $paquete->sucursal_id;

                continue;
            }

            $inscripciones[] = [
                'tipo_entidad' => TipoEntidadVigencia::PAQUETE_SESIONES->value,
                'tipo_entidad_label' => TipoEntidadVigencia::PAQUETE_SESIONES->label(),
                'id' => (int) $paquete->id,
                'paciente' => [
                    'id' => (int) $paquete->paciente_id,
                    'nombre_completo' => $this->patientName($paquete),
                ],
                'sucursal' => [
                    'id' => (int) $paquete->sucursal_id,
                    'nombre' => (string) ($paquete->sucursal_nombre ?? ''),
                ],
                'servicio' => [
                    'id' => (int) $paquete->servicio_id,
                    'nombre' => (string) ($paquete->servicio_nombre ?? ''),
                ],
                'profesional' => $paquete->profesional_id === null ? null : [
                    'id' => (int) $paquete->profesional_id,
                    'nombre_completo' => (string) ($paquete->profesional_nombre ?? ''),
                ],
                'cantidad_sesiones' => (int) $paquete->cantidad_sesiones,
                'fecha_inicio' => $paquete->fecha_inicio->toDateString(),
                'fecha_fin' => $paquete->fecha_fin->toDateString(),
                'estado_efectivo' => $efectivo->value,
                'estado_efectivo_label' => $efectivo->label(),
            ];
        }

        if ($faltantes !== []) {
            throw new RetiroFueraDeSucursalException(array_values(array_unique($faltantes)));
        }

        return [
            'paciente' => $paciente->toArray(),
            'inscripciones' => $inscripciones,
            'conteos' => [
                'total' => count($inscripciones),
                'salas' => count(array_filter(
                    $inscripciones,
                    static fn (array $item): bool => $item['tipo_entidad'] === TipoEntidadVigencia::INSCRIPCION_SALA->value,
                )),
                'paquetes' => count(array_filter(
                    $inscripciones,
                    static fn (array $item): bool => $item['tipo_entidad'] === TipoEntidadVigencia::PAQUETE_SESIONES->value,
                )),
            ],
        ];
    }

    private function patientName(InscripcionSala|PaqueteSesiones $modelo): string
    {
        return trim(implode(' ', array_filter([
            $modelo->paciente_nombre ?? null,
            $modelo->paciente_apellido_paterno ?? null,
            $modelo->paciente_apellido_materno ?? null,
        ], static fn (mixed $value): bool => $value !== null && $value !== '')));
    }
}
