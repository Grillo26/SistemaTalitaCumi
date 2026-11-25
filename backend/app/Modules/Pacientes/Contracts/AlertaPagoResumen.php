<?php

namespace App\Modules\Pacientes\Contracts;

/**
 * Alerta de próximo pago generada por el módulo 09 (04.04/RF-009).
 *
 * El enlace de WhatsApp se genera en backend; el frontend solo lo abre.
 * No hay envío automático (04.04/RN-007).
 */
final readonly class AlertaPagoResumen
{
    public function __construct(
        public int $tutorId,
        public string $tutorNombre,
        public string $telefono,
        public string $whatsappUrl,
        public string $mensaje,
        public ?string $fechaVencimiento = null,
    ) {}

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'tutor_id' => $this->tutorId,
            'tutor_nombre' => $this->tutorNombre,
            'telefono' => $this->telefono,
            'whatsapp_url' => $this->whatsappUrl,
            'mensaje' => $this->mensaje,
            'fecha_vencimiento' => $this->fechaVencimiento,
        ];
    }
}
