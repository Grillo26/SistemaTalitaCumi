<?php

namespace App\Modules\CatalogoServicios\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Listado de áreas (RF-C09).
 */
final class IndexAreaRequest extends FormRequest
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
            'search' => ['nullable', 'string', 'max:150'],
            'estado' => ['nullable', 'in:activo,inactivo'],
            'sort' => ['nullable', 'in:nombre,activo,updated_at'],
            'direction' => ['nullable', 'in:asc,desc'],
        ];
    }
}
