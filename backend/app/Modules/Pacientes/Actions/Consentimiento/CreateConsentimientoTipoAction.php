<?php

namespace App\Modules\Pacientes\Actions\Consentimiento;

use App\Models\User;
use App\Modules\Pacientes\Events\Consentimiento\ConsentimientoTipoCreated;
use App\Modules\Pacientes\Exceptions\ConsentimientoTipoDuplicateException;
use App\Modules\Pacientes\Models\ConsentimientoTipo;
use App\Modules\Pacientes\Rules\TutorIdentityRule;
use App\Modules\Pacientes\Support\ConsentimientoTipoSnapshot;
use Illuminate\Support\Facades\DB;

/**
 * Crea un tipo de consentimiento (04.03 Fase B).
 */
final class CreateConsentimientoTipoAction
{
    /**
     * @param  array<string, mixed>  $attributes
     */
    public function __invoke(array $attributes, User $actor, ?string $ip = null): ConsentimientoTipo
    {
        return DB::transaction(function () use ($attributes, $actor, $ip): ConsentimientoTipo {
            $codigo = strtolower(trim((string) $attributes['codigo']));

            if (ConsentimientoTipo::query()->where('codigo', $codigo)->exists()) {
                throw new ConsentimientoTipoDuplicateException($codigo);
            }

            $tipo = ConsentimientoTipo::query()->create([
                'codigo' => $codigo,
                'nombre' => trim((string) $attributes['nombre']),
                'descripcion' => TutorIdentityRule::normalizeOptional($attributes['descripcion'] ?? null),
                'orden' => (int) ($attributes['orden'] ?? 0),
                'activo' => (bool) ($attributes['activo'] ?? true),
                'version' => 1,
            ]);

            event(new ConsentimientoTipoCreated(
                tipoId: (int) $tipo->id,
                snapshot: ConsentimientoTipoSnapshot::from($tipo),
                actorId: $actor->id,
                ip: $ip,
            ));

            return $tipo;
        });
    }
}
