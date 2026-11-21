<?php

namespace App\Modules\Seguridad\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Reconstrucción de estado por entidad y fecha (02/RF-B11).
 */
final class ReconstructAuditEntityRequest extends FormRequest
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
            'entidad' => ['required', 'string', 'max:100'],
            'entidad_id' => ['required', 'integer', 'min:1'],
            'fecha' => ['required', 'date_format:Y-m-d'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'entidad.required' => 'Indicá la entidad a reconstruir.',
            'entidad_id.required' => 'Indicá el identificador de la entidad.',
            'fecha.required' => 'Indicá la fecha de reconstrucción.',
            'fecha.date_format' => 'La fecha debe tener el formato AAAA-MM-DD.',
        ];
    }
}
