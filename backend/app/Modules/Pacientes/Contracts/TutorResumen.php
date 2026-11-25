<?php

namespace App\Modules\Pacientes\Contracts;

/**
 * Resumen de tutor para el módulo 04.
 *
 * No incluye datos de pacientes ni información clínica. El teléfono se
 * devuelve completo porque el contrato es interno; la capa HTTP lo enmascara
 * según `tutor.ver_contacto`.
 */
final readonly class TutorResumen
{
    public function __construct(
        public int $id,
        public string $nombreCompleto,
        public ?string $ci,
        public string $telefono,
        public ?string $telefonoAlternativo,
        public ?string $email,
        public ?string $direccion,
        public ?int $parentescoId,
        public ?string $parentesco,
        public bool $activo,
        public bool $esPrincipal = false,
        public ?int $sucursalRegistroId = null,
    ) {}

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'nombre_completo' => $this->nombreCompleto,
            'ci' => $this->ci,
            'telefono' => $this->telefono,
            'telefono_alternativo' => $this->telefonoAlternativo,
            'email' => $this->email,
            'direccion' => $this->direccion,
            'parentesco_id' => $this->parentescoId,
            'parentesco' => $this->parentesco,
            'activo' => $this->activo,
            'es_principal' => $this->esPrincipal,
            'sucursal_registro_id' => $this->sucursalRegistroId,
        ];
    }
}
