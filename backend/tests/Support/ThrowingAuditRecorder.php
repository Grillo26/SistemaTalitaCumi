<?php

namespace Tests\Support;

use App\Modules\Seguridad\Contracts\AuditRecorderContract;
use App\Modules\Seguridad\Exceptions\AuditUnavailableException;

/**
 * Recorder que siempre falla, para probar fail-closed.
 */
final class ThrowingAuditRecorder implements AuditRecorderContract
{
    public function registrarConsultaSensible(
        string $entidad,
        int $entidadId,
        ?int $sucursalId = null,
        array $contexto = [],
        ?int $usuarioId = null,
        ?string $ip = null,
    ): void {
        throw new AuditUnavailableException;
    }

    public function registrarExportacion(
        string $recurso,
        array $filtros,
        int $cantidad,
        ?int $sucursalId = null,
        ?int $usuarioId = null,
        ?string $ip = null,
    ): void {
        throw new AuditUnavailableException;
    }

    public function registrarConsultaBitacora(
        array $filtros,
        int $cantidad,
        ?int $usuarioId = null,
        ?string $ip = null,
    ): void {
        throw new AuditUnavailableException;
    }
}
