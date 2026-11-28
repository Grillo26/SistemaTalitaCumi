<?php

namespace App\Modules\Pagos\Http\Requests;

use App\Modules\Pagos\Enums\TipoEntidadPago;
use App\Modules\Pagos\Rules\FechaPagoRule;
use App\Modules\Pagos\Rules\PagoLineasRule;
use Illuminate\Foundation\Http\FormRequest;

/**
 * Formato del registro de pago. Las reglas de negocio viven en las Rules y la Action.
 *
 * El monto se envía como texto decimal; la Action rechaza más de dos decimales
 * (no redondea).
 */
final class StorePagoRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'tipo_entidad' => ['required', 'string', 'in:'.implode(',', TipoEntidadPago::values())],
            'entidad_id' => ['required', 'integer', 'min:1'],
            'clave_idempotencia' => ['required', 'string', 'min:8', 'max:64', 'regex:/^[A-Za-z0-9\-_]+$/'],
            'fecha_pago' => ['nullable', 'date_format:Y-m-d'],
            'tutor_id' => ['nullable', 'integer', 'min:1'],
            'motivo_fecha_futura' => ['nullable', 'string', 'max:'.FechaPagoRule::MOTIVO_MAXIMO],
            'lineas' => ['required', 'array', 'min:1', 'max:'.PagoLineasRule::MAXIMO_LINEAS],
            'lineas.*.medio_pago' => ['required', 'string', 'max:20'],
            'lineas.*.monto' => ['required'],
            'lineas.*.referencia' => ['nullable', 'string', 'max:'.PagoLineasRule::REFERENCIA_MAXIMA],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'clave_idempotencia.required' => 'La clave de registro es obligatoria.',
            'clave_idempotencia.regex' => 'La clave de registro solo admite letras, números, guiones y guiones bajos.',
            'lineas.max' => 'Un pago admite como máximo dos líneas (efectivo y QR).',
        ];
    }
}
