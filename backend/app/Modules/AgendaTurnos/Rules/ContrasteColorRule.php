<?php

namespace App\Modules\AgendaTurnos\Rules;

/**
 * [Agenda Semanal] RF-003, RN-002, RNF-008.
 *
 * Elige texto blanco o negro según el contraste del color de área. El color
 * del área viene del catálogo; esta regla no fija colores de área.
 */
final class ContrasteColorRule
{
    private const TEXTO_CLARO = '#FFFFFF';

    private const TEXTO_OSCURO = '#101828';

    public function __invoke(?string $color): ?string
    {
        $color = $this->normalizar($color);

        if ($color === null) {
            return null;
        }

        $luminancia = $this->luminancia($color);

        // Contraste contra blanco vs. contra negro (WCAG).
        return $luminancia > 0.179 ? self::TEXTO_OSCURO : self::TEXTO_CLARO;
    }

    private function normalizar(?string $color): ?string
    {
        if ($color === null) {
            return null;
        }

        $color = mb_strtoupper(trim($color));

        if (preg_match('/^#([A-F0-9]{6}|[A-F0-9]{3})$/', $color) !== 1) {
            return null;
        }

        $hex = substr($color, 1);

        if (strlen($hex) === 3) {
            $hex = implode('', array_map(
                static fn (string $part): string => $part.$part,
                str_split($hex),
            ));
        }

        return '#'.$hex;
    }

    private function luminancia(string $color): float
    {
        $hex = substr($color, 1);

        $canales = [];

        foreach ([0, 2, 4] as $offset) {
            $valor = hexdec(substr($hex, $offset, 2)) / 255;
            $canales[] = $valor <= 0.03928
                ? $valor / 12.92
                : (($valor + 0.055) / 1.055) ** 2.4;
        }

        return (0.2126 * $canales[0]) + (0.7152 * $canales[1]) + (0.0722 * $canales[2]);
    }
}
