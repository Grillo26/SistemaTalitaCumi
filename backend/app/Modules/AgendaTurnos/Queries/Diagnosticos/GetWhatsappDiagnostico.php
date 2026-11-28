<?php

namespace App\Modules\AgendaTurnos\Queries\Diagnosticos;

use App\Models\User;
use App\Modules\AgendaTurnos\Enums\EstadoTurno;
use App\Modules\AgendaTurnos\Exceptions\DiagnosticoNotFoundException;
use App\Modules\AgendaTurnos\Exceptions\WhatsappDiagnosticoNoDisponibleException;
use App\Modules\AgendaTurnos\Models\Turno;
use App\Modules\AgendaTurnos\Rules\RegeneracionRule;
use App\Modules\AgendaTurnos\Support\AgendaAccessScope;
use App\Modules\AgendaTurnos\Support\AgendaClock;
use App\Modules\AgendaTurnos\Support\DiagnosticosModule;
use App\Modules\AgendaTurnos\Support\DiagnosticosPermission;
use App\Modules\AgendaTurnos\Support\TurnoExistente;
use App\Modules\AgendaTurnos\Support\WhatsappDiagnosticoLink;
use App\Modules\InscripcionesAtenciones\Contracts\DiagnosticoAtencionContract;
use App\Modules\Pacientes\Contracts\PatientDirectoryContract;
use App\Modules\Pacientes\Contracts\TutorDirectoryContract;
use App\Modules\Profesionales\Contracts\ProfessionalDirectoryContract;
use App\Modules\Seguridad\Contracts\AuditRecorderContract;
use Throwable;

/**
 * 07.05/RF-014 / 07.05/CA-010.
 *
 * Enlace wa.me con el calendario de turnos programados. No envía mensajes.
 * Solo se devuelve con `tutor.ver_contacto`; la consulta se audita.
 */
final class GetWhatsappDiagnostico
{
    public function __construct(
        private readonly DiagnosticoAtencionContract $diagnosticos,
        private readonly AgendaAccessScope $access,
        private readonly TutorDirectoryContract $tutores,
        private readonly PatientDirectoryContract $pacientes,
        private readonly ProfessionalDirectoryContract $profesionales,
        private readonly WhatsappDiagnosticoLink $link,
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

        if (! $actor->can(DiagnosticosPermission::VER)) {
            throw new DiagnosticoNotFoundException($diagnosticoId);
        }

        if (! $actor->can(DiagnosticosPermission::WHATSAPP)) {
            throw new WhatsappDiagnosticoNoDisponibleException(
                'No tenés permiso para ver el contacto del tutor.',
            );
        }

        $this->auditoria->registrarConsultaSensible(
            entidad: DiagnosticosModule::ENTITY_DIAGNOSTICO,
            entidadId: $diagnosticoId,
            sucursalId: $resumen->sucursalId,
            contexto: ['recurso' => 'diagnostico_whatsapp'],
            usuarioId: (int) $actor->id,
            ip: $ip,
        );

        $tutor = $this->tutores->tutorPrincipal($resumen->pacienteId);

        if ($tutor === null) {
            throw new WhatsappDiagnosticoNoDisponibleException(
                'El paciente no tiene un tutor principal activo.',
            );
        }

        $turnos = Turno::query()
            ->where('atencion_diagnostico_id', $diagnosticoId)
            ->where('estado', EstadoTurno::PROGRAMADO->value)
            ->ordenado()
            ->get()
            ->filter(function (Turno $turno): bool {
                return $this->futuro->esFuturo(
                    TurnoExistente::fromTurno($turno),
                    $this->clock->hoy(),
                    $this->clock->horaActual(),
                );
            })
            ->values();

        if ($turnos->isEmpty()) {
            throw new WhatsappDiagnosticoNoDisponibleException(
                'El diagnóstico no tiene turnos programados futuros para avisar.',
            );
        }

        $nombres = $this->nombresProfesionales($turnos->all());
        $paciente = $this->nombrePaciente($resumen->pacienteId);
        $calendario = [];

        foreach ($turnos as $turno) {
            /** @var Turno $turno */
            $profesionalId = $turno->profesional_id === null ? null : (int) $turno->profesional_id;
            $calendario[] = [
                'fecha' => $turno->fecha->toDateString(),
                'hora_inicio' => (string) $turno->hora_inicio,
                'profesional_nombre' => $profesionalId === null
                    ? 'Sin asignar'
                    : ($nombres[$profesionalId] ?? 'No disponible'),
            ];
        }

        return $this->link->construir($tutor, $paciente, $calendario);
    }

    /**
     * @param  list<Turno>  $turnos
     * @return array<int, string>
     */
    private function nombresProfesionales(array $turnos): array
    {
        $ids = [];

        foreach ($turnos as $turno) {
            if ($turno->profesional_id !== null) {
                $ids[] = (int) $turno->profesional_id;
            }
        }

        $ids = array_values(array_unique($ids));

        if ($ids === []) {
            return [];
        }

        try {
            return $this->profesionales->nombres($ids);
        } catch (Throwable) {
            return [];
        }
    }

    private function nombrePaciente(int $pacienteId): string
    {
        try {
            return $this->pacientes->resumen($pacienteId)->nombreCompleto;
        } catch (Throwable) {
            return 'No disponible';
        }
    }
}
