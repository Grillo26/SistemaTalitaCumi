<?php

namespace App\Modules\Organizacion\Contracts;

/**
 * Detalle tipado de un parámetro para pantallas de configuración acotadas.
 */
final readonly class ParameterAdminValue
{
    public function __construct(
        public int $id,
        public string $clave,
        public mixed $valor,
        public mixed $valorDefecto,
        public string $tipo,
        public ?string $unidad,
        public ?string $minimo,
        public ?string $maximo,
        public int $version,
        public bool $editable,
        public ?string $actualizadoEn,
    ) {}

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'clave' => $this->clave,
            'valor' => $this->valor,
            'valor_defecto' => $this->valorDefecto,
            'tipo' => $this->tipo,
            'unidad' => $this->unidad,
            'minimo' => $this->minimo,
            'maximo' => $this->maximo,
            'version' => $this->version,
            'editable' => $this->editable,
            'actualizado_en' => $this->actualizadoEn,
        ];
    }
}
