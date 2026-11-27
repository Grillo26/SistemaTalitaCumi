<?php

namespace Database\Seeders;

use App\Modules\AgendaTurnos\Support\WhatsappTurnoLink;
use App\Modules\Organizacion\Enums\ParameterCategory;
use App\Modules\Organizacion\Enums\ParameterType;
use App\Modules\Organizacion\Events\ParameterCreated;
use App\Modules\Organizacion\Models\Parametro;
use App\Modules\Organizacion\Support\ParameterSnapshot;
use Illuminate\Database\Seeder;

/**
 * [Reprogramación y Cancelación] RF-015.
 *
 * El prefijo de país queda vacío a propósito: debe configurarlo el centro
 * (por ejemplo, 591 en Bolivia). Sin valor no se genera el enlace wa.me.
 */
final class CambiosTurnosConfiguracionSeeder extends Seeder
{
    public function run(): void
    {
        $parametro = Parametro::query()->firstOrCreate(
            ['clave' => WhatsappTurnoLink::PREFIJO_PAIS],
            [
                'clave' => WhatsappTurnoLink::PREFIJO_PAIS,
                'categoria' => ParameterCategory::AGENDA->value,
                'descripcion' => 'Prefijo de país para enlaces wa.me de cambios de turno (sin signo +).',
                'tipo' => ParameterType::TEXTO->value,
                'valor' => '',
                'valor_defecto' => '',
                'unidad' => null,
                'minimo' => null,
                'maximo' => '5',
                'sensible' => false,
                'editable' => true,
                'visible_recepcion' => false,
            ],
        );

        if ($parametro->wasRecentlyCreated) {
            event(new ParameterCreated(
                parametroId: $parametro->id,
                snapshot: ParameterSnapshot::from($parametro),
                actorId: null,
            ));
        }
    }
}
