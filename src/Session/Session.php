<?php
/**
 * This File is part of JTL-Software
 *
 * User: rherrgesell
 * Date: 14.08.17
 */

namespace JTL\Onetimelink\Session;

use JTL\Onetimelink\User;

/**
 * Class Session
 * @package JTL\Onetimelink\Authentication
 */
class Session
{

    /**
     * @var array
     */
    private $session;
    /**
     * @var PhpSessionHandler
     */
    private $handler;

    /**
     * @param null|string $sessionId
     * @param PhpSessionHandler|null $handler
     * @return Session
     */
    public static function start(string $sessionId = null, PhpSessionHandler $handler = null)
    {
        if (!($handler instanceof PhpSessionHandler)) {
            $handler = new PhpSessionHandler();
        }

        if ($sessionId !== null) {
            $handler->setIdAndStartSession($sessionId);
        } else {
            $handler->sessionStart();
        }

        return new Session($handler->getSessionData(), $handler);
    }

    /**
     * Session constructor.
     * @param array $sessionData
     * @param PhpSessionHandler $handler
     */
    public function __construct(array $sessionData, PhpSessionHandler $handler)
    {
        $this->session = $sessionData;
        $this->handler = $handler;
    }

    public function __destruct()
    {
        $this->persist();
    }

    /**
     * @return string
     */
    public function getSessionId(): string
    {
        return $this->handler->getSessionId();
    }

    /**
     * @param string $key
     * @return mixed|null
     */
    public function get(string $key)
    {
        return $this->session[$key] ?? null;
    }

    /**
     * @param string $key
     * @param $value
     * @return Session
     */
    public function set(string $key, $value): Session
    {
        $this->session[$key] = $value;
        return $this;
    }

    /**
     * @param string $token
     * @param User $user
     * @return Session
     */
    public function setAuthToken(string $token, User $user): Session
    {
        return $this->set("at{$token}", serialize($user));
    }

    /**
     * @param null|string $token
     * @return User|null
     */
    public function getAuthenticatedUser(string $token = null)
    {
        $_ = unserialize($this->get("at{$token}"));
        if ($_ instanceof User) {
            return $_;
        }

        return null;
    }

    /**
     * Store Session data
     */
    public function persist()
    {
        foreach ($this->session as $key => $value) {
            $_SESSION[$key] = $value;
        }
    }
}
