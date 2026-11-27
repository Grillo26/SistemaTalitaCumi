<?php

namespace App\Modules\InscripcionesAtenciones\Queries;

use App\Models\User;
use App\Modules\InscripcionesAtenciones\Exceptions\ComprobanteConfiguracionIncompletaException;
use App\Modules\InscripcionesAtenciones\Support\InscripcionDetalle;
use App\Modules\Organizacion\Contracts\ParameterReaderContract;
use App\Modules\Organizacion\Exceptions\ParameterNotFoundException;
use App\Modules\Organizacion\Support\ParameterValue;
use Illuminate\Support\Facades\Storage;

/**
 * RF-012: datos reales del comprobante de inscripción no fiscal.
 *
 * Los datos del centro salen de configuración; nunca se hardcodean.
 */
final class GetComprobanteDataQuery
{
    public function __construct(
        private readonly GetInscripcionQuery $detalle,
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
            'inscripcion' => $this->detalleToArray($detalle),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function detalleToArray(InscripcionDetalle $detalle): array
    {
        $inscripcion = $detalle->inscripcion;
        $estadoMostrado = $inscripcion->estado->mostrado(
            $inscripcion->fecha_fin->toDateString(),
            now()->toDateString(),
        );

        return [
            'id' => (int) $inscripcion->id,
            'fecha_inicio' => $inscripcion->fecha_inicio->toDateString(),
            'fecha_fin' => $inscripcion->fecha_fin->toDateString(),
            'monto_esperado' => (string) $inscripcion->monto_esperado,
            'estado' => $estadoMostrado->value,
            'estado_label' => $estadoMostrado->label(),
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
            'sala' => $detalle->sala,
            'servicio' => $detalle->servicio,
            'categoria' => $detalle->categoria,
            'sucursal' => $detalle->sucursal,
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
