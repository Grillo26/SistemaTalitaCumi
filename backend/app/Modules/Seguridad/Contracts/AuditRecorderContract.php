<?php

namespace App\Modules\Seguridad\Contracts;

/**
 * Registro de auditoría para módulos consumidores (02/RF-B07, RF-B08, RF-B15).
 *
 * El contexto de una consulta sensible debe contener solo referencias e
 * identificadores: nunca contenido clínico, contraseñas, hashes ni tokens.
 */
interface AuditRecorderContract
{
    /**
     * @param  array<string, mixed>  $contexto  solo referencias/ids
     */
    public function registrarConsultaSensible(
        string $entidad,
        int $entidadId,
        ?int $sucursalId = null,
        array $contexto = [],
        ?int $usuarioId = null,
        ?string $ip = null,
    ): void;

    /**
     * @param  array<string, mixed>  $filtros
     */
    public function registrarExportacion(
        string $recurso,
        array $filtros,
        int $cantidad,
        ?int $sucursalId = null,
        ?int $usuarioId = null,
        ?string $ip = null,
    ): void;

    /**
     * @param  array<string, mixed>  $filtros
     */
    public function registrarConsultaBitacora(
        array $filtros,
        int $cantidad,
        ?int $usuarioId = null,
        ?string $ip = null,
    ): void;
}
