<?php

namespace App\Modules\InscripcionesAtenciones\Queries;

use App\Models\User;
use App\Modules\InscripcionesAtenciones\Enums\DiaSemana;
use App\Modules\InscripcionesAtenciones\Exceptions\ComprobanteConfiguracionIncompletaException;
use App\Modules\InscripcionesAtenciones\Support\PaqueteDetalle;
use App\Modules\InscripcionesAtenciones\Support\PaqueteSnapshot;
use App\Modules\Organizacion\Contracts\ParameterReaderContract;
use App\Modules\Organizacion\Exceptions\ParameterNotFoundException;
use App\Modules\Organizacion\Support\ParameterValue;
use Illuminate\Support\Facades\Storage;

/**
 * RF-015: datos reales del comprobante no fiscal de un paquete.
 */
final class GetPaqueteComprobanteDataQuery
{
    public function __construct(
        private readonly GetPaqueteQuery $detalle,
        private readonly ParameterReaderContract $parameters,
    ) {}

    /**
     * @return array<string, mixed>
     */
    public function __invoke(int $id, User $actor): array
    {
        $detalle = ($this->detalle)($id, $actor);

        $keys = [
            'nombre_centro',
            'direccion_centro',
            'telefono_centro',
            'centro.logo',
            'comprobante.leyenda_no_factura',
            'moneda',
        ];

        $values = [];
        $missing = [];

        foreach ($keys as $key) {
            try {
                $values[$key] = $this->parameters->obtener($key);
            } catch (ParameterNotFoundException) {
                $missing[] = $key;
            }
        }

        if ($missing !== []) {
            throw new ComprobanteConfiguracionIncompletaException($missing);
        }

        $logo = $this->stringValue($values['centro.logo']);

        return [
            'centro' => [
                'nombre' => $this->stringValue($values['nombre_centro']),
                'direccion' => $this->stringValue($values['direccion_centro']),
                'telefono' => $this->stringValue($values['telefono_centro']),
                'logo_url' => $logo !== '' ? Storage::disk('public')->url($logo) : null,
            ],
            'moneda' => [
                'codigo' => $this->stringValue($values['moneda']),
                'simbolo' => $values['moneda']->unidad ?? 'Bs',
            ],
            'leyenda_no_factura' => $this->stringValue($values['comprobante.leyenda_no_factura']),
            'fecha_emision' => now()->toDateString(),
            'paquete' => $this->detalleToArray($detalle),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function detalleToArray(PaqueteDetalle $detalle): array
    {
        $paquete = $detalle->paquete;
        $estado = $paquete->estado->mostrado(
            $paquete->fecha_fin->toDateString(),
            now()->toDateString(),
        );

        return [
            'id' => (int) $paquete->id,
            'cantidad_sesiones' => (int) $paquete->cantidad_sesiones,
            'dias_semana' => $paquete->dias_semana,
            'dias_semana_labels' => array_map(
                static fn (int $dia): string => DiaSemana::labelFor($dia),
                $paquete->dias_semana,
            ),
            'hora' => PaqueteSnapshot::horaCorta((string) $paquete->hora),
            'duracion_minutos' => (int) $paquete->duracion_minutos,
            'fecha_inicio' => $paquete->fecha_inicio->toDateString(),
            'fecha_fin' => $paquete->fecha_fin->toDateString(),
            'monto_esperado' => (string) $paquete->monto_esperado,
            'estado' => $estado->value,
            'estado_label' => $estado->label(),
            'paciente' => [
                'id' => $detalle->paciente->id,
                'codigo' => $detalle->paciente->codigo,
                'nombre_completo' => $detalle->paciente->nombreCompleto,
            ],
            'tutor' => [
                'id' => $detalle->tutor->id,
                'nombre_completo' => $detalle->tutor->nombreCompleto,
                'parentesco' => $detalle->tutor->parentesco,
            ],
            'servicio' => $detalle->servicio,
            'categoria' => $detalle->categoria,
            'sucursal' => $detalle->sucursal,
            'profesional' => $detalle->profesional,
        ];
    }

    private function stringValue(ParameterValue $value): string
    {
        $raw = $value->valor;

        if (is_bool($raw)) {
            return $raw ? '1' : '0';
        }

        return $raw === null ? '' : (string) $raw;
    }
}
