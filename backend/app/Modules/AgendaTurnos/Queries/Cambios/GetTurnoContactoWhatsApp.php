<?php

namespace App\Modules\AgendaTurnos\Queries\Cambios;

use App\Models\User;
use App\Modules\AgendaTurnos\Exceptions\TurnoNotFoundException;
use App\Modules\AgendaTurnos\Models\Turno;
use App\Modules\AgendaTurnos\Support\AgendaAccessScope;
use App\Modules\AgendaTurnos\Support\DirectoriosAgenda;
use App\Modules\AgendaTurnos\Support\WhatsappTurnoLink;
use App\Modules\Pacientes\Contracts\TutorDirectoryContract;
use App\Modules\Pacientes\Support\PacientesPermission;

/**
 * [Reprogramación y Cancelación] RF-015 / RN-010 / CA-010.
 *
 * Solo devuelve el enlace wa.me; no envía mensajes. El teléfono se expone
 * únicamente con `tutor.ver_contacto`.
 */
final class GetTurnoContactoWhatsApp
{
    public function __construct(
        private readonly AgendaAccessScope $access,
        private readonly TutorDirectoryContract $tutores,
        private readonly DirectoriosAgenda $nombres,
        private readonly WhatsappTurnoLink $link,
    ) {}

    /**
     * @return array<string, mixed>
     */
    public function __invoke(
        int $turnoId,
        User $actor,
        string $accion = 'reprogramado',
    ): array {
        $turno = Turno::query()->find($turnoId);

        if ($turno === null) {
            throw new TurnoNotFoundException($turnoId);
        }

        $this->access->assertSucursal($actor, (int) $turno->sucursal_id);

        if (! $actor->can(PacientesPermission::TUTOR_VIEW_CONTACT)) {
            return [
                'disponible' => false,
                'mensaje' => 'No tenés permiso para ver el contacto del tutor.',
            ];
        }

        $tutor = $this->tutores->tutorPrincipal((int) $turno->paciente_id);

        if ($tutor === null) {
            return [
                'disponible' => false,
                'mensaje' => 'El paciente no tiene un tutor principal activo.',
            ];
        }

        return $this->link->construir(
            $tutor,
            $this->nombres->paciente((int) $turno->paciente_id),
            $turno->fecha->toDateString(),
            substr((string) $turno->hora_inicio, 0, 5),
            $accion,
        );
    }
}
