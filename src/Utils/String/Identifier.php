<?php

namespace App\Utils\String;

class Identifier
{
    private static $identifierCharacters = 'ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz0123456789-_';

    public static function generate(string $chars, int $length = 15): string
    {
        $identifier = '';
        $charsLength = strlen($chars) - 1;

        for ($i = 0; $i < $length; ++$i) {
            $identifier .= $chars[mt_rand(0, $charsLength)];
        }

        return $identifier;
    }

    public static function database(): string
    {
        return self::generate(self::$identifierCharacters);
    }

    public static function filename(int $length = 15): string
    {
        return self::generate(preg_replace('/[^A-Za-z0-9]/', '', self::$identifierCharacters), $length);
    }

    public static function letters(int $length = 8, bool $isCaseSensitive = false): string
    {
        if ($isCaseSensitive) {
            return self::generate(preg_replace('/[^A-Za-z]/', '', self::$identifierCharacters), $length);
        }

        return self::generate(preg_replace('/[^A-Z]/', '', self::$identifierCharacters), $length);
    }

    public static function numbers(int $length = 6): string
    {
        return self::generate(preg_replace('/[^0-9]/', '', self::$identifierCharacters), $length);
    }
}
