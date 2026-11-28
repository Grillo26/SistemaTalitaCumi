<?php

namespace App\Modules\AgendaTurnos\Actions\Feriados;

use App\Models\User;
use App\Modules\AgendaTurnos\Events\FeriadoActualizado;
use App\Modules\AgendaTurnos\Exceptions\FeriadoInvalidoException;
use App\Modules\AgendaTurnos\Exceptions\FeriadoNoEncontradoException;
use App\Modules\AgendaTurnos\Models\Feriado;
use App\Modules\Organizacion\Contracts\UserBranchAccessContract;
use App\Modules\Organizacion\Exceptions\BranchAccessDeniedException;

/**
 * [Programación de Turnos] RF-016 / RN-008.
 */
final class UpdateFeriadoAction
{
    public function __construct(private readonly UserBranchAccessContract $branchAccess) {}

    /**
     * @param  array{fecha?: string, descripcion?: string, sucursal_id?: int|null, activo?: bool}  $data
     */
    public function __invoke(int $feriadoId, array $data, User $actor, ?string $ip = null): Feriado
    {
        $feriado = Feriado::query()->find($feriadoId);

        if ($feriado === null) {
            throw new FeriadoNoEncontradoException($feriadoId);
        }

        $before = $feriado->only(['fecha', 'descripcion', 'sucursal_id', 'activo']);
        $fecha = $data['fecha'] ?? $feriado->fecha->toDateString();
        $sucursalId = array_key_exists('sucursal_id', $data)
            ? ($data['sucursal_id'] === null ? null : (int) $data['sucursal_id'])
            : $feriado->sucursal_id;

        if ($sucursalId !== null && ! $this->branchAccess->puedeAcceder((int) $actor->id, $sucursalId)) {
            throw new BranchAccessDeniedException;
        }

        if (
            $fecha !== $feriado->fecha->toDateString()
            || $sucursalId !== $feriado->sucursal_id
        ) {
            $this->asegurarUnico($fecha, $sucursalId, $feriadoId);
        }

        $feriado->fill([
            'fecha' => $fecha,
            'sucursal_id' => $sucursalId,
            'descripcion' => $data['descripcion'] ?? $feriado->descripcion,
            'activo' => $data['activo'] ?? $feriado->activo,
        ])->save();

        event(new FeriadoActualizado(
            feriadoId: (int) $feriado->id,
            before: $before,
            after: $feriado->only(['fecha', 'descripcion', 'sucursal_id', 'activo']),
            actorId: (int) $actor->id,
            ip: $ip,
        ));

        return $feriado->refresh();
    }

    private function asegurarUnico(string $fecha, ?int $sucursalId, int $excluirId): void
    {
        $query = Feriado::query()
            ->whereDate('fecha', $fecha)
            ->whereKeyNot($excluirId);

        $sucursalId === null
            ? $query->whereNull('sucursal_id')
            : $query->where('sucursal_id', $sucursalId);

        if ($query->exists()) {
            throw new FeriadoInvalidoException(
                'Ya existe un feriado para esa fecha y alcance.',
            );
        }
    }
}
