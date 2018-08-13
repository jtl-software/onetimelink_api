<?php
/**
 * This File is part of JTL-Software
 *
 * User: mbrandt
 * Date: 26.04.18
 */

namespace JTL\Onetimelink\Controller\Command;


use JTL\Onetimelink\Controller\CommandInterface;
use JTL\Onetimelink\PasswordHash;
use JTL\Onetimelink\Response;
use JTL\Onetimelink\Storage\UserStorage;
use JTL\Onetimelink\View\ViewInterface;
use Monolog\Logger;

class UpdateUser implements CommandInterface
{
    /**
     * @var ViewInterface
     */
    private $view;

    /**
     * @var string
     */
    private $username;

    /**
     * @var string
     */
    private $oldPassword;

    /**
     * @var string
     */
    private $newPassword;

    /**
     * @var UserStorage
     */
    private $userStorage;
    /**
     * @var Logger
     */
    private $logger;


    /**
     * CreateUser constructor.
     *
     * @param string $user
     * @param string $oldPassword
     * @param string $newPassword
     * @param UserStorage $userStorage
     * @param ViewInterface $view
     * @param Logger $logger
     */
    public function __construct(
        string $user,
        string $oldPassword,
        string $newPassword,
        UserStorage $userStorage,
        ViewInterface $view,
        Logger $logger
    ) {
        $this->oldPassword = $oldPassword;
        $this->newPassword = $newPassword;
        if (empty($this->newPassword)
            || empty($this->oldPassword)
            || $this->newPassword === $this->username
            || $this->oldPassword === $this->username
            || $this->newPassword === $this->oldPassword
            || \strlen($this->newPassword) < 8
            || \strlen($this->oldPassword) < 8) {
            throw new \InvalidArgumentException('Invalid password (empty, or length < 8 or equals username)');
        }

        $this->username = $user;
        $this->view = $view;
        $this->userStorage = $userStorage;
        $this->logger = $logger;
    }

    /**
     * @return Response
     * @throws \Exception
     */
    public function execute(): Response
    {
        $userList = $this->userStorage->read();
        $obfuscatedUser = "md5/" . md5($this->username);

        $oldPasswordHash = PasswordHash::createFromHash($userList['user'][$this->username]['password']);
        if (!$oldPasswordHash->verify($this->username, $this->oldPassword)) {
            $this->logger->info("Update fail for User {$obfuscatedUser}: old password did not match");
            throw new \RuntimeException('Current password does not match');
        }

        $newPasswordHash = PasswordHash::createHash($this->username, $this->newPassword);
        $userList['user'][$this->username]['password'] = $newPasswordHash;

        if($this->userStorage->write($userList) === true){
            $this->logger->info("Password update for User {$obfuscatedUser} successful");
            return Response::createSuccessful($this->view);
        }

        $this->logger->error("Fail to update password for User {$obfuscatedUser} - could not write user data");
        throw new \RuntimeException('Fail to write user data');
    }
}