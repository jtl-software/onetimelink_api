<?php
/**
 * This File is part of JTL-Software
 *
 * User: rherrgesell
 * Date: 02.08.17
 */

namespace JTL\Onetimelink\Authentication;

use JTL\Onetimelink\Exception\AuthenticationException;
use JTL\Onetimelink\Request;
use JTL\Onetimelink\User;
use JTL\Onetimelink\UserList;

/**
 * Class BasicAuth
 * @package JTL\Onetimelink\Authentication
 */
class BasicAuth implements AuthenticationInterface
{
    const TOKEN_PREFIX = 'at';

    /**
     * @var Request
     */
    private $request;

    /**
     * @var ?string
     */
    private $authToken = null;

    /**
     * BasicAuth constructor.
     * @param Request $request
     */
    public function __construct(Request $request)
    {
        $this->request = $request;
        $this->authToken = $request->readGet('auth') ?? $request->readPost('auth');
    }

    /**
     * @param UserList $userList
     * @return User
     */
    public function authenticate(UserList $userList): User
    {
        $_ = $this->request->getSession()->getAuthenticatedUser($this->authToken);
        if ($_ instanceof User) {
            return $_;
        }

        $authUser = trim((string)$this->request->readServer('PHP_AUTH_USER'));
        $authPassword = (string)$this->request->readServer('PHP_AUTH_PW');

        $user = $userList->getUser($authUser);
        $user->verify($authPassword);
        return $user;
    }

    /**
     * Generate authentication Token and store it to Session Object
     *
     * @param User $user
     *
     * @return string
     *
     * @throws AuthenticationException
     */
    public function generateAuthToken(User $user): string
    {
        if ($user->isAuthenticated()) {
            $token = self::TOKEN_PREFIX . sha1(microtime(true) . uniqid(random_int(1000, 9999)));
            $this->request->getSession()->setAuthToken($token, $user);
            return $token;
        }

        throw new AuthenticationException("User {$user} is not authenticated.");
    }
}
