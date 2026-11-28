<?php

namespace App\Modules\Pagos\Http\Controllers;

use App\Models\User;
use App\Modules\Pagos\Exceptions\PagoExportacionMuyGrandeException;
use App\Modules\Pagos\Http\Requests\IndexPagoRequest;
use App\Modules\Pagos\Queries\ListPagosQuery;
use App\Modules\Pagos\Support\PagoCsvExporter;
use App\Modules\Seguridad\Contracts\AuditRecorderContract;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * [08.01] Exportación CSV del listado (RNF-010). Permiso `pago.exportar`, límite
 * configurable y auditoría antes de transmitir.
 */
final class PagoExportController
{
    public function export(
        IndexPagoRequest $request,
        ListPagosQuery $query,
        PagoCsvExporter $exporter,
        AuditRecorderContract $audit,
    ): StreamedResponse {
        /** @var User $actor */
        $actor = $request->user();
        $data = $request->validated();

        $builder = $query->builder(
            $data,
            $actor,
            (string) ($data['sort'] ?? 'fecha_pago'),
            (string) ($data['direction'] ?? 'desc'),
        );
        $cantidad = $exporter->count($builder);
        $maximo = (int) config('pagos.export_max_rows', 10000);

        if ($cantidad > $maximo) {
            throw new PagoExportacionMuyGrandeException;
        }

        $audit->registrarExportacion(
            recurso: 'pagos',
            filtros: [
                'sucursal_id' => $data['sucursal_id'] ?? null,
                'periodo_desde' => $data['periodo_desde'] ?? null,
                'periodo_hasta' => $data['periodo_hasta'] ?? null,
                'paciente_id' => $data['paciente_id'] ?? null,
                'medio_pago' => $data['medio_pago'] ?? null,
                'estado' => $data['estado'] ?? null,
                'formato' => 'csv',
            ],
            cantidad: $cantidad,
            sucursalId: isset($data['sucursal_id']) ? (int) $data['sucursal_id'] : null,
            usuarioId: $actor->id,
            ip: $request->ip(),
        );

        $filename = 'pagos_'.now()->format('Ymd_His').'.csv';

        return response()->streamDownload(function () use ($builder, $exporter): void {
            $handle = fopen('php://output', 'w');

            if ($handle === false) {
                return;
            }

            $exporter->write($builder, $handle);
            fclose($handle);
        }, $filename, [
            'Content-Type' => 'text/csv; charset=UTF-8',
        ]);
    }
}
