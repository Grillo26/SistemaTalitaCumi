<?php

namespace App\Modules\Pacientes\Actions\Tutores;

use App\Models\User;
use App\Modules\Pacientes\Events\Tutores\TutorCreated;
use App\Modules\Pacientes\Exceptions\DuplicateTutorException;
use App\Modules\Pacientes\Exceptions\TutorPhoneInvalidException;
use App\Modules\Pacientes\Exceptions\TutorSucursalRequiredException;
use App\Modules\Pacientes\Models\Tutor;
use App\Modules\Pacientes\Rules\TutorIdentityRule;
use App\Modules\Pacientes\Rules\TutorPhoneRule;
use App\Modules\Pacientes\Support\ParentescoResolver;
use App\Modules\Pacientes\Support\TutorDuplicateDetector;
use App\Modules\Pacientes\Support\TutorDuplicatePresenter;
use App\Modules\Pacientes\Support\TutorSnapshot;
use App\Modules\Pacientes\Support\UniqueViolationTranslator;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;

/**
 * Crear tutor (04.02/RF-001, 04.02/RF-002, 04.02/RF-003).
 *
 * Se usa desde el alta de paciente y desde la página de tutores.
 */
final class CreateTutorAction
{
    public function __construct(
        private readonly ParentescoResolver $parentescos,
        private readonly TutorDuplicateDetector $duplicates,
        private readonly TutorDuplicatePresenter $duplicatePresenter,
    ) {}

    /**
     * @param  array<string, mixed>  $attributes
     */
    public function __invoke(
        array $attributes,
        User $actor,
        ?string $ip = null,
        ?int $sucursalRegistroId = null,
    ): Tutor {
        if ($sucursalRegistroId === null) {
            throw new TutorSucursalRequiredException;
        }

        $nombre = TutorIdentityRule::normalizeName((string) $attributes['nombre_completo']);
        $ci = TutorIdentityRule::normalizeDocument($attributes['ci'] ?? null);
        $telefono = TutorPhoneRule::normalize((string) $attributes['telefono']);

        if (! TutorPhoneRule::isValidPrincipal($telefono)) {
            throw new TutorPhoneInvalidException('telefono');
        }

        $telefonoAlternativo = TutorPhoneRule::normalize($attributes['telefono_alternativo'] ?? null);

        if ($telefonoAlternativo !== null && ! TutorPhoneRule::isValidAlternate($telefonoAlternativo)) {
            throw new TutorPhoneInvalidException('telefono_alternativo');
        }

        $parentesco = $this->parentescos->resolve(
            isset($attributes['parentesco_id']) ? (int) $attributes['parentesco_id'] : null,
            $attributes['parentesco'] ?? null,
        );

        $duplicates = $this->duplicates->detect($nombre, $ci, (string) $telefono);

        if ($duplicates->hasConflict()) {
            throw new DuplicateTutorException(
                $duplicates->errors(),
                $this->duplicatePresenter->for($actor, $duplicates),
            );
        }

        return DB::transaction(function () use (
            $attributes,
            $nombre,
            $ci,
            $telefono,
            $telefonoAlternativo,
            $parentesco,
            $sucursalRegistroId,
            $actor,
            $ip,
        ): Tutor {
            try {
                $tutor = Tutor::query()->create([
                    'sucursal_registro_id' => $sucursalRegistroId,
                    'nombre_completo' => $nombre,
                    'ci' => $ci,
                    'telefono' => $telefono,
                    'telefono_alternativo' => $telefonoAlternativo,
                    'email' => TutorIdentityRule::normalizeOptional($attributes['email'] ?? null),
                    'direccion' => TutorIdentityRule::normalizeOptional($attributes['direccion'] ?? null),
                    'parentesco' => $parentesco->nombre,
                    'parentesco_id' => $parentesco->id,
                    'activo' => true,
                    'version' => 1,
                ]);
            } catch (QueryException $exception) {
                UniqueViolationTranslator::tutor($exception);
            }

            $tutor->refresh()->load('parentescoCatalogo');

            event(new TutorCreated(
                tutorId: (int) $tutor->id,
                snapshot: TutorSnapshot::from($tutor),
                branchIds: [$sucursalRegistroId],
                actorId: $actor->id,
                ip: $ip,
            ));

            return $tutor;
        });
    }
}
