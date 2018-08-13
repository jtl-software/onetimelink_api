<?php
/**
 * This File is part of JTL-Software
 *
 * User: mbrandt
 * Date: 06/08/18
 */

namespace JTL\Onetimelink\Monolog;


class IdentifyProcessor
{

    /**
     * @param array $record
     * @return array
     */
    public function __invoke(array $record): array
    {
        $ip = $_SERVER['REMOTE_ADDR'] ?? null;
        $userAgent = $_SERVER['HTTP_USER_AGENT'] ?? null;

        if ($ip !== null) {
            $record['extra']['IP'] = $ip;
        }

        if ($userAgent !== null) {
            $record['extra']['user_agent'] = $userAgent;
        }

        return $record;
    }
}