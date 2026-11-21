<?php

namespace App\Modules\Pacientes\Actions\Consentimiento;

use App\Models\User;
use App\Modules\Pacientes\Events\Consentimiento\ConsentimientoTipoUpdated;
use App\Modules\Pacientes\Exceptions\ConsentimientoTipoConcurrencyException;
use App\Modules\Pacientes\Exceptions\ConsentimientoTipoNotFoundException;
use App\Modules\Pacientes\Models\ConsentimientoTipo;
use App\Modules\Pacientes\Rules\TutorIdentityRule;
use App\Modules\Pacientes\Support\ConsentimientoTipoSnapshot;
use Illuminate\Support\Facades\DB;

/**
 * Edita un tipo de consentimiento con versión optimista (04.03 Fase B).
 *
 * El código no se modifica una vez creado para no romper consumidores.
 */
final class UpdateConsentimientoTipoAction
{
    /**
     * @param  array<string, mixed>  $attributes
     */
    public function __invoke(
        ConsentimientoTipo $tipo,
        array $attributes,
        int $expectedVersion,
        User $actor,
        ?string $ip = null,
    ): ConsentimientoTipo {
        return DB::transaction(function () use ($tipo, $attributes, $expectedVersion, $actor, $ip): ConsentimientoTipo {
            $current = ConsentimientoTipo::query()
                ->whereKey($tipo->id)
                ->lockForUpdate()
                ->first();

            if ($current === null) {
                throw new ConsentimientoTipoNotFoundException((int) $tipo->id);
            }

            if ((int) $current->version !== $expectedVersion) {
                throw new ConsentimientoTipoConcurrencyException;
            }

            $before = ConsentimientoTipoSnapshot::from($current);
            $changes = [];

            if (array_key_exists('nombre', $attributes)) {
                $changes['nombre'] = trim((string) $attributes['nombre']);
            }

            if (array_key_exists('descripcion', $attributes)) {
                $changes['descripcion'] = TutorIdentityRule::normalizeOptional($attributes['descripcion']);
            }

            if (array_key_exists('orden', $attributes)) {
                $changes['orden'] = (int) $attributes['orden'];
            }

            if (array_key_exists('activo', $attributes)) {
                $changes['activo'] = (bool) $attributes['activo'];
            }

            $realChanges = [];

            foreach ($changes as $field => $value) {
                $currentValue = match ($field) {
                    'orden' => (int) $current->orden,
                    'activo' => (bool) $current->activo,
                    default => $current->{$field},
                };

                if ($currentValue !== $value) {
                    $realChanges[$field] = $value;
                }
            }

            if ($realChanges === []) {
                return $current;
            }

            $current->fill($realChanges);
            $current->version = (int) $current->version + 1;
            $current->save();

            event(new ConsentimientoTipoUpdated(
                tipoId: (int) $current->id,
                before: $before,
                after: ConsentimientoTipoSnapshot::from($current, array_keys($realChanges)),
                changedFields: array_keys($realChanges),
                actorId: $actor->id,
                ip: $ip,
            ));

            return $current;
        });
    }
}
