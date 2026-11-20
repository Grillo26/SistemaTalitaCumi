<?php

namespace Database\Seeders;

use App\Modules\Organizacion\Enums\ParameterCategory;
use App\Modules\Organizacion\Enums\ParameterType;
use App\Modules\Organizacion\Events\ParameterCreated;
use App\Modules\Organizacion\Models\Parametro;
use App\Modules\Organizacion\Rules\ParameterFixedValueRule;
use App\Modules\Organizacion\Support\ParameterSnapshot;
use Illuminate\Database\Seeder;

/**
 * Catálogo inicial de parámetros (RNF-012).
 *
 * Los valores y rangos marcados PROPUESTO - VALIDAR deben confirmarse con el
 * cliente. El seeder no pisa valores vigentes: solo crea las claves faltantes.
 * Cada alta deja un registro CREAR en bitácora para permitir la reconstrucción
 * de la configuración en una fecha (RNF-013).
 */
final class OrganizacionConfiguracionCatalogSeeder extends Seeder
{
    public function run(): void
    {
        foreach ($this->catalog() as $definition) {
            $parametro = Parametro::query()->firstOrCreate(
                ['clave' => $definition['clave']],
                $definition,
            );

            if (! $parametro->wasRecentlyCreated) {
                continue;
            }

            event(new ParameterCreated(
                parametroId: $parametro->id,
                snapshot: ParameterSnapshot::from($parametro),
                actorId: null,
            ));
        }
    }

