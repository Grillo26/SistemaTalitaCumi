<?php

namespace App\Modules\Pacientes\Contracts;

/**
 * Contacto del tutor principal para la alerta de próximo pago.
 *
 * Pertenece a 04.02 Fase C. Mientras no exista, el stub falla de forma
 * explícita y la ficha resumen muestra la alerta como no disponible.
 */
interface TutorContactContract
{
    public function disponible(): bool;

    public function contactoPrincipal(int $pacienteId): ?ContactoTutorResumen;
}
