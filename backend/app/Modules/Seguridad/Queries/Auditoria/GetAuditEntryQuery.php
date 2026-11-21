<?php

namespace App\Modules\Seguridad\Queries\Auditoria;

use App\Models\User;
use App\Modules\Seguridad\Exceptions\AuditEntryNotFoundException;
use App\Modules\Seguridad\Models\BitacoraEntry;
use App\Modules\Seguridad\Support\AuditScope;

/**
 * Detalle de un evento de bitácora (02/RF-B05, RF-B06).
 */
final class GetAuditEntryQuery
{
    public function __construct(private readonly AuditScope $scope) {}

    public function __invoke(int $auditId, User $actor): BitacoraEntry
    {
        $entry = BitacoraEntry::query()
            ->with([
                'usuario:id,name,email',
                'sucursal:id,codigo,nombre',
            ])
            ->find($auditId);

        if ($entry === null) {
            throw new AuditEntryNotFoundException($auditId);
        }

        $this->scope->assertCanView($actor, $entry);

        return $entry;
    }
}
