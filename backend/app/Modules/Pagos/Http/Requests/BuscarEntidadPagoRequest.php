<?php

namespace App\Modules\Pagos\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Búsqueda de entidades a pagar (08.01/RF-006).
 */
final class BuscarEntidadPagoRequest extends FormRequest
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
            'q' => ['required', 'string', 'min:2', 'max:100'],
        ];
    }
}
