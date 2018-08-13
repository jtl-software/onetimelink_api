<?php
/**
 * This File is part of JTL-Software
 *
 * User: rherrgesell
 * Date: 14.08.17
 */

namespace JTL\Onetimelink\Session;


class PhpSessionHandler
{

    public function setIdAndStartSession(string $sessionId)
    {
        session_id($sessionId);
        self::sessionStart();
    }

    public function sessionStart()
    {
        session_start();
    }

    public function getSessionId()
    {
        return session_id();
    }

    public function getSessionData(): array
    {
        return $_SESSION;
    }
}