<?php

namespace App\Modules\Pagos\Listeners;

use App\Modules\Pagos\Events\PagoRegistrado;
use App\Modules\Pagos\Exceptions\PagoAuditUnavailableException;
use App\Modules\Pagos\Models\BitacoraEntry;
use App\Modules\Pagos\Support\PagosModule;
use Throwable;

/**
 * Bitácora de pagos (RF-015, RNF-012, CA-010).
 *
 * Síncrona y fail-closed: una fila por pago. Si no se puede auditar, la
 * transacción del registro se revierte.
 */
final class RecordPagoAudit
{
    public function handle(PagoRegistrado $event): void
    {
        try {
            foreach ($event->pagos as $pago) {
                BitacoraEntry::query()->create([
                    'usuario_id' => $event->actorId,
                    'sucursal_id' => $pago['sucursal_id'],
                    'accion' => PagosModule::ACTION_CREAR,
                    'entidad' => PagosModule::ENTITY_PAGO,
                    'entidad_id' => $pago['id'],
                    'datos_anteriores' => null,
                    'datos_nuevos' => $pago,
                    'motivo' => $event->fechaFutura ? 'Registro con fecha futura autorizada' : null,
                    'ip' => $event->ip,
                    'modulo' => PagosModule::AUDIT_MODULE,
                    'resultado' => 'exito',
                    'critico' => $event->fechaFutura,
                ]);
            }
        } catch (Throwable $exception) {
            throw new PagoAuditUnavailableException($exception);
        }
    }
}
