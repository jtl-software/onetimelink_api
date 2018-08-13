<?php
/**
 * This File is part of JTL-Software
 *
 * User: rherrgesell
 * Date: 11.08.17
 */

namespace JTL\Onetimelink;


class PasswordHash
{

    /**
     * @var string
     */
    private $hash;

    /**
     * @param string $hash
     * @return PasswordHash
     */
    public static function createFromHash(string $hash)
    {
        return new PasswordHash($hash);
    }

    /**
     * PasswordHash constructor.
     * @param string $hash
     */
    private function __construct(string $hash)
    {
        $this->hash = $hash;
    }

    /**
     * @param string $username
     * @param string $rawPassword
     *
     * @return bool
     */
    public function verify(string $username, string $rawPassword)
    {
        $_ = $this->createRawPasswordHash($username, $rawPassword);
        return password_verify($_, $this->hash);
    }

    /**
     * @param string $username
     * @param string $rawPassword
     * @return bool|string
     */
    public static function createHash(string $username, string $rawPassword)
    {
        $_ = self::createRawPasswordHash($username, $rawPassword);
        return password_hash($_, PASSWORD_BCRYPT, ["cost" => 12]);
    }

    /**
     * @param string $username
     * @param string $rawPassword
     * @return string
     */
    private static function createRawPasswordHash(string $username, string $rawPassword): string
    {
        return sha1($username . $rawPassword);
    }
}