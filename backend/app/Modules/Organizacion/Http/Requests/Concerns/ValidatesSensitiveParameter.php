<?php

namespace App\Modules\Organizacion\Http\Requests\Concerns;

use App\Modules\Organizacion\Models\Parametro;
use Illuminate\Validation\Validator;

/**
 * Confirmación y motivo obligatorios en parámetros sensibles (RF-016 / RN-006).
 */
trait ValidatesSensitiveParameter
{
    protected function addSensitiveErrors(Validator $validator, ?Parametro $parametro): void
    {
        if ($parametro === null || ! $parametro->sensible) {
            return;
        }

        $confirmado = filter_var($this->input('confirmado', false), FILTER_VALIDATE_BOOLEAN);

        if (! $confirmado) {
            $validator->errors()->add(
                'confirmado',
                'Este parámetro es sensible: debés confirmar explícitamente el cambio.',
            );
        }

        if (trim((string) $this->input('motivo', '')) === '') {
            $validator->errors()->add(
                'motivo',
                'Este parámetro es sensible: el motivo del cambio es obligatorio.',
            );
        }
    }
}
