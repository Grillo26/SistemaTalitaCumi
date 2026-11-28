<?php

namespace App\Modules\AgendaTurnos\Exceptions;

final class CancelacionMasivaFueraDeSucursalException extends AgendaTurnosDomainException
{
    /**
     * @param  list<int>  $sucursalesFaltantes
     */
    public function __construct(public readonly array $sucursalesFaltantes)
    {
        $lista = implode(', ', array_map(
            static fn (int $id): string => "#{$id}",
            $sucursalesFaltantes,
        ));

        parent::__construct(
            "No tenés acceso a todas las sucursales de los turnos seleccionados: {$lista}.",
        );
    }

    public function status(): int
    {
        return 403;
    }

    public function errorCode(): string
    {
        return 'CANCELACION_MASIVA_FUERA_DE_SUCURSAL';
    }

    public function context(): array
    {
        return ['sucursales_faltantes' => $this->sucursalesFaltantes];
    }
}
