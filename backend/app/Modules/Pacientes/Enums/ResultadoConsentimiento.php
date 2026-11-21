<?php

namespace App\Modules\Pacientes\Enums;

/**
 * Resultado de un evento de consentimiento (04.03 Fase B).
 *
 * `otorgado` y `negado` son registros iniciales. `revocado` siempre es un
 * evento nuevo que referencia al consentimiento otorgado original.
 */
enum ResultadoConsentimiento: string
{
    case OTORGADO = 'otorgado';

    case NEGADO = 'negado';

    case REVOCADO = 'revocado';

    public function label(): string
    {
        return match ($this) {
            self::OTORGADO => 'Otorgado',
            self::NEGADO => 'Negado',
            self::REVOCADO => 'Revocado',
        };
    }

    public function esOtorgado(): bool
    {
        return $this === self::OTORGADO;
    }

    public function esRevocado(): bool
    {
        return $this === self::REVOCADO;
    }
}
