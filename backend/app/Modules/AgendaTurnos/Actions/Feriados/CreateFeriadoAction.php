<?php

namespace App\Modules\AgendaTurnos\Actions\Feriados;

use App\Models\User;
use App\Modules\AgendaTurnos\Events\FeriadoCreado;
use App\Modules\AgendaTurnos\Exceptions\FeriadoInvalidoException;
use App\Modules\AgendaTurnos\Models\Feriado;
use App\Modules\Organizacion\Contracts\UserBranchAccessContract;
use App\Modules\Organizacion\Exceptions\BranchAccessDeniedException;

/**
 * [Programación de Turnos] RF-016 / RN-008.
 *
 * Alta de un feriado global (sucursal null) o de una sucursal. Sin precarga.
 */
final class CreateFeriadoAction
{
    public function __construct(private readonly UserBranchAccessContract $branchAccess) {}

    public function __invoke(
        string $fecha,
        string $descripcion,
        ?int $sucursalId,
        User $actor,
        ?string $ip = null,
    ): Feriado {
        $this->asegurarAcceso($actor, $sucursalId);
        $this->asegurarUnico($fecha, $sucursalId);

        $feriado = Feriado::query()->create([
            'fecha' => $fecha,
            'descripcion' => $descripcion,
            'sucursal_id' => $sucursalId,
            'activo' => true,
            'usuario_id' => $actor->id,
        ]);

        event(new FeriadoCreado(
            feriadoId: (int) $feriado->id,
            fecha: $fecha,
            descripcion: $descripcion,
            sucursalId: $sucursalId,
            actorId: (int) $actor->id,
            ip: $ip,
        ));

        return $feriado;
    }

    private function asegurarAcceso(User $actor, ?int $sucursalId): void
    {
        if ($sucursalId !== null && ! $this->branchAccess->puedeAcceder((int) $actor->id, $sucursalId)) {
            throw new BranchAccessDeniedException;
        }
    }

    private function asegurarUnico(string $fecha, ?int $sucursalId): void
    {
        $query = Feriado::query()->whereDate('fecha', $fecha);

        $sucursalId === null
            ? $query->whereNull('sucursal_id')
            : $query->where('sucursal_id', $sucursalId);

        if ($query->exists()) {
            throw new FeriadoInvalidoException(
                'Ya existe un feriado para esa fecha y alcance. Editá el existente en lugar de duplicarlo.',
            );
        }
    }
}
