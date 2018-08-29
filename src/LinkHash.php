<?php
/**
 * This File is part of JTL-Software
 *
 * User: rherrgesell
 * Date: 08.08.17
 */

namespace JTL\Onetimelink;

class LinkHash
{
    public static function create(string $content, int $length = 24): string
    {
        return substr(hash('sha512', $content), 14, $length);
    }

    public static function createUnique(int $length = 24): string
    {
        $unique = (string)microtime(true) . "##" . uniqid('', true);
        return substr(hash('sha512', $unique), 0, $length);
    }
}
