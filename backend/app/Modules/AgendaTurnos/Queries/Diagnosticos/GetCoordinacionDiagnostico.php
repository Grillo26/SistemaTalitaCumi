<?php

namespace App\Modules\AgendaTurnos\Queries\Diagnosticos;

use App\Models\User;
use App\Modules\AgendaTurnos\Enums\EstadoGeneralDiagnostico;
use App\Modules\AgendaTurnos\Enums\EstadoTurno;
use App\Modules\AgendaTurnos\Exceptions\DiagnosticoNotFoundException;
use App\Modules\AgendaTurnos\Exceptions\WhatsappDiagnosticoNoDisponibleException;
use App\Modules\AgendaTurnos\Models\Turno;
use App\Modules\AgendaTurnos\Rules\EstadoGeneralDiagnosticoRule;
use App\Modules\AgendaTurnos\Rules\RegeneracionRule;
use App\Modules\AgendaTurnos\Support\AgendaAccessScope;
use App\Modules\AgendaTurnos\Support\AgendaClock;
use App\Modules\AgendaTurnos\Support\DiagnosticosModule;
use App\Modules\AgendaTurnos\Support\DiagnosticosPermission;
use App\Modules\AgendaTurnos\Support\DiagnosticoTurnosResolver;
use App\Modules\AgendaTurnos\Support\TurnoExistente;
use App\Modules\AgendaTurnos\Support\WhatsappDiagnosticoLink;
use App\Modules\InscripcionesAtenciones\Contracts\DiagnosticoAtencionContract;
use App\Modules\InscripcionesAtenciones\Contracts\DiagnosticoAtencionResumen;
use App\Modules\Organizacion\Contracts\BranchDirectoryContract;
use App\Modules\Organizacion\Contracts\BranchResumen;
use App\Modules\Pacientes\Contracts\PacienteResumen;
use App\Modules\Pacientes\Contracts\PatientDirectoryContract;
use App\Modules\Pacientes\Contracts\TutorDirectoryContract;
use App\Modules\Pacientes\Contracts\TutorResumen;
use App\Modules\Profesionales\Contracts\ProfessionalDirectoryContract;
use App\Modules\Seguridad\Contracts\AuditRecorderContract;
use Throwable;

/**
 * 07.05/RF-006 / 07.05/RF-011 / 07.05/RF-014 / 07.05/RN-008 /
 * 07.05/CA-002 / 07.05/CA-005 / 07.05/CA-009 / 07.05/CA-010.
 *
 * Vista consolidada de un diagnóstico con todos sus puestos, profesionales,
 * turnos y advertencias. Audita la consulta del detalle (dato de salud) con
 * fail-closed; el listado no se audita.
 */
final class GetCoordinacionDiagnostico
{
    public function __construct(
        private readonly DiagnosticoAtencionContract $diagnosticos,
        private readonly AgendaAccessScope $access,
        private readonly DiagnosticoTurnosResolver $turnos,
        private readonly EstadoGeneralDiagnosticoRule $rule,
        private readonly PatientDirectoryContract $pacientes,
        private readonly BranchDirectoryContract $sucursales,
        private readonly ProfessionalDirectoryContract $profesionales,
        private readonly TutorDirectoryContract $tutores,
        private readonly WhatsappDiagnosticoLink $whatsapp,
        private readonly AuditRecorderContract $auditoria,
        private readonly AgendaClock $clock,
        private readonly RegeneracionRule $futuro,
    ) {}

