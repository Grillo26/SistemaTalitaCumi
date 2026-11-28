<?php

namespace App\Modules\AgendaTurnos\Support;

use App\Modules\AgendaTurnos\Enums\EstadoTurno;

/**
 * Metadatos de una acción que 7.3 o 7.4 registran para el detalle del turno.
 *
 * No ejecuta nada: solo describe qué acción existe, qué permiso exige y en
 * qué estados está disponible. La UI de este submódulo no crea acciones.
 */
final readonly class AccionTurnoDefinicion
{
    /**
     * @param  list<EstadoTurno>  $estadosPermitidos
     */
    public function __construct(
        public string $codigo,
        public string $etiqueta,
        public string $permiso,
        public array $estadosPermitidos,
        public string $metodo = 'POST',
        public string $ruta = '',
        public int $orden = 100,
        public bool $repetible = false,
    ) {}

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'codigo' => $this->codigo,
            'etiqueta' => $this->etiqueta,
            'metodo' => $this->metodo,
            'ruta' => $this->ruta,
        ];
    }
}
