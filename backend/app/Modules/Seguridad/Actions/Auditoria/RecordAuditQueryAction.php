<?php

namespace App\Modules\Seguridad\Actions\Auditoria;

use App\Models\User;
use App\Modules\Seguridad\Contracts\AuditRecorderContract;

/**
 * Audita la consulta a la bitácora (02/RF-B15).
 */
final class RecordAuditQueryAction
{
    public function __construct(private readonly AuditRecorderContract $recorder) {}

    /**
     * @param  array<string, mixed>  $filtros
     */
    public function __invoke(array $filtros, int $cantidad, User $actor, ?string $ip): void
    {
        $this->recorder->registrarConsultaBitacora(
            filtros: $filtros,
            cantidad: $cantidad,
            usuarioId: $actor->id,
            ip: $ip,
        );
    }
}
