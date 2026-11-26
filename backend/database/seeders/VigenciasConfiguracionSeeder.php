<?php

namespace Database\Seeders;

use App\Modules\InscripcionesAtenciones\Support\VigenciaSettings;
use App\Modules\Organizacion\Enums\ParameterCategory;
use App\Modules\Organizacion\Enums\ParameterType;
use App\Modules\Organizacion\Events\ParameterCreated;
use App\Modules\Organizacion\Models\Parametro;
use App\Modules\Organizacion\Support\ParameterSnapshot;
use Illuminate\Database\Seeder;

/**
 * Ventana de alerta inicial para vigencias.
 *
 * No pisa un valor existente. El 7 es un dato inicial de configuración, no
 * una constante de la Rule.
 */
final class VigenciasConfiguracionSeeder extends Seeder
{
    public function run(): void
    {
        $parametro = Parametro::query()->firstOrCreate(
            ['clave' => VigenciaSettings::VENTANA_ALERTA_DIAS],
            [
                'clave' => VigenciaSettings::VENTANA_ALERTA_DIAS,
                'categoria' => ParameterCategory::OPERACION->value,
                'descripcion' => 'Días de antelación para clasificar una atención como "Por vencer".',
                'tipo' => ParameterType::ENTERO->value,
                'valor' => '7',
                'valor_defecto' => '7',
                'unidad' => 'días',
                'minimo' => '1',
                'maximo' => '365',
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