    /**
     * @return array<string, mixed>
     */
    public function __invoke(int $diagnosticoId, User $actor, ?string $ip = null): array
    {
        $resumen = $this->diagnosticos->resumen($diagnosticoId);

        if ($resumen === null || ! in_array($resumen->sucursalId, $this->access->accesibles($actor), true)) {
            throw new DiagnosticoNotFoundException($diagnosticoId);
        }

        $this->auditoria->registrarConsultaSensible(
            entidad: DiagnosticosModule::ENTITY_DIAGNOSTICO,
            entidadId: $diagnosticoId,
            sucursalId: $resumen->sucursalId,
            contexto: ['recurso' => DiagnosticosModule::RECURSO_CONSULTA],
            usuarioId: (int) $actor->id,
            ip: $ip,
        );

        $turnos = $this->turnos->delDiagnostico($diagnosticoId);
        $resultado = $this->rule->evaluar(
            $resumen->requisitos,
            $this->turnos->paraRegla($turnos),
            $this->turnos->especialidadesPorProfesional($resumen),
        );

        $estado = $resumen->estadoGeneral === EstadoGeneralDiagnostico::CANCELADO->value
            ? EstadoGeneralDiagnostico::CANCELADO
            : $resultado->estado;

        $nombresProfesionales = $this->nombresProfesionales($resumen);
        $activosSucursal = $this->activosDeSucursal($resumen->sucursalId);
        $turnosData = $this->turnosData($turnos, $nombresProfesionales, $actor);
        $profesionalesData = $this->profesionalesData(
            $resumen,
            $nombresProfesionales,
            $activosSucursal,
            $turnosData,
        );
        $puestos = $this->puestos($resultado->puestos, $profesionalesData, $turnosData);
        $paciente = $this->paciente($resumen->pacienteId);

        $habilitado = $resumen->estado === 'activa'
            && $estado !== EstadoGeneralDiagnostico::CANCELADO;

        return [
            'data' => [
                'id' => $resumen->id,
                'paciente' => $paciente,
                'sucursal' => $this->sucursal($resumen->sucursalId),
                'servicio' => [
                    'id' => $resumen->servicioId,
                    'nombre' => $resumen->servicioNombre,
                ],
                'tipo_diagnostico' => [
                    'id' => $resumen->tipoDiagnosticoId,
                    'nombre' => $resumen->tipoDiagnosticoNombre,
                ],
                'fecha_inicio' => $resumen->fechaInicio,
                'fecha_fin' => $resumen->fechaFin,
                'estado' => $resumen->estado,
                'estado_label' => $resumen->estado === 'activa' ? 'Activa' : 'Retirada',
                'estado_general' => $estado->value,
                'estado_general_label' => $estado->label(),
                'estado_general_guardado' => $resumen->estadoGeneral,
                'profesionales' => $profesionalesData,
                'turnos' => $turnosData,
                'puestos' => $puestos,
                'faltantes' => $resultado->faltantes,
                'whatsapp' => $this->whatsapp(
                    $resumen,
                    $actor,
                    $turnosData,
                    $paciente,
                ),
            ],
            'can' => [
                'ver' => $actor->can(DiagnosticosPermission::VER),
                'programar' => $actor->can(DiagnosticosPermission::PROGRAMAR) && $habilitado,
                'cancelar' => $actor->can(DiagnosticosPermission::CANCELAR) && $habilitado,
                'actualizar_estado' => $actor->can(DiagnosticosPermission::ACTUALIZAR_ESTADO) && $habilitado,
                'exportar' => $actor->can(DiagnosticosPermission::EXPORTAR),
                'whatsapp' => $actor->can(DiagnosticosPermission::WHATSAPP),
            ],
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function paciente(int $pacienteId): array
    {
        try {
            /** @var PacienteResumen $paciente */
            $paciente = $this->pacientes->resumen($pacienteId);

            return [
                'id' => $paciente->id,
                'codigo' => $paciente->codigo,
                'nombre_completo' => $paciente->nombreCompleto,
                'estado' => $paciente->estado->value,
                'estado_label' => $paciente->estado->label(),
            ];
        } catch (Throwable) {
            return [
                'id' => $pacienteId,
                'codigo' => null,
                'nombre_completo' => 'No disponible',
                'estado' => null,
                'estado_label' => null,
            ];
        }
    }

    /**
     * @return array{id: int, nombre: string}
     */
    private function sucursal(int $sucursalId): array
    {
        try {
            /** @var BranchResumen|null $sucursal */
            $sucursal = $this->sucursales->resumenes([$sucursalId])[$sucursalId] ?? null;

            if ($sucursal !== null) {
                return ['id' => $sucursal->id, 'nombre' => $sucursal->nombre];
            }
        } catch (Throwable) {
            // Se devuelve el id sin nombre; no se inventan datos.
        }

        return ['id' => $sucursalId, 'nombre' => 'No disponible'];
    }

    /**
     * @return array<int, string>
     */
    private function nombresProfesionales(DiagnosticoAtencionResumen $resumen): array
    {
        $ids = array_map(
            static fn (array $profesional): int => (int) $profesional['id'],
            $resumen->profesionales,
        );

        $nombres = [];

        foreach ($resumen->profesionales as $profesional) {
            $nombres[(int) $profesional['id']] = (string) $profesional['nombre_completo'];
        }

        if ($ids === []) {
            return $nombres;
        }

        try {
            return $this->profesionales->nombres($ids) + $nombres;
        } catch (Throwable) {
            return $nombres;
        }
    }

    /**
     * @return array<int, string>
     */
    private function activosDeSucursal(int $sucursalId): array
    {
        try {
            return $this->profesionales->nombresPorSucursal($sucursalId);
        } catch (Throwable) {
            return [];
        }
    }

    /**
     * @param  list<Turno>  $turnos
     * @param  array<int, string>  $nombres
     * @return list<array<string, mixed>>
     */
    private function turnosData(array $turnos, array $nombres, User $actor): array
    {
        $hoy = $this->clock->hoy();
        $horaActual = $this->clock->horaActual();
        $puedeReprogramar = $actor->can('turno.reprogramar') && $actor->can('turno.ver');
        $puedeAsistencia = $actor->can('asistencia.registrar') && $actor->can('asistencia.ver');
        $resultado = [];

        foreach ($turnos as $turno) {
            $existente = TurnoExistente::fromTurno($turno);
            $esFuturo = $this->futuro->esFuturo($existente, $hoy, $horaActual);
            $iniciado = $turno->fecha->toDateString() < $hoy
                || ($turno->fecha->toDateString() === $hoy && (string) $turno->hora_inicio <= $horaActual);
            $profesionalId = $turno->profesional_id === null ? null : (int) $turno->profesional_id;
            $estado = $turno->estado;

            $resultado[] = [
                'id' => (int) $turno->id,
                'profesional_id' => $profesionalId,
                'profesional_nombre' => $profesionalId === null
                    ? 'Sin asignar'
                    : ($nombres[$profesionalId] ?? 'No disponible'),
                'fecha' => $turno->fecha->toDateString(),
                'hora_inicio' => (string) $turno->hora_inicio,
                'hora_fin' => (string) $turno->hora_fin,
                'duracion_minutos' => (int) $turno->duracion_minutos,
                'estado' => $estado->value,
                'estado_label' => $estado->label(),
                'es_futuro' => $esFuturo,
                'puede_reprogramar' => $puedeReprogramar
                    && $estado === EstadoTurno::PROGRAMADO
                    && $esFuturo,
                'puede_registrar_asistencia' => $puedeAsistencia
                    && $estado === EstadoTurno::PROGRAMADO
                    && $iniciado,
            ];
        }

        return $resultado;
    }

    /**
     * @param  array<int, string>  $nombres
     * @param  array<int, string>  $activos
     * @param  list<array<string, mixed>>  $turnos
     * @return list<array<string, mixed>>
     */
    private function profesionalesData(
        DiagnosticoAtencionResumen $resumen,
        array $nombres,
        array $activos,
        array $turnos,
    ): array {
        $resultado = [];

        foreach ($resumen->profesionales as $profesional) {
            $id = (int) $profesional['id'];
            $vigente = $this->turnoVigente($turnos, $id);
            $actual = $this->turnoActual($turnos, $id);

            $resultado[] = [
                'id' => $id,
                'nombre_completo' => $nombres[$id] ?? (string) $profesional['nombre_completo'],
                'especialidades' => $profesional['especialidades'],
                'activo_en_sucursal' => array_key_exists($id, $activos),
                'turno_vigente' => $vigente,
                'turno_actual' => $actual,
                'disponible_para_programar' => array_key_exists($id, $activos) && $vigente === null,
            ];
        }

        return $resultado;
    }

    /**
     * @param  list<array<string, mixed>>  $puestos
     * @param  list<array<string, mixed>>  $profesionales
     * @param  list<array<string, mixed>>  $turnos
     * @return list<array<string, mixed>>
     */
    private function puestos(array $puestos, array $profesionales, array $turnos): array
    {
        $resultado = [];

        foreach ($puestos as $puesto) {
            $especialidadId = (int) $puesto['especialidad_id'];
            $asignados = [];

            foreach ($profesionales as $profesional) {
                foreach ($profesional['especialidades'] as $especialidad) {
                    if ((int) $especialidad['id'] !== $especialidadId) {
                        continue;
                    }

                    $asignados[] = [
                        'id' => $profesional['id'],
                        'nombre_completo' => $profesional['nombre_completo'],
                        'turno' => $this->turnoActual($turnos, (int) $profesional['id']),
                        'disponible_para_programar' => $profesional['disponible_para_programar'],
                    ];
                    break;
                }
            }

            $resultado[] = [
                ...$puesto,
                'profesionales' => $asignados,
            ];
        }

        return $resultado;
    }

    /**
     * @param  list<array<string, mixed>>  $turnos
     * @return array<string, mixed>|null
     */
    private function turnoVigente(array $turnos, int $profesionalId): ?array
    {
        foreach ($turnos as $turno) {
            if ((int) $turno['profesional_id'] !== $profesionalId) {
                continue;
            }

            if (in_array($turno['estado'], ['programado', 'asistio'], true)) {
                return $turno;
            }
        }

        return null;
    }

    /**
     * Turno visible para el profesional: programado/asistió; si no hay,
     * conserva el último "faltó" para no mostrarlo como "sin turno".
     *
     * @param  list<array<string, mixed>>  $turnos
     * @return array<string, mixed>|null
     */
    private function turnoActual(array $turnos, int $profesionalId): ?array
    {
        $falto = null;

        foreach ($turnos as $turno) {
            if ((int) $turno['profesional_id'] !== $profesionalId) {
                continue;
            }

            if (in_array($turno['estado'], ['programado', 'asistio'], true)) {
                return $turno;
            }

            if ($turno['estado'] === 'falto' && $falto === null) {
                $falto = $turno;
            }
        }

        return $falto;
    }

    /**
     * @param  list<array<string, mixed>>  $turnos
     * @param  array<string, mixed>  $paciente
     * @return array<string, mixed>
     */
    private function whatsapp(
        DiagnosticoAtencionResumen $resumen,
        User $actor,
        array $turnos,
        array $paciente,
    ): array {
        if (! $actor->can(DiagnosticosPermission::WHATSAPP)) {
            return [
                'disponible' => false,
                'mensaje' => 'No tenés permiso para ver el contacto del tutor.',
            ];
        }

        try {
            /** @var TutorResumen|null $tutor */
            $tutor = $this->tutores->tutorPrincipal($resumen->pacienteId);
        } catch (Throwable) {
            $tutor = null;
        }

        if ($tutor === null) {
            return [
                'disponible' => false,
                'mensaje' => 'El paciente no tiene un tutor principal activo.',
            ];
        }

        $programados = [];

        foreach ($turnos as $turno) {
            if ($turno['estado'] === EstadoTurno::PROGRAMADO->value && ($turno['es_futuro'] ?? false)) {
                $programados[] = [
                    'fecha' => (string) $turno['fecha'],
                    'hora_inicio' => (string) $turno['hora_inicio'],
                    'profesional_nombre' => (string) $turno['profesional_nombre'],
                ];
            }
        }

        try {
            return $this->whatsapp->construir(
                $tutor,
                (string) ($paciente['nombre_completo'] ?? ''),
                $programados,
            );
        } catch (WhatsappDiagnosticoNoDisponibleException $exception) {
            return [
                'disponible' => false,
                'mensaje' => $exception->getMessage(),
            ];
        }
    }
}
