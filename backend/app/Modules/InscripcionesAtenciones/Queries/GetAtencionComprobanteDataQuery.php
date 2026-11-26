<?php

namespace App\Modules\InscripcionesAtenciones\Queries;

use App\Models\User;
use App\Modules\InscripcionesAtenciones\Enums\TipoAtencion;
use App\Modules\InscripcionesAtenciones\Exceptions\ComprobanteConfiguracionIncompletaException;
use App\Modules\InscripcionesAtenciones\Support\AtencionDetalle;
use App\Modules\Organizacion\Contracts\ParameterReaderContract;
use App\Modules\Organizacion\Exceptions\ParameterNotFoundException;
use App\Modules\Organizacion\Support\ParameterValue;
use Illuminate\Support\Facades\Storage;

/**
 * RF-012 / CA-010: datos reales del comprobante no fiscal de una atención.
 */
final class GetAtencionComprobanteDataQuery
{
    public function __construct(
        private readonly GetAtencionQuery $detalle,
        private readonly ParameterReaderContract $parameters,
    ) {}

    /**
     * @return array<string, mixed>
     */
    public function __invoke(TipoAtencion $tipo, int $id, User $actor): array
    {
        $detalle = ($this->detalle)($tipo, $id, $actor);

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
            'tipo' => $tipo->value,
            'tipo_label' => $tipo->label(),
            'atencion' => $this->detalleToArray($detalle),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function detalleToArray(AtencionDetalle $detalle): array
    {
        $atencion = $detalle->atencion;
        $esEntrevista = $detalle->tipo === TipoAtencion::ENTREVISTA;

        return [
            'id' => (int) $atencion->id,
            'paciente' => [
                'id' => $detalle->paciente->id,
                'codigo' => $detalle->paciente->codigo,
                'nombre_completo' => $detalle->paciente->nombreCompleto,
            ],
            'tutor' => $detalle->tutor === null ? null : [
                'id' => $detalle->tutor->id,
                'nombre_completo' => $detalle->tutor->nombreCompleto,
                'parentesco' => $detalle->tutor->parentesco,
            ],
            'sucursal' => $detalle->sucursal,
            'servicio' => $detalle->servicio,
            'categoria' => $detalle->categoria,
            'tipo_diagnostico' => $detalle->tipoDiagnostico,
            'fecha' => $esEntrevista ? $atencion->fecha->toDateString() : null,
            'fecha_inicio' => $esEntrevista ? null : $atencion->fecha_inicio->toDateString(),
            'fecha_fin' => $esEntrevista ? null : $atencion->fecha_fin->toDateString(),
            'monto_esperado' => (string) $atencion->monto_esperado,
            'estado' => $atencion->estado->value,
            'estado_label' => $atencion->estado->label(),
            'profesionales' => $detalle->profesionales,
            'requisitos' => $detalle->requisitos,
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
