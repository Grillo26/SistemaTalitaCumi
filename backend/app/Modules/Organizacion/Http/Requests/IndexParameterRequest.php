<?php

namespace App\Modules\Organizacion\Http\Requests;

use App\Modules\Organizacion\Enums\ParameterCategory;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Listado y filtros de parámetros (RF-001/RF-002).
 */
final class IndexParameterRequest extends FormRequest
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
            'categoria' => ['nullable', Rule::in(ParameterCategory::values())],
        ];
    }
}
