<?php

namespace App\Modules\InscripcionesAtenciones\Actions;

use App\Models\User;
use App\Modules\InscripcionesAtenciones\Rules\VentanaAlertaRule;
use App\Modules\InscripcionesAtenciones\Support\VigenciaSettings;
use App\Modules\Organizacion\Contracts\ParameterAdminContract;
use Illuminate\Support\Facades\DB;

/**
 * [Vigencia y Renovación] RF-003 / RN-007 / RNF-011.
 *
 * Delegates la escritura en la configuración de Organización para conservar
 * validación, versión, caché y bitácora.
 */
final class UpdateVentanaAlertaAction
{
    public function __construct(private readonly ParameterAdminContract $parameters) {}

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    public function __invoke(array $data, User $actor): array
    {
        $dias = (int) $data['valor'];
        VentanaAlertaRule::assertValida($dias);

        return DB::transaction(function () use ($dias, $data, $actor): array {
            return $this->parameters->actualizar(
                clave: VigenciaSettings::VENTANA_ALERTA_DIAS,
                valor: (string) $dias,
                version: (int) $data['version'],
                actorId: $actor->id,
                motivo: 'Cambio de ventana de alerta de vigencias',
            )->toArray();
        });
    }
}
