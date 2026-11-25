<?php

namespace App\Modules\Pacientes\Support;

/**
 * Bloque de la ficha resumen (04.04/RF-005).
 *
 * `estado=no_disponible` nunca representa ceros ni datos inventados: siempre
 * lleva un motivo y un mensaje para la UI.
 */
final readonly class PacienteResumenBlock
{
    public function __construct(
        public string $estado,
        public ?string $motivo,
        public string $mensaje,
        public mixed $data,
    ) {}

    public static function disponible(string $mensaje, mixed $data = null): self
    {
        return new self('disponible', null, $mensaje, $data);
    }

    public static function noDisponible(string $motivo, string $mensaje): self
    {
        return new self('no_disponible', $motivo, $mensaje, null);
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'estado' => $this->estado,
            'motivo' => $this->motivo,
            'mensaje' => $this->mensaje,
            'data' => $this->data,
        ];
    }
}
