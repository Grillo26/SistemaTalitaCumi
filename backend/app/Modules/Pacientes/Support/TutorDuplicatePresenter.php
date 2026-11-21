<?php

namespace App\Modules\Pacientes\Support;

use App\Models\User;
use App\Modules\Pacientes\Models\Tutor;

/**
 * Presentación sanitizada de duplicados de tutor (04.02/CA-002).
 *
 * Solo devuelve datos personales de tutores dentro del alcance del actor.
 * Un tutor fuera de alcance se informa con flags, sin id ni nombre.
 */
final class TutorDuplicatePresenter
{
    public function __construct(private readonly TutorAccessScope $scope) {}

    /**
     * @return array<string, mixed>
     */
    public function for(User $actor, TutorDuplicateResult $result): array
    {
        $candidates = collect([$result->documentTutor, $result->namePhoneTutor])
            ->filter()
            ->unique(static fn (Tutor $tutor): int => (int) $tutor->id)
            ->values();

        $inScope = [];
        $outOfScope = false;

        foreach ($candidates as $tutor) {
            if ($this->scope->canAccess($actor, $tutor)) {
                $inScope[] = $this->minimal($actor, $tutor);
            } else {
                $outOfScope = true;
            }
        }

        return [
            'coincide_documento' => $result->documentTutor !== null,
            'coincide_nombre_telefono' => $result->namePhoneTutor !== null,
            'fuera_de_alcance' => $outOfScope,
            'puede_asociar' => $inScope !== [],
            'tutores_en_alcance' => $inScope,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function minimal(User $actor, Tutor $tutor): array
    {
        $tutor->loadMissing('parentescoCatalogo');
        $fullContact = $actor->can(PacientesPermission::TUTOR_VIEW_CONTACT);

        return [
            'id' => (int) $tutor->id,
            'nombre_completo' => $tutor->nombre_completo,
            'telefono' => $fullContact ? $tutor->telefono : PhoneMask::mask($tutor->telefono),
            'parentesco_id' => $tutor->parentesco_id === null ? null : (int) $tutor->parentesco_id,
            'parentesco' => $tutor->parentescoCatalogo?->nombre ?? $tutor->parentesco,
            'activo' => (bool) $tutor->activo,
            'puede_asociar' => true,
        ];
    }
}
