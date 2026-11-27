<?php

namespace App\Modules\AgendaTurnos\Http\Requests;

use App\Modules\AgendaTurnos\Enums\TipoOrigenTurno;
use App\Modules\InscripcionesAtenciones\Support\InscripcionPermission;
use App\Modules\InscripcionesAtenciones\Support\PaquetePermission;
use Illuminate\Foundation\Http\FormRequest;

/**
 * [Programación de Turnos] RF-003 / RF-008 / RF-013.
 *
 * Además de `turno.generar` (middleware), exige permiso para ver la entidad
 * origen. El alcance por sucursal se valida en la Query/Action.
 */
final class PreviewGeneracionRequest extends FormRequest
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
            'profesional_id' => ['sometimes', 'nullable', 'integer', 'exists:profesional,id'],
            'usar_profesional_origen' => ['sometimes', 'boolean'],
        ];
    }
}
