<?php

namespace App\Modules\Pacientes\Http\Resources;

use App\Models\User;
use App\Modules\Organizacion\Models\Branch;
use App\Modules\Pacientes\Models\PacienteTutor;
use App\Modules\Pacientes\Models\Parentesco;
use App\Modules\Pacientes\Models\Tutor;
use App\Modules\Pacientes\Rules\TutorIdentityEditScopeRule;
use App\Modules\Pacientes\Support\PacientesPermission;
use App\Modules\Pacientes\Support\PhoneMask;
use App\Modules\Pacientes\Support\TutorAbilities;
use App\Modules\Pacientes\Support\TutorAccessScope;
use App\Modules\Pacientes\Support\TutorBranchResolver;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Representación del tutor (04.02/RF-007, 04.02/RF-008).
 *
 * El listado enmascara el teléfono salvo `tutor.ver_contacto`. La ficha
 * devuelve pacientes solo si el actor tiene `paciente.ver` y están dentro de
 * su alcance; nunca devuelve un conteo de pacientes ocultos.
 */
final class TutorResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public static function forList(User $actor, Tutor $tutor, Request $request): array
    {
        $data = self::base($tutor, $actor, false);
        $data['can'] = TutorAbilities::for($actor, $tutor, self::identityEditable($actor, $tutor));

        return $data;
    }

    /**
     * @return array<string, mixed>
     */
    public static function forFicha(User $actor, Tutor $tutor, Request $request): array
    {
        $data = self::base($tutor, $actor, true);
        $data['motivo_estado'] = $tutor->motivo_estado;
        $data['estado_cambiado_en'] = $tutor->estado_cambiado_en?->toIso8601String();
        $data['pacientes'] = $actor->can(PacientesPermission::PATIENT_VIEW)
            ? self::patients($tutor)
            : [];
        $data['can'] = TutorAbilities::for($actor, $tutor, self::identityEditable($actor, $tutor));

        return $data;
    }

    /**
     * Tutor dentro de la ficha de un paciente (04.02/RF-004, 04.02/RF-008).
     *
     * @return array<string, mixed>
     */
    public static function forPaciente(User $actor, PacienteTutor $relation, Request $request): array
    {
        $relation->loadMissing(['tutor.parentescoCatalogo', 'parentesco']);

        $tutor = $relation->tutor;
        $parentesco = $relation->parentesco ?? $tutor?->parentescoCatalogo;

        if ($tutor === null) {
            return [];
        }

        $data = self::base($tutor, $actor, true, $parentesco);
        $data['tutor_id'] = (int) $tutor->id;
        $data['es_principal'] = (bool) $relation->es_principal;
        $data['can'] = TutorAbilities::for($actor, $tutor, self::identityEditable($actor, $tutor));

        return $data;
    }

    /**
     * Alias de ficha para compatibilidad con consumidores internos.
     *
     * @return array<string, mixed>
     */
    public static function for(User $actor, Tutor $tutor, Request $request): array
    {
        return self::forFicha($actor, $tutor, $request);
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        /** @var Tutor $tutor */
        $tutor = $this->resource;

        return self::base($tutor, null, false);
    }

    /**
     * @return array<string, mixed>
     */
    private static function base(
        Tutor $tutor,
        ?User $actor,
        bool $includeSensitiveContact,
        ?Parentesco $parentesco = null,
    ): array {
        $fullContact = $actor !== null
            && $actor->can(PacientesPermission::TUTOR_VIEW_CONTACT);
        $parentesco ??= $tutor->parentescoCatalogo;
        $sucursal = self::visibleHomeBranch($actor, $tutor);

        return [
            'id' => (int) $tutor->id,
            'sucursal_registro' => $sucursal instanceof Branch
                ? [
                    'id' => (int) $sucursal->id,
                    'codigo' => $sucursal->codigo,
                    'nombre' => $sucursal->nombre,
                    'activo' => (bool) $sucursal->activo,
                ]
                : null,
            'nombre_completo' => $tutor->nombre_completo,
            'ci' => $tutor->ci,
            'telefono' => $fullContact ? $tutor->telefono : PhoneMask::mask($tutor->telefono),
            'telefono_alternativo' => $fullContact
                ? $tutor->telefono_alternativo
                : PhoneMask::mask($tutor->telefono_alternativo),
            'email' => $includeSensitiveContact && $fullContact ? $tutor->email : null,
            'direccion' => $includeSensitiveContact && $fullContact ? $tutor->direccion : null,
            'parentesco_id' => $parentesco?->id,
            'parentesco' => $parentesco?->nombre ?? $tutor->parentesco,
            'activo' => (bool) $tutor->activo,
            'estado' => $tutor->estado,
            'version' => (int) $tutor->version,
            'created_at' => $tutor->created_at?->toIso8601String(),
            'updated_at' => $tutor->updated_at?->toIso8601String(),
        ];
    }

    /**
     * @return list<array<string, mixed>>
     */
    private static function patients(Tutor $tutor): array
    {
        $tutor->loadMissing('relacionesPaciente.paciente', 'relacionesPaciente.parentesco');

        return $tutor->relacionesPaciente
            ->filter(static fn (PacienteTutor $relacion): bool => $relacion->paciente !== null)
            ->sortBy(static fn (PacienteTutor $relacion): string => sprintf(
                '%d-%s',
                $relacion->es_principal ? 0 : 1,
                mb_strtolower((string) $relacion->paciente->nombre_completo),
            ))
            ->map(static function (PacienteTutor $relacion): array {
                $parentesco = $relacion->parentesco ?? $relacion->tutor?->parentescoCatalogo;

                return [
                    'id' => (int) $relacion->paciente->id,
                    'codigo' => $relacion->paciente->codigo,
                    'nombre_completo' => $relacion->paciente->nombre_completo,
                    'estado' => $relacion->paciente->estado->value,
                    'estado_label' => $relacion->paciente->estado->label(),
                    'sucursal_registro_id' => (int) $relacion->paciente->sucursal_registro_id,
                    'es_principal' => (bool) $relacion->es_principal,
                    'parentesco_id' => $parentesco?->id,
                    'parentesco' => $parentesco?->nombre,
                ];
            })
            ->values()
            ->all();
    }

    private static function identityEditable(User $actor, Tutor $tutor): bool
    {
        return app(TutorIdentityEditScopeRule::class)->canEditIdentity(
            app(TutorAccessScope::class)->accessibleBranchIds($actor),
            app(TutorBranchResolver::class)->for($tutor),
        );
    }

    private static function visibleHomeBranch(?User $actor, Tutor $tutor): ?Branch
    {
        if (! $tutor->relationLoaded('sucursalRegistro')) {
            $tutor->loadMissing('sucursalRegistro');
        }

        $branch = $tutor->sucursalRegistro;

        if ($branch === null || $actor === null) {
            return $branch;
        }

        $branchIds = app(TutorAccessScope::class)->accessibleBranchIds($actor);

        if ($branchIds === null || in_array((int) $branch->id, $branchIds, true)) {
            return $branch;
        }

        return null;
    }
}
