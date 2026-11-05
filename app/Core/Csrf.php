<?php

declare(strict_types=1);

namespace App\Core;

/**
 * Protección contra Cross-Site Request Forgery (patrón synchronizer token).
 */
final class Csrf
{
    private const KEY = '_csrf_token';
    public const FIELD = '_token';

    public static function token(): string
    {
        $token = Session::get(self::KEY);
        if (!is_string($token) || strlen($token) !== 64) {
            $token = bin2hex(random_bytes(32));
            Session::put(self::KEY, $token);
        }

        return $token;
    }

    public static function rotate(): void
    {
        Session::put(self::KEY, bin2hex(random_bytes(32)));
    }

    public static function validate(mixed $token): bool
    {
        $expected = Session::get(self::KEY);

        return is_string($token) && is_string($expected) && $expected !== '' && hash_equals($expected, $token);
    }

    public static function field(): string
    {
        return '<input type="hidden" name="' . self::FIELD . '" value="' . self::token() . '">';
    }
}
