<?php

namespace App\Modules\Organizacion\Actions;

use App\Modules\Organizacion\Events\ParameterRestored;
use App\Modules\Organizacion\Exceptions\ParameterAlreadyDefaultException;
use App\Modules\Organizacion\Exceptions\ParameterCoherenceException;
use App\Modules\Organizacion\Exceptions\ParameterConcurrencyException;
use App\Modules\Organizacion\Exceptions\ParameterNotFoundException;
use App\Modules\Organizacion\Exceptions\ParameterOutOfRangeException;
use App\Modules\Organizacion\Exceptions\SensitiveParameterConfirmationRequiredException;
use App\Modules\Organizacion\Models\Parametro;
use App\Modules\Organizacion\Rules\ParameterCoherenceRule;
use App\Modules\Organizacion\Rules\ParameterEditabilityRule;
use App\Modules\Organizacion\Rules\ParameterRangeRule;
use App\Modules\Organizacion\Rules\SensitiveParameterRule;
use App\Modules\Organizacion\Support\ParameterSnapshot;
use Illuminate\Support\Facades\DB;

/**
 * Restaurar un parámetro a su valor por defecto (RF-005 / RN-008 / CU-02).
 */
final class RestoreParameterDefaultAction
{
    public function __invoke(
        int $id,
        int $expectedVersion,
        bool $confirmado,
        ?string $motivo,
        ?int $actorId,
    ): Parametro {
        return DB::transaction(function () use ($id, $expectedVersion, $confirmado, $motivo, $actorId): Parametro {
            $parametro = Parametro::query()->whereKey($id)->lockForUpdate()->first();

            if ($parametro === null) {
                throw new ParameterNotFoundException($id);
            }

            if ($parametro->version !== $expectedVersion) {
                throw new ParameterConcurrencyException;
            }

            ParameterEditabilityRule::assertEditable($parametro->editable, $parametro->clave);

            if ($parametro->valor === $parametro->valor_defecto) {
                throw new ParameterAlreadyDefaultException($parametro->clave);
            }

            $rangeError = ParameterRangeRule::error(
                $parametro->tipo,
                $parametro->valor_defecto,
                $parametro->minimo,
                $parametro->maximo,
                $parametro->unidad,
            );

            if ($rangeError !== null) {
                throw new ParameterOutOfRangeException($parametro->clave, $rangeError);
            }

            $motivo = SensitiveParameterRule::normalizedReason($motivo);

            if (SensitiveParameterRule::error($parametro->sensible, $confirmado, $motivo) !== null) {
                throw new SensitiveParameterConfirmationRequiredException($parametro->clave);
            }

            if (ParameterCoherenceRule::affectsAgenda($parametro->clave)) {
                $values = Parametro::query()
                    ->whereIn('clave', ParameterCoherenceRule::agendaKeys())
                    ->pluck('valor', 'clave')
                    ->all();

                $coherenceError = ParameterCoherenceRule::check(
                    $values,
                    $parametro->clave,
                    $parametro->valor_defecto,
                );

                if ($coherenceError !== null) {
                    throw new ParameterCoherenceException($coherenceError);
                }
            }

            $before = ParameterSnapshot::from($parametro);

            $parametro->valor = $parametro->valor_defecto;
            $parametro->version = $parametro->version + 1;
            $parametro->save();
            $parametro->refresh();

            event(new ParameterRestored(
                parametroId: $parametro->id,
                before: $before,
                after: ParameterSnapshot::from($parametro),
                motivo: $motivo,
                actorId: $actorId,
            ));

            return $parametro;
        });
    }
}
