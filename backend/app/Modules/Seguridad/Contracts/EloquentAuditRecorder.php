<?php

namespace App\Modules\Seguridad\Contracts;

use App\Modules\Seguridad\Enums\AuditAction;
use App\Modules\Seguridad\Enums\AuditModule;
use App\Modules\Seguridad\Enums\AuditResult;
use App\Modules\Seguridad\Exceptions\AuditUnavailableException;
use App\Modules\Seguridad\Models\BitacoraEntry;
use App\Modules\Seguridad\Support\AuditModuleResolver;
use Throwable;

/**
 * Implementación síncrona y fail-closed del registro de auditoría
 * (02/RF-B07, RF-B08, RF-B15).
 */
final class EloquentAuditRecorder implements AuditRecorderContract
{
    public function registrarConsultaSensible(
        string $entidad,
        int $entidadId,
        ?int $sucursalId = null,
        array $contexto = [],
        ?int $usuarioId = null,
        ?string $ip = null,
    ): void {
        $this->write([
            'usuario_id' => $usuarioId,
            'sucursal_id' => $sucursalId,
            'accion' => AuditAction::QUERY,
            'entidad' => $entidad,
            'entidad_id' => $entidadId,
            'datos_anteriores' => null,
            'datos_nuevos' => ['contexto' => $this->sanitizeContext($contexto)],
            'motivo' => null,
            'ip' => $ip,
            'modulo' => AuditModuleResolver::fromEntity($entidad),
            'resultado' => AuditResult::EXITO,
            'critico' => false,
        ]);
    }

    public function registrarExportacion(
        string $recurso,
        array $filtros,
        int $cantidad,
        ?int $sucursalId = null,
        ?int $usuarioId = null,
        ?string $ip = null,
    ): void {
        $this->write([
            'usuario_id' => $usuarioId,
            'sucursal_id' => $sucursalId,
            'accion' => AuditAction::EXPORT,
            'entidad' => 'exportacion',
            'entidad_id' => null,
            'datos_anteriores' => null,
            'datos_nuevos' => [
                'recurso' => $recurso,
                'filtros' => $filtros,
                'cantidad' => $cantidad,
            ],
            'motivo' => null,
            'ip' => $ip,
            'modulo' => AuditModule::AUDITORIA->value,
            'resultado' => AuditResult::EXITO,
            'critico' => true,
        ]);
    }

    public function registrarConsultaBitacora(
        array $filtros,
        int $cantidad,
        ?int $usuarioId = null,
        ?string $ip = null,
    ): void {
        $this->write([
            'usuario_id' => $usuarioId,
            'sucursal_id' => null,
            'accion' => AuditAction::QUERY,
            'entidad' => 'bitacora',
            'entidad_id' => null,
            'datos_anteriores' => null,
            'datos_nuevos' => [
                'filtros' => $filtros,
                'cantidad' => $cantidad,
            ],
            'motivo' => null,
            'ip' => $ip,
            'modulo' => AuditModule::AUDITORIA->value,
            'resultado' => AuditResult::EXITO,
            'critico' => false,
        ]);
    }

    /**
     * @param  array<string, mixed>  $contexto
     * @return array<string, mixed>
     */
    private function sanitizeContext(array $contexto): array
    {
        $allowed = [
            'entidad',
            'entidad_id',
            'recurso',
            'campo',
            'filtros',
            'cantidad',
        ];

        return array_intersect_key($contexto, array_flip($allowed));
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    private function write(array $payload): void
    {
        try {
            BitacoraEntry::query()->create($payload);
        } catch (Throwable $exception) {
            throw new AuditUnavailableException($exception);
        }
    }
}
