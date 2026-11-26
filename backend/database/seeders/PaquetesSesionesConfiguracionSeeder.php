<?php

namespace Database\Seeders;

use App\Modules\InscripcionesAtenciones\Support\PaqueteSettings;
use App\Modules\Organizacion\Enums\ParameterCategory;
use App\Modules\Organizacion\Enums\ParameterType;
use App\Modules\Organizacion\Events\ParameterCreated;
use App\Modules\Organizacion\Models\Parametro;
use App\Modules\Organizacion\Support\ParameterSnapshot;
use Illuminate\Database\Seeder;

/**
 * Default de cantidad de sesiones de paquetes.
 *
 * No se pisa un valor existente; la duración por defecto se reutiliza de
 * `duracion_sesion_minutos`, ya creada por Organización.
 */
final class PaquetesSesionesConfiguracionSeeder extends Seeder
{
    public function run(): void
    {
        $parametro = Parametro::query()->firstOrCreate(
            ['clave' => PaqueteSettings::CANTIDAD_SESIONES_DEFAULT],
            [
                'clave' => PaqueteSettings::CANTIDAD_SESIONES_DEFAULT,
                'categoria' => ParameterCategory::AGENDA->value,
                'descripcion' => 'Cantidad de sesiones por defecto de un paquete individual. PROPUESTO - VALIDAR.',
                'tipo' => ParameterType::ENTERO->value,
                'valor' => '10',
                'valor_defecto' => '10',
                'unidad' => 'sesiones',
                'minimo' => '1',
                'maximo' => '100',
                'sensible' => false,
                'editable' => true,
                'visible_recepcion' => true,
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
