<?php

namespace App\Modules\CatalogoServicios\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Listado de tipos de servicio (RN-C09).
 */
final class IndexTipoServicioRequest extends FormRequest
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
            'search' => ['nullable', 'string', 'max:100'],
            'estado' => ['nullable', 'in:activo,inactivo'],
            'sort' => ['nullable', 'in:nombre,activo,updated_at'],
            'direction' => ['nullable', 'in:asc,desc'],
        ];
    }
}
