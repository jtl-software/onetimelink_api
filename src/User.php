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
     * @var int
     */
    private $maxUploadSize;

    /**
     * @var int
     */
    private $quota;

    /**
     * @param string $email
     * @return User
     */
    public static function createUserFromString(string $email): User
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
    public static function createAnonymousUser(): User
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
    public function setEmail(string $email): void
    {
        $this->email = $email;
    }

    /**
     * @return string|null
     */
    public function getEmail(): ?string
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
    public function setIsActive(bool $isActive = true): void
    {
        $this->isActive = $isActive;
    }

    /**
     * @param int $maxUploadSize
     */
    public function setMaxUploadSize(int $maxUploadSize): void
    {
        $this->maxUploadSize = $maxUploadSize;
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
    public function setIsAdmin(bool $isAdmin = true): void
    {
        $this->isAdmin = $isAdmin;
    }

    /**
     * @param int $quota
     */
    public function setQuota(int $quota): void
    {
        $this->quota = $quota;
    }

    /**
     * @return int
     */
    public function getMaxUploadSize(): int
    {
        return $this->maxUploadSize;
    }

    /**
     * @return int
     */
    public function getQuota(): int
    {
        return $this->quota;
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
        return 'md5/' . md5($this->getEmail());
    }

    /**
     * User constructor.
     * @param string $email
     * @param PasswordHash|null $passwordHash
     * @param bool $isAnonymous
     * @param int $maxUploadSize
     * @param int $quota
     */
    private function __construct(string $email, PasswordHash $passwordHash = null, $isAnonymous = false, int $maxUploadSize = 0, int $quota = 0)
    {
        $this->email = strtolower($email);
        $this->isAnonymous = $isAnonymous;
        $this->passwordHash = $passwordHash;
        if ($this->passwordHash === null) {
            $this->passwordHash = PasswordHash::createFromHash('');
        }
        $this->maxUploadSize = $maxUploadSize;
        $this->quota = $quota;
    }
}
