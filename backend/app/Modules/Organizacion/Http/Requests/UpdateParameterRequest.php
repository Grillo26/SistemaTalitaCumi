<?php

namespace App\Modules\Organizacion\Http\Requests;

use App\Modules\Organizacion\Http\Requests\Concerns\ValidatesSensitiveParameter;
use App\Modules\Organizacion\Models\Parametro;
use App\Modules\Organizacion\Rules\ParameterRangeRule;
use App\Modules\Organizacion\Rules\ParameterTypeRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;

/**
 * Edición de un parámetro (RF-003, RF-004, RF-011 y RF-016).
 *
 * El tipo y el rango se toman del catálogo vigente. La Action vuelve a
 * validar en backend como defensa adicional (RNF-004).
 */
final class UpdateParameterRequest extends FormRequest
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
        $rules = [
            'valor' => ['present'],
            'version' => ['required', 'integer', 'min:1'],
            'confirmado' => ['sometimes', 'boolean'],
            'motivo' => ['sometimes', 'nullable', 'string', 'max:1000'],
            'clave' => ['prohibited'],
            'tipo' => ['prohibited'],
            'categoria' => ['prohibited'],
            'valor_defecto' => ['prohibited'],
            'editable' => ['prohibited'],
        ];

        $parametro = $this->parametro();

        if ($parametro === null) {
            return $rules;
        }

        $rules['valor'][] = new ParameterTypeRule($parametro->tipo);
        $rules['valor'][] = new ParameterRangeRule(
            $parametro->tipo,
            $parametro->minimo,
            $parametro->maximo,
            $parametro->unidad,
        );

        foreach ($this->catalogRules($parametro->clave) as $field => $extraRules) {
            $rules[$field] = array_merge($rules[$field] ?? [], $extraRules);
        }

        return $rules;
    }

    /**
     * @return array<int, callable>
     */
    public function after(): array
    {
        $parametro = $this->parametro();

        if ($parametro === null) {
            return [];
        }

        return [
            function (Validator $validator) use ($parametro): void {
                $this->addSensitiveErrors($validator, $parametro);

                if (! $parametro->editable) {
                    $validator->errors()->add(
                        'valor',
                        'Este parámetro es de solo lectura y no puede editarse desde esta pantalla.',
                    );
                }

                $valor = (string) $this->input('valor', '');

                if (in_array($parametro->clave, ['nombre_centro', 'direccion_centro'], true)
                    && trim($valor) === '') {
                    $validator->errors()->add('valor', 'Este dato del centro es obligatorio.');
                }

                if ($parametro->clave === 'agenda.dias_semana_habilitados') {
                    $days = array_filter(array_map('trim', explode(',', $valor)));

                    if (count($days) !== count(array_unique($days))) {
                        $validator->errors()->add('valor', 'No repitas días de la semana.');
                    }
                }

                if ($parametro->clave === 'comprobante.formato') {
                    $error = $this->comprobanteFormatError($valor);

                    if ($error !== null) {
                        $validator->errors()->add('valor', $error);
                    }
                }
            },
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function catalogRules(string $clave): array
    {
        return match ($clave) {
            'agenda.dias_semana_habilitados' => [
                'valor' => ['regex:/^[1-7](,[1-7])*$/'],
            ],
            'agenda.formato_visualizacion' => [
                'valor' => ['in:semanal,mensual'],
            ],
            'zona_horaria' => [
                'valor' => ['timezone'],
            ],
            'comprobante.prefijo' => [
                'valor' => ['regex:/^[A-Za-z0-9]{1,10}$/'],
            ],
            'comprobante.formato' => [
                'valor' => ['string', 'max:50'],
            ],
            default => [],
        };
    }

    private function parametro(): ?Parametro
    {
        $id = $this->route('id');

        return is_numeric($id) ? Parametro::query()->find((int) $id) : null;
    }

    private function comprobanteFormatError(string $valor): ?string
    {
        if (! str_contains($valor, '{numero')) {
            return 'El formato debe incluir el marcador {numero}.';
        }

        $rest = preg_replace('/\{(prefijo|numero)(:\d+)?\}/', '', $valor);

        if ($rest === null || str_contains($rest, '{') || str_contains($rest, '}')) {
            return 'El formato solo admite los marcadores {prefijo} y {numero} (opcionalmente {numero:N}).';
        }

        return null;
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'valor.present' => 'El valor es obligatorio.',
            'valor.regex' => 'El formato del valor no es válido.',
            'valor.in' => 'El valor debe ser semanal o mensual.',
            'valor.timezone' => 'Seleccioná una zona horaria válida.',
            'version.required' => 'La versión es obligatoria para controlar ediciones concurrentes.',
            'clave.prohibited' => 'La clave del parámetro no se puede cambiar.',
            'tipo.prohibited' => 'El tipo del parámetro no se puede cambiar.',
            'categoria.prohibited' => 'La categoría del parámetro no se puede cambiar.',
            'valor_defecto.prohibited' => 'El valor por defecto no se edita desde esta pantalla.',
            'editable.prohibited' => 'La editabilidad no se cambia desde esta pantalla.',
        ];
    }
}
