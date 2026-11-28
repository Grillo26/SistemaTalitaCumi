<?php

namespace Tests\Support;

use App\Modules\Pagos\Contracts\PayableEntityContract;
use App\Modules\Pagos\Contracts\PayableEntityResumen;
use App\Modules\Pagos\Enums\TipoEntidadPago;
use App\Modules\Pagos\Exceptions\PagoEntidadNoEncontradaException;
use App\Modules\Pagos\Exceptions\PagoModuloNoDisponibleException;
use Illuminate\Support\Facades\DB;

/**
 * Doble de prueba de entidades pagables (path B de 08.01). Lee filas reales de
 * `paquete_sesiones` para que las FKs y los montos sean verdaderos; no inventa datos.
 */
final class FakePayableEntityContract implements PayableEntityContract
{
    public function disponible(): bool
    {
        return true;
    }

    public function resumen(TipoEntidadPago $tipo, int $entidadId): PayableEntityResumen
    {
        if ($tipo !== TipoEntidadPago::PAQUETE_SESIONES) {
            throw new PagoModuloNoDisponibleException($tipo->label());
        }

        $fila = DB::table('paquete_sesiones')->where('id', $entidadId)->first();

        if ($fila === null) {
            throw new PagoEntidadNoEncontradaException;
        }

        return $this->desde($fila);
    }

    public function buscar(string $termino, array $sucursalesAccesibles): array
    {
        return DB::table('paquete_sesiones')
            ->whereIn('sucursal_id', $sucursalesAccesibles)
            ->get()
            ->map(fn (object $fila): PayableEntityResumen => $this->desde($fila))
            ->filter(fn (PayableEntityResumen $entidad): bool => stripos($entidad->pacienteNombre, $termino) !== false)
            ->values()
            ->all();
    }

    private function desde(object $fila): PayableEntityResumen
    {
        $sucursal = DB::table('sucursal')->where('id', $fila->sucursal_id)->first();
        $paciente = DB::table('paciente')->where('id', $fila->paciente_id)->first();
        $tutor = DB::table('paciente_tutor')
            ->join('tutor', 'tutor.id', '=', 'paciente_tutor.tutor_id')
            ->where('paciente_tutor.paciente_id', $fila->paciente_id)
            ->where('paciente_tutor.es_principal', true)
            ->first(['tutor.id', 'tutor.nombre_completo']);

        return new PayableEntityResumen(
            tipo: TipoEntidadPago::PAQUETE_SESIONES,
            id: (int) $fila->id,
            sucursalId: (int) $fila->sucursal_id,
            sucursalCodigo: (string) $sucursal->codigo,
            sucursalNombre: (string) $sucursal->nombre,
            pacienteId: (int) $fila->paciente_id,
            pacienteNombre: trim($paciente->nombre.' '.$paciente->apellido_paterno),
            referencia: 'Paquete de sesiones #'.$fila->id,
            estado: (string) $fila->estado,
            montoEsperado: bcadd((string) $fila->monto_esperado, '0', 2),
            tutorSugeridoId: $tutor?->id !== null ? (int) $tutor->id : null,
            tutorSugeridoNombre: $tutor?->nombre_completo,
            periodo: $fila->fecha_inicio.' a '.$fila->fecha_fin,
        );
    }
}
