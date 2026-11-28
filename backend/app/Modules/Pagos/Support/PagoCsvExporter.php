<?php

namespace App\Modules\Pagos\Support;

use App\Modules\Pagos\Models\Pago;
use Illuminate\Database\Eloquent\Builder;

/**
 * Exportación CSV compatible con Excel (08.01/RNF-010). BOM UTF-8, separador
 * `;` y escape de fórmulas. Sin librerías nuevas.
 */
final class PagoCsvExporter
{
    public const DELIMITER = ';';

    public function count(Builder $query): int
    {
        return (clone $query)->count();
    }

    /**
     * @param  resource  $handle
     */
    public function write(Builder $query, mixed $handle): void
    {
        fwrite($handle, "\xEF\xBB\xBF");
        fputcsv($handle, [
            'ID',
            'Número',
            'Fecha de pago',
            'Sucursal',
            'Paciente',
            'Tutor',
            'Entidad',
            'Monto esperado',
            'Monto',
            'Saldo resultante',
            'Medio',
            'Referencia',
            'Estado',
            'Registrado',
        ], self::DELIMITER, '"', '');

        foreach ($query->lazy(200) as $pago) {
            /** @var Pago $pago */
            fputcsv($handle, [
                (int) $pago->id,
                $this->seguro(PagoSnapshot::numeroComprobante($pago)),
                $pago->fecha_pago->toDateString(),
                $this->seguro($pago->sucursal_nombre_snapshot),
                $this->seguro($pago->paciente_nombre_snapshot),
                $this->seguro($pago->tutor_nombre_snapshot),
                $this->seguro($pago->entidad_referencia_snapshot),
                $pago->monto_esperado_snapshot,
                $pago->monto,
                $pago->saldo_resultante,
                $this->seguro($pago->medio_pago),
                $this->seguro($pago->referencia_pago),
                $pago->estado->label(),
                $pago->created_at?->format('Y-m-d H:i:s'),
            ], self::DELIMITER, '"', '');
        }
    }

    /**
     * Evita que Excel interprete texto como fórmula.
     */
    private function seguro(?string $valor): string
    {
        $valor = (string) $valor;

        return preg_match('/^[=+\-@]/', $valor) === 1 ? "'".$valor : $valor;
    }
}
