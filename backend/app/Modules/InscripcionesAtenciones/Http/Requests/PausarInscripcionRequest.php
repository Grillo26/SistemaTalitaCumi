<?php

namespace App\Modules\InscripcionesAtenciones\Http\Requests;

use App\Modules\InscripcionesAtenciones\Enums\EstadoInscripcion;
use App\Modules\InscripcionesAtenciones\Enums\MotivoPausa;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * [Estados Paciente e Inscripción] RF-004 / RF-006 / CA-001.
 */
final class PausarInscripcionRequest extends FormRequest
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
            'estado_esperado' => ['required', Rule::enum(EstadoInscripcion::class)],
            'motivo' => ['required', Rule::in(MotivoPausa::values())],
            'motivo_detalle' => ['nullable', 'string', 'max:500'],
        ];
    }
}
