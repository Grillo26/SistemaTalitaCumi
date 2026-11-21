<?php

namespace App\Modules\Seguridad\Support;

/**
 * Genera contraseñas temporales que cumplen la política vigente.
 *
 * Incluye mayúscula, minúscula, número y símbolo, y usa random_int
 * (criptográficamente seguro). La longitud mínima es 16 salvo que la
 * política exija más.
 */
final class TemporaryPasswordGenerator
{
    private const UPPERCASE = 'ABCDEFGHJKLMNPQRSTUVWXYZ';

    private const LOWERCASE = 'abcdefghijkmnopqrstuvwxyz';

    private const NUMBERS = '23456789';

    private const SYMBOLS = '!@#$%&*?';

    private const MIN_LENGTH = 16;

    public function generate(?int $length = null): string
    {
        $length = max(self::MIN_LENGTH, $length ?? self::MIN_LENGTH);

        $chars = [
            self::randomChar(self::UPPERCASE),
            self::randomChar(self::LOWERCASE),
            self::randomChar(self::NUMBERS),
            self::randomChar(self::SYMBOLS),
        ];

        $pool = self::UPPERCASE.self::LOWERCASE.self::NUMBERS.self::SYMBOLS;

        while (count($chars) < $length) {
            $chars[] = self::randomChar($pool);
        }

        for ($i = count($chars) - 1; $i > 0; $i--) {
            $j = random_int(0, $i);
            [$chars[$i], $chars[$j]] = [$chars[$j], $chars[$i]];
        }

        return implode('', $chars);
    }

    private static function randomChar(string $pool): string
    {
        return $pool[random_int(0, strlen($pool) - 1)];
    }
}
