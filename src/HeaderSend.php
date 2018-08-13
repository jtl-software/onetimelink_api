<?php
/**
 * This File is part of JTL-Software
 *
 * User: rherrgesell
 * Date: 04.08.17
 */

namespace JTL\Onetimelink;

/**
 * @codeCoverageIgnore
 */
class HeaderSend
{

    public function send(string $key, string $value)
    {
        header($key . ": " . $value);
    }
}