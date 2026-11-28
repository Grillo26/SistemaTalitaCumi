<?php

namespace App\Modules\AgendaTurnos\Queries\Asistencia;

use App\Models\User;
use App\Modules\AgendaTurnos\Support\AsistenciaTurnosModule;
use App\Modules\Seguridad\Contracts\AuditRecorderContract;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * 07.04/RF-010, 07.04/RNF-010.
 *
 * CSV compatible con Excel (BOM UTF-8, punto y coma). Solo agregados: no
 * exporta nombres de menores por paciente; para eso hace falta una decisión
 * funcional expresa. La exportación queda auditada con filtros y cantidad.
 */
final class ExportarAsistenciaCsv
{
    public function __construct(
        private readonly GetIndicadoresAsistencia $indicadores,
        private readonly AuditRecorderContract $auditoria,
    ) {}

    /**
     * @param  array<string, mixed>  $filtros
     */
    public function __invoke(array $filtros, User $actor, ?string $ip = null): StreamedResponse
    {
        if (($filtros['agrupacion'] ?? null) === 'paciente') {
            throw ValidationException::withMessages([
                'agrupacion' => ['La exportación por paciente no está habilitada. Usá sala, servicio, profesional, sucursal o día.'],
            ]);
        }

        $data = $this->indicadores->todas($filtros, $actor);

        $this->auditoria->registrarExportacion(
            recurso: AsistenciaTurnosModule::RECURSO_EXPORTACION,
            filtros: $filtros,
            cantidad: count($data['filas']),
            sucursalId: (int) $filtros['sucursal_id'],
            usuarioId: (int) $actor->id,
            ip: $ip,
        );

        $nombre = 'asistencia-'.$data['periodo']['desde'].'-'.$data['periodo']['hasta'].'.csv';

        return response()->streamDownload(function () use ($data): void {
            $handle = fopen('php://output', 'w');

            if ($handle === false) {
                return;
            }

            fwrite($handle, "\xEF\xBB\xBF");
            fputcsv($handle, [
                'Sucursal',
                'Agrupación',
                'Clave',
                'Etiqueta',
                'Período desde',
                'Período hasta',
                'Total',
                'Asistidos',
                'Faltos',
                'Cancelados',
                'Reprogramados',
                'Pendientes',
                'Porcentaje',
            ], ';', '"', '');

            foreach ($data['filas'] as $fila) {
                fputcsv($handle, [
                    $data['sucursal']['nombre'],
                    $data['agrupacion'],
                    $fila['clave'],
                    $fila['etiqueta'],
                    $data['periodo']['desde'],
                    $data['periodo']['hasta'],
                    $fila['total'],
                    $fila['asistidos'],
                    $fila['faltos'],
                    $fila['cancelados'],
                    $fila['reprogramados'],
                    $fila['pendientes'],
                    number_format($fila['porcentaje'], 2, '.', ''),
                ], ';', '"', '');
            }

            fclose($handle);
        }, $nombre, [
            'Content-Type' => 'text/csv; charset=UTF-8',
        ]);
    }
}
