<?php

namespace App\Modules\Organizacion\Actions;

use App\Modules\Organizacion\Events\ParameterUpdated;
use App\Modules\Organizacion\Exceptions\ParameterCoherenceException;
use App\Modules\Organizacion\Exceptions\ParameterConcurrencyException;
use App\Modules\Organizacion\Exceptions\ParameterInvalidValueException;
use App\Modules\Organizacion\Exceptions\ParameterNotEditableException;
use App\Modules\Organizacion\Exceptions\ParameterNotFoundException;
use App\Modules\Organizacion\Exceptions\ParameterOutOfRangeException;
use App\Modules\Organizacion\Exceptions\SensitiveParameterConfirmationRequiredException;
use App\Modules\Organizacion\Models\Parametro;
use App\Modules\Organizacion\Rules\ParameterCoherenceRule;
use App\Modules\Organizacion\Rules\ParameterEditabilityRule;
use App\Modules\Organizacion\Rules\ParameterFixedValueRule;
use App\Modules\Organizacion\Rules\ParameterRangeRule;
use App\Modules\Organizacion\Rules\SensitiveParameterRule;
use App\Modules\Organizacion\Support\ParameterSnapshot;
use App\Modules\Organizacion\Support\ParameterValueCaster;
use Illuminate\Support\Facades\DB;

/**
 * Editar el valor de un parámetro (RF-003, RF-004, RF-006..RF-011, RF-016).
 *
 * Valida tipo, rango, coherencia, editabilidad y confirmación de sensibles en
 * backend. La bitácora se escribe en la misma transacción mediante listener.
 */
final class UpdateParameterAction
{
    /**
     * @param  array<string, mixed>  $attributes
     */
    public function __invoke(
        int $id,
        array $attributes,
        int $expectedVersion,
        ?int $actorId,
    ): Parametro {
        return DB::transaction(function () use ($id, $attributes, $expectedVersion, $actorId): Parametro {
            $parametro = Parametro::query()->whereKey($id)->lockForUpdate()->first();

            if ($parametro === null) {
                throw new ParameterNotFoundException($id);
            }

            if ($parametro->version !== $expectedVersion) {
                throw new ParameterConcurrencyException;
            }

            ParameterEditabilityRule::assertEditable($parametro->editable, $parametro->clave);

            $rawValue = $attributes['valor'] ?? null;

            if (! ParameterValueCaster::isValid($parametro->tipo, $rawValue)) {
                throw new ParameterInvalidValueException($parametro->clave, $parametro->tipo->label());
            }

            $valor = ParameterValueCaster::normalize($parametro->tipo, $rawValue);

            if (ParameterFixedValueRule::isFixed($parametro->clave)
                && ! ParameterFixedValueRule::matches($parametro->clave, $valor)) {
                throw new ParameterNotEditableException($parametro->clave);
            }

            $rangeError = ParameterRangeRule::error(
                $parametro->tipo,
                $valor,
                $parametro->minimo,
                $parametro->maximo,
                $parametro->unidad,
            );

            if ($rangeError !== null) {
                throw new ParameterOutOfRangeException($parametro->clave, $rangeError);
            }

            if ($valor === $parametro->valor) {
                return $parametro;
            }

            $confirmado = filter_var($attributes['confirmado'] ?? false, FILTER_VALIDATE_BOOLEAN);
            $motivo = SensitiveParameterRule::normalizedReason($attributes['motivo'] ?? null);

            if (SensitiveParameterRule::error($parametro->sensible, $confirmado, $motivo) !== null) {
                throw new SensitiveParameterConfirmationRequiredException($parametro->clave);
            }

            if (ParameterCoherenceRule::affectsAgenda($parametro->clave)) {
                $values = Parametro::query()
                    ->whereIn('clave', ParameterCoherenceRule::agendaKeys())
                    ->pluck('valor', 'clave')
                    ->all();

                $coherenceError = ParameterCoherenceRule::check($values, $parametro->clave, $valor);

                if ($coherenceError !== null) {
                    throw new ParameterCoherenceException($coherenceError);
                }
            }

            $before = ParameterSnapshot::from($parametro);

            $parametro->valor = $valor;
            $parametro->version = $parametro->version + 1;
            $parametro->save();
            $parametro->refresh();

            event(new ParameterUpdated(
                parametroId: $parametro->id,
                before: $before,
                after: ParameterSnapshot::from($parametro),
                changedFields: ['valor'],
                motivo: $motivo,
                actorId: $actorId,
            ));

            return $parametro;
        });
    }
}