    /**
     * @return list<array<string, mixed>>
     */
    private function catalog(): array
    {
        return [
            // Identidad del centro (RF-009).
            [
                'clave' => 'nombre_centro',
                'categoria' => ParameterCategory::IDENTIDAD->value,
                'descripcion' => 'Nombre del centro que aparece en documentos y pantallas.',
                'tipo' => ParameterType::TEXTO->value,
                'valor' => 'TalitaCumi',
                'valor_defecto' => 'TalitaCumi',
                'unidad' => null,
                'minimo' => null,
                'maximo' => '200',
                'sensible' => false,
                'editable' => true,
                'visible_recepcion' => false,
            ],
            [
                'clave' => 'direccion_centro',
                'categoria' => ParameterCategory::IDENTIDAD->value,
                'descripcion' => 'Dirección del centro que aparece en documentos.',
                'tipo' => ParameterType::TEXTO->value,
                'valor' => 'Yacuiba, Bolivia',
                'valor_defecto' => 'Yacuiba, Bolivia',
                'unidad' => null,
                'minimo' => null,
                'maximo' => '500',
                'sensible' => false,
                'editable' => true,
                'visible_recepcion' => false,
            ],
            [
                'clave' => 'telefono_centro',
                'categoria' => ParameterCategory::IDENTIDAD->value,
                'descripcion' => 'Teléfono de contacto del centro.',
                'tipo' => ParameterType::TEXTO->value,
                'valor' => '',
                'valor_defecto' => '',
                'unidad' => null,
                'minimo' => null,
                'maximo' => '30',
                'sensible' => false,
                'editable' => true,
                'visible_recepcion' => false,
            ],
            [
                'clave' => 'centro.logo',
                'categoria' => ParameterCategory::IDENTIDAD->value,
                'descripcion' => 'Logo del centro. Se actualiza únicamente desde la carga de archivo.',
                'tipo' => ParameterType::TEXTO->value,
                'valor' => '',
                'valor_defecto' => '',
                'unidad' => null,
                'minimo' => null,
                'maximo' => null,
                'sensible' => false,
                'editable' => false,
                'visible_recepcion' => false,
            ],

            // Operación.
            [
                'clave' => 'sesion_inactividad_minutos',
                'categoria' => ParameterCategory::OPERACION->value,
                'descripcion' => 'Minutos de inactividad antes de cerrar la sesión.',
                'tipo' => ParameterType::ENTERO->value,
                'valor' => '15',
                'valor_defecto' => '15',
                'unidad' => 'minutos',
                'minimo' => '5',
                'maximo' => '120',
                'sensible' => false,
                'editable' => true,
                'visible_recepcion' => false,
            ],

            // Agenda (RF-006, RF-007 y RF-011).
            [
                'clave' => 'duracion_sesion_minutos',
                'categoria' => ParameterCategory::AGENDA->value,
                'descripcion' => 'Duración por defecto de una sesión individual. PROPUESTO - VALIDAR.',
                'tipo' => ParameterType::ENTERO->value,
                'valor' => '50',
                'valor_defecto' => '50',
                'unidad' => 'minutos',
                'minimo' => '30',
                'maximo' => '90',
                'sensible' => true,
                'editable' => true,
                'visible_recepcion' => true,
            ],
            [
                'clave' => 'agenda.horario_apertura',
                'categoria' => ParameterCategory::AGENDA->value,
                'descripcion' => 'Hora de apertura del centro. PROPUESTO - VALIDAR.',
                'tipo' => ParameterType::HORA->value,
                'valor' => '07:00',
                'valor_defecto' => '07:00',
                'unidad' => null,
                'minimo' => '00:00',
                'maximo' => '23:59',
                'sensible' => false,
                'editable' => true,
                'visible_recepcion' => true,
            ],
            [
                'clave' => 'agenda.horario_cierre',
                'categoria' => ParameterCategory::AGENDA->value,
                'descripcion' => 'Hora de cierre del centro. PROPUESTO - VALIDAR.',
                'tipo' => ParameterType::HORA->value,
                'valor' => '19:00',
                'valor_defecto' => '19:00',
                'unidad' => null,
                'minimo' => '00:00',
                'maximo' => '23:59',
                'sensible' => false,
                'editable' => true,
                'visible_recepcion' => true,
            ],
            [
                'clave' => 'agenda.turno_manana.inicio',
                'categoria' => ParameterCategory::AGENDA->value,
                'descripcion' => 'Hora de inicio del turno mañana. PROPUESTO - VALIDAR.',
                'tipo' => ParameterType::HORA->value,
                'valor' => '08:00',
                'valor_defecto' => '08:00',
                'unidad' => null,
                'minimo' => '00:00',
                'maximo' => '23:59',
                'sensible' => false,
                'editable' => true,
                'visible_recepcion' => true,
            ],
            [
                'clave' => 'agenda.turno_manana.fin',
                'categoria' => ParameterCategory::AGENDA->value,
                'descripcion' => 'Hora de fin del turno mañana. PROPUESTO - VALIDAR.',
                'tipo' => ParameterType::HORA->value,
                'valor' => '12:00',
                'valor_defecto' => '12:00',
                'unidad' => null,
                'minimo' => '00:00',
                'maximo' => '23:59',
                'sensible' => false,
                'editable' => true,
                'visible_recepcion' => true,
            ],
            [
                'clave' => 'agenda.turno_tarde.inicio',
                'categoria' => ParameterCategory::AGENDA->value,
                'descripcion' => 'Hora de inicio del turno tarde. PROPUESTO - VALIDAR.',
                'tipo' => ParameterType::HORA->value,
                'valor' => '14:00',
                'valor_defecto' => '14:00',
                'unidad' => null,
                'minimo' => '00:00',
                'maximo' => '23:59',
                'sensible' => false,
                'editable' => true,
                'visible_recepcion' => true,
            ],
            [
                'clave' => 'agenda.turno_tarde.fin',
                'categoria' => ParameterCategory::AGENDA->value,
                'descripcion' => 'Hora de fin del turno tarde. PROPUESTO - VALIDAR.',
                'tipo' => ParameterType::HORA->value,
                'valor' => '18:00',
                'valor_defecto' => '18:00',
                'unidad' => null,
                'minimo' => '00:00',
                'maximo' => '23:59',
                'sensible' => false,
                'editable' => true,
                'visible_recepcion' => true,
            ],
            [
                'clave' => 'agenda.intervalo_minimo_entre_turnos_minutos',
                'categoria' => ParameterCategory::AGENDA->value,
                'descripcion' => 'Minutos mínimos entre turnos. PROPUESTO - VALIDAR.',
                'tipo' => ParameterType::ENTERO->value,
                'valor' => '0',
                'valor_defecto' => '0',
                'unidad' => 'minutos',
                'minimo' => '0',
                'maximo' => '60',
                'sensible' => false,
                'editable' => true,
                'visible_recepcion' => true,
            ],
            [
                'clave' => 'agenda.dias_semana_habilitados',
                'categoria' => ParameterCategory::AGENDA->value,
                'descripcion' => 'Días habilitados para agendar (1=lunes, 7=domingo). PROPUESTO - VALIDAR.',
                'tipo' => ParameterType::TEXTO->value,
                'valor' => '1,2,3,4,5,6',
                'valor_defecto' => '1,2,3,4,5,6',
                'unidad' => null,
                'minimo' => null,
                'maximo' => null,
                'sensible' => false,
                'editable' => true,
                'visible_recepcion' => true,
            ],
            [
                'clave' => 'agenda.formato_visualizacion',
                'categoria' => ParameterCategory::AGENDA->value,
                'descripcion' => 'Formato de visualización de la agenda. PROPUESTO - VALIDAR.',
                'tipo' => ParameterType::TEXTO->value,
                'valor' => 'semanal',
                'valor_defecto' => 'semanal',
                'unidad' => null,
                'minimo' => null,
                'maximo' => null,
                'sensible' => false,
                'editable' => true,
                'visible_recepcion' => true,
            ],
            [
                'clave' => 'ventana_edicion_asistencia_horas',
                'categoria' => ParameterCategory::AGENDA->value,
                'descripcion' => 'Horas durante las que se puede editar la asistencia.',
                'tipo' => ParameterType::ENTERO->value,
                'valor' => '24',
                'valor_defecto' => '24',
                'unidad' => 'horas',
                'minimo' => '0',
                'maximo' => '72',
                'sensible' => false,
                'editable' => true,
                'visible_recepcion' => false,
            ],

            // Pagos.
            [
                'clave' => 'dias_alerta_vencimiento',
                'categoria' => ParameterCategory::PAGOS->value,
                'descripcion' => 'Días de anticipación para alertas de vencimiento.',
                'tipo' => ParameterType::ENTERO->value,
                'valor' => '7',
                'valor_defecto' => '7',
                'unidad' => 'días',
                'minimo' => '1',
                'maximo' => '30',
                'sensible' => false,
                'editable' => true,
                'visible_recepcion' => true,
            ],
            [
                'clave' => 'mensaje_whatsapp',
                'categoria' => ParameterCategory::PAGOS->value,
                'descripcion' => 'Plantilla de mensaje de WhatsApp para recordatorios de pago.',
                'tipo' => ParameterType::TEXTO->value,
                'valor' => 'Hola {tutor}, le recordamos que el pago de {concepto} vence el {fecha}. Gracias, TalitaCumi.',
                'valor_defecto' => 'Hola {tutor}, le recordamos que el pago de {concepto} vence el {fecha}. Gracias, TalitaCumi.',
                'unidad' => null,
                'minimo' => null,
                'maximo' => '1000',
                'sensible' => false,
                'editable' => true,
                'visible_recepcion' => true,
            ],

            // Formato de documentos (RF-008).
            [
                'clave' => 'comprobante.prefijo',
                'categoria' => ParameterCategory::FORMATO_DOCUMENTOS->value,
                'descripcion' => 'Prefijo de numeración de comprobantes. PROPUESTO - VALIDAR.',
                'tipo' => ParameterType::TEXTO->value,
                'valor' => 'TC',
                'valor_defecto' => 'TC',
                'unidad' => null,
                'minimo' => null,
                'maximo' => null,
                'sensible' => true,
                'editable' => true,
                'visible_recepcion' => false,
            ],
            [
                'clave' => 'comprobante.formato',
                'categoria' => ParameterCategory::FORMATO_DOCUMENTOS->value,
                'descripcion' => 'Formato de numeración de comprobantes. PROPUESTO - VALIDAR.',
                'tipo' => ParameterType::TEXTO->value,
                'valor' => '{prefijo}-{numero:6}',
                'valor_defecto' => '{prefijo}-{numero:6}',
                'unidad' => null,
                'minimo' => null,
                'maximo' => '50',
                'sensible' => true,
                'editable' => true,
                'visible_recepcion' => false,
            ],
            [
                'clave' => ParameterFixedValueRule::LEYENDA_CLAVE,
                'categoria' => ParameterCategory::FORMATO_DOCUMENTOS->value,
                'descripcion' => 'Leyenda obligatoria del comprobante (RN-010).',
                'tipo' => ParameterType::TEXTO->value,
                'valor' => ParameterFixedValueRule::LEYENDA_VALOR,
                'valor_defecto' => ParameterFixedValueRule::LEYENDA_VALOR,
                'unidad' => null,
                'minimo' => null,
                'maximo' => null,
                'sensible' => false,
                'editable' => false,
                'visible_recepcion' => false,
            ],

            // Zona horaria (RF-010 / RN-011).
            [
                'clave' => 'zona_horaria',
                'categoria' => ParameterCategory::ZONA_HORARIA->value,
                'descripcion' => 'Zona horaria del sistema para fechas y reportes.',
                'tipo' => ParameterType::TEXTO->value,
                'valor' => 'America/La_Paz',
                'valor_defecto' => 'America/La_Paz',
                'unidad' => null,
                'minimo' => null,
                'maximo' => null,
                'sensible' => true,
                'editable' => true,
                'visible_recepcion' => false,
            ],

            // Moneda única (RN-003).
            [
                'clave' => ParameterFixedValueRule::MONEDA_CLAVE,
                'categoria' => ParameterCategory::MONEDA->value,
                'descripcion' => 'Moneda única del sistema (boliviano).',
                'tipo' => ParameterType::TEXTO->value,
                'valor' => ParameterFixedValueRule::MONEDA_VALOR,
                'valor_defecto' => ParameterFixedValueRule::MONEDA_VALOR,
                'unidad' => 'Bs',
                'minimo' => null,
                'maximo' => null,
                'sensible' => false,
                'editable' => false,
                'visible_recepcion' => true,
            ],
        ];
    }
}
