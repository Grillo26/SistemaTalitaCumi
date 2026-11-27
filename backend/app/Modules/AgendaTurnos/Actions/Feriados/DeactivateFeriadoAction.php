<?php

namespace App\Modules\AgendaTurnos\Actions\Feriados;

use App\Models\User;
use App\Modules\AgendaTurnos\Events\FeriadoDesactivado;
use App\Modules\AgendaTurnos\Exceptions\FeriadoNoEncontradoException;
use App\Modules\AgendaTurnos\Models\Feriado;

/**
 * [Programación de Turnos] RF-016.
 *
 * Baja lógica: el feriado deja de excluir fechas, pero se conserva el registro.
 */
final class DeactivateFeriadoAction
{
    public function __invoke(int $feriadoId, User $actor, ?string $ip = null): Feriado
    {
        $feriado = Feriado::query()->find($feriadoId);

        if ($feriado === null) {
            throw new FeriadoNoEncontradoException($feriadoId);
        }

        $before = $feriado->only(['fecha', 'descripcion', 'sucursal_id', 'activo']);

        if ($feriado->activo) {
            $feriado->forceFill(['activo' => false])->save();

            event(new FeriadoDesactivado(
                feriadoId: (int) $feriado->id,
                before: $before,
                actorId: (int) $actor->id,
                ip: $ip,
            ));
        }

        return $feriado->refresh();
    }
}
