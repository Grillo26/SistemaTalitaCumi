<?php

namespace App\Modules\AgendaTurnos\Support;

/**
 * Semana normalizada de lunes a domingo.
 *
 * La vista muestra solo los días habilitados por configuración; el rango
 * completo se conserva para detectar y avisar turnos en días ocultos.
 */
final readonly class SemanaAgenda
{
    /**
     * @param  list<DiaAgenda>  $dias
     */
    public function __construct(
        public string $inicio,
        public string $fin,
        public array $dias,
    ) {}

    /**
     * @return list<DiaAgenda>
     */
    public function diasVisibles(): array
    {
        return array_values(array_filter(
            $this->dias,
            static fn (DiaAgenda $dia): bool => $dia->visible,
        ));
    }

    public function inicioVisible(): string
    {
        $visibles = $this->diasVisibles();

        return $visibles === [] ? $this->inicio : $visibles[0]->fecha;
    }

    public function finVisible(): string
    {
        $visibles = $this->diasVisibles();

        return $visibles === [] ? $this->fin : $visibles[count($visibles) - 1]->fecha;
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'inicio' => $this->inicio,
            'fin' => $this->fin,
            'inicio_visible' => $this->inicioVisible(),
            'fin_visible' => $this->finVisible(),
            'dias' => array_map(
                static fn (DiaAgenda $dia): array => $dia->toArray(),
                $this->dias,
            ),
        ];
    }
}
