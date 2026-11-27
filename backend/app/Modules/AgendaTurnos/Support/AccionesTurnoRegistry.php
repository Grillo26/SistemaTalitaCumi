<?php

namespace App\Modules\AgendaTurnos\Support;

/**
 * Registro en memoria de acciones disponibles para el detalle del turno.
 *
 * Los providers de 7.3 y 7.4 llaman a `registrar()` en su boot. La agenda
 * solo muestra las acciones registradas; mientras no existan, el detalle se
 * muestra sin acciones y con un aviso.
 */
final class AccionesTurnoRegistry
{
    /**
     * @var array<string, AccionTurnoDefinicion>
     */
    private array $acciones = [];

    public function registrar(AccionTurnoDefinicion ...$acciones): void
    {
        foreach ($acciones as $accion) {
            $this->acciones[$accion->codigo] = $accion;
        }
    }

    /**
     * @return list<AccionTurnoDefinicion>
     */
    public function todas(): array
    {
        return array_values($this->acciones);
    }

    public function vaciar(): void
    {
        $this->acciones = [];
    }
}
