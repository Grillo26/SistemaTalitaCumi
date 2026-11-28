<?php

namespace App\Modules\AgendaTurnos\Http\Requests;

use App\Modules\AgendaTurnos\Enums\TipoOrigenTurno;
use App\Modules\InscripcionesAtenciones\Support\InscripcionPermission;
use App\Modules\InscripcionesAtenciones\Support\PaquetePermission;
use Illuminate\Foundation\Http\FormRequest;

/**
 * [Programación de Turnos] RF-002 / RF-008 / RF-013 / RT-001 / RNF-012.
 *
 * `clave_idempotencia` es obligatoria para que el doble envío no duplique
 * turnos. `confirmar_advertencias` habilita la confirmación de advertencias
 * de sala o de sesiones faltantes.
 */
final class GenerarTurnosRequest extends FormRequest
{
    public function authorize(): bool
    {
        $tipo = TipoOrigenTurno::tryFrom((string) $this->route('tipo'));

        if ($tipo === null) {
            return false;
        }

        $permiso = $tipo === TipoOrigenTurno::PAQUETE_SESIONES
            ? PaquetePermission::VIEW
            : InscripcionPermission::VIEW;

        return (bool) $this->user()?->can($permiso);
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'clave_idempotencia' => ['required', 'uuid'],
            'confirmar_advertencias' => ['sometimes', 'boolean'],
            'profesional_id' => ['sometimes', 'nullable', 'integer', 'exists:profesional,id'],
            'usar_profesional_origen' => ['sometimes', 'boolean'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'clave_idempotencia.required' => 'Falta la clave de idempotencia.',
            'clave_idempotencia.uuid' => 'La clave de idempotencia no es válida.',
            'profesional_id.exists' => 'El profesional seleccionado no existe.',
        ];
    }
}
