<?php
/**
 * This File is part of JTL-Software
 *
 * User: rherrgesell
 * Date: 02.08.17
 */

namespace JTL\Onetimelink;

class User
{

    const USER_ANONYMOUS = 'anonym@jtl-software.com';

    /**
     * @var bool
     */
    private $isAnonymous;

    /**
     * @var bool
     */
    private $isAuthenticated = false;

    /**
     * @var PasswordHash
     */
    private $passwordHash;

    /**
     * @var string
     */
    private $email;

    /**
     * @var bool
     */
    private $isAdmin = false;

    /**
     * @var bool
     */
    private $isActive = true;

    /**
     * @param string $email
     * @return User
     */
    public static function createUserFromString(string $email)
    {
        return new User($email);
    }

    /**
     * @param string $email
     * @param PasswordHash $passwordHash
     * @return User
     */
    public static function createFromCredentials(string $email, PasswordHash $passwordHash): User
    {
        return new User($email, $passwordHash);
    }

    /**
     * @return User
     */
    public static function createAnonymousUser()
    {
        return new User(self::USER_ANONYMOUS, null, true);
    }

    /**
     * @return bool
     */
    public function isAnonymous(): bool
    {
        return $this->isAnonymous;
    }

    /**
     * @return bool
     */
    public function isAuthenticated(): bool
    {
        return $this->isAuthenticated;
    }

    /**
     * @param User $user
     * @return string
     */
    public function equals(User $user)
    {
        return $this->email === (string)$user;
    }

    /**
     * @param string $rawPassword
     * @return bool
     */
    public function verify(string $rawPassword): bool
    {
        $this->isAuthenticated = false;
        if ($this->passwordHash->verify($this->email, $rawPassword)) {
            $this->isAuthenticated = true;
        }
        return $this->isAuthenticated();
    }

    /**
     * @param string $email
     */
    public function setEmail(string $email)
    {
        $this->email = $email;
    }

    /**
     * @return string|null
     */
    public function getEmail()
    {
        return $this->email;
    }

    /**
     * @return bool
     */
    public function isActive(): bool
    {
        return $this->isActive;
    }

    /**
     * @param bool $isActive
     */
    public function setIsActive(bool $isActive = true)
    {
        $this->isActive = $isActive;
    }

    /**
     * @return bool
     */
    public function isAdmin(): bool
    {
        return $this->isAdmin;
    }

    /**
     * @param bool $isAdmin
     */
    public function setIsAdmin(bool $isAdmin = true)
    {
        $this->isAdmin = $isAdmin;
    }

    /**
     * @return string
     */
    public function __toString(): string
    {
        return $this->getEmail();
    }

    /**
     * @return string
     */
    public function obfuscatedUsername() : string
    {
        return "md5/" . md5($this->getEmail());
    }

    /**
     * User constructor.
     *
     * @param string $email
     * @param PasswordHash|null $passwordHash
     * @param bool $isAnonymous
     */
    private function __construct(string $email, PasswordHash $passwordHash = null, $isAnonymous = false)
    {
        $this->email = $email;
        $this->isAnonymous = $isAnonymous;
        $this->passwordHash = $passwordHash;
        if ($this->passwordHash === null) {
            $this->passwordHash = PasswordHash::createFromHash('');
        }
    }
}