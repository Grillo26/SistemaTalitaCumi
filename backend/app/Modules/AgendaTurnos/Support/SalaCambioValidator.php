<?php

namespace App\Modules\AgendaTurnos\Support;

use App\Modules\AgendaTurnos\Enums\SeveridadHallazgo;
use App\Modules\AgendaTurnos\Exceptions\ConflictoDisponibilidadException;
use App\Modules\Organizacion\Contracts\SalaDirectoryContract;
use Throwable;

/**
 * Valida la sala elegida al reprogramar.
 */
final class SalaCambioValidator
{
    public function __construct(private readonly SalaDirectoryContract $salas) {}

    public function validar(?int $salaId, int $sucursalId, bool $requiereSala): void
    {
        if ($salaId === null) {
            if ($requiereSala) {
                throw new ConflictoDisponibilidadException([
                    new Hallazgo(
                        tipo: 'sala',
                        severidad: SeveridadHallazgo::BLOQUEO,
                        mensaje: 'El turno de una inscripción a sala requiere una sala.',
                    ),
                ]);
            }

            return;
        }

        try {
            $this->salas->asegurarActivaEnSucursal($salaId, $sucursalId);
        } catch (Throwable) {
            throw new ConflictoDisponibilidadException([
                new Hallazgo(
                    tipo: 'sala',
                    severidad: SeveridadHallazgo::BLOQUEO,
                    mensaje: 'La sala seleccionada no existe o no está activa en la sucursal del turno.',
                ),
            ]);
        }
    }
}
