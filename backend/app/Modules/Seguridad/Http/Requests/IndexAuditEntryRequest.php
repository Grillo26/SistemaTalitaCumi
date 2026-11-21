<?php

namespace App\Modules\Seguridad\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

/**
 * Filtros de consulta de bitácora (02/RF-B04).
 */
final class IndexAuditEntryRequest extends FormRequest
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
            'fecha_desde' => ['nullable', 'date_format:Y-m-d'],
            'fecha_hasta' => ['nullable', 'date_format:Y-m-d'],
            'usuario_id' => ['nullable', 'integer', Rule::exists('users', 'id')],
            'modulo' => ['nullable', 'string', 'max:50'],
            'entidad' => ['nullable', 'string', 'max:100'],
            'entidad_id' => ['nullable', 'integer', 'min:1'],
            'accion' => ['nullable', 'string', 'max:100'],
            'resultado' => ['nullable', Rule::in(['exito', 'fallo', 'denegado'])],
            'critico' => ['nullable', 'boolean'],
            'sucursal_id' => ['nullable', 'integer', Rule::exists('sucursal', 'id')],
            'page' => ['nullable', 'integer', 'min:1'],
            'per_page' => ['nullable', 'integer', 'min:1', 'max:50'],
        ];
    }

    /**
     * @return array<int, callable>
     */
    public function after(): array
    {
        return [
            function (Validator $validator): void {
                if (! $this->filled('fecha_desde') || ! $this->filled('fecha_hasta')) {
                    return;
                }

                if ((string) $this->input('fecha_hasta') < (string) $this->input('fecha_desde')) {
                    $validator->errors()->add(
                        'fecha_hasta',
                        'La fecha hasta no puede ser anterior a la fecha desde.',
                    );
                }
            },
        ];
    }
}
