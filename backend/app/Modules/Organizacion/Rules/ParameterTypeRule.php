<?php

namespace App\Modules\Organizacion\Rules;

use App\Modules\Organizacion\Enums\ParameterType;
use App\Modules\Organizacion\Support\ParameterValueCaster;
use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

/**
 * Validación pura del tipo declarado (RF-004 / CA-008).
 */
final class ParameterTypeRule implements ValidationRule
{
    public function __construct(private readonly ParameterType $type) {}

    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if (ParameterValueCaster::isValid($this->type, $value)) {
            return;
        }

        $message = match ($this->type) {
            ParameterType::ENTERO => 'El valor debe ser un número entero.',
            ParameterType::DECIMAL => 'El valor debe ser un número decimal (usá punto como separador).',
            ParameterType::HORA => 'El valor debe ser una hora válida en formato HH:MM (por ejemplo 08:00).',
            ParameterType::BOOLEANO => 'El valor debe ser verdadero o falso.',
            ParameterType::TEXTO => 'El valor debe ser texto.',
        };

        $fail($message);
    }
}
