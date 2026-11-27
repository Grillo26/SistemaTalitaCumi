<?php

namespace App\Modules\InscripcionesAtenciones\Http\Requests;

use App\Modules\InscripcionesAtenciones\Rules\VentanaAlertaRule;
use Illuminate\Foundation\Http\FormRequest;

/**
 * [Vigencia y Renovación] RF-003 / RN-007.
 */
final class UpdateVentanaAlertaRequest extends FormRequest
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
            'valor' => ['required', 'integer', 'between:'.VentanaAlertaRule::MINIMO.','.VentanaAlertaRule::MAXIMO],
            'version' => ['required', 'integer', 'min:1'],
        ];
    }
}
