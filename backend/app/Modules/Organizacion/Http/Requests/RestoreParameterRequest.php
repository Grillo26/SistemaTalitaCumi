<?php

namespace App\Modules\Organizacion\Http\Requests;

use App\Modules\Organizacion\Http\Requests\Concerns\ValidatesSensitiveParameter;
use App\Modules\Organizacion\Models\Parametro;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;

/**
 * Restauración de un parámetro a su valor por defecto (RF-005 / RF-016).
 */
final class RestoreParameterRequest extends FormRequest
{
    use ValidatesSensitiveParameter;

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
            'version' => ['required', 'integer', 'min:1'],
            'confirmado' => ['sometimes', 'boolean'],
            'motivo' => ['sometimes', 'nullable', 'string', 'max:1000'],
        ];
    }

    /**
     * @return array<int, callable>
     */
    public function after(): array
    {
        $id = $this->route('id');

        if (! is_numeric($id)) {
            return [];
        }

        $parametro = Parametro::query()->find((int) $id);

        return [
            function (Validator $validator) use ($parametro): void {
                $this->addSensitiveErrors($validator, $parametro);
            },
        ];
    }
}
