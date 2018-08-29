<?php
/**
 * This File is part of JTL-Software
 *
 * User: mbrandt
 * Date: 22/05/18
 */

namespace JTL\Onetimelink\Controller\Command;

use JTL\Onetimelink\Controller\AbstractObservable;
use JTL\Onetimelink\Controller\CommandInterface;
use JTL\Onetimelink\Factory;
use JTL\Onetimelink\PasswordHash;
use JTL\Onetimelink\Response;
use JTL\Onetimelink\Storage\UserStorage;
use JTL\Onetimelink\View\ViewInterface;
use Monolog\Logger;

class PasswordResetAction extends AbstractObservable implements CommandInterface
{

    /**
     * @var string
     */
    private $email;

    /**
     * @var string
     */
    private $newPassword;

    /**
     * @var UserStorage
     */
    private $userStorage;

    /**
     * @var ViewInterface
     */
    private $view;
    /**
     * @var Logger
     */
    private $logger;

    /**
     * PasswordResetAction constructor.
     * @param string $email
     * @param string $newPassword
     * @param Factory $factory
     * @param UserStorage $userStorage
     * @param ViewInterface $view
     * @param Logger $logger
     */
    public function __construct(
        string $email,
        string $newPassword,
        Factory $factory,
        UserStorage $userStorage,
        ViewInterface $view,
        Logger $logger
    ) {
        $this->email = $email;
        $this->newPassword = $newPassword;
        $this->userStorage = $userStorage;
        $this->view = $view;
        $this->logger = $logger;

        if (empty($this->newPassword)
            || $this->newPassword === $this->email
            || \strlen($this->newPassword) < 8) {
            throw new \InvalidArgumentException('Invalid password (empty, or length < 8 or equals username)');
        }

        parent::__construct($factory->getConfig()->createNotifier());
    }

    /**
     * @return Response
     * @throws \RuntimeException
     */
    public function execute(): Response
    {
        $obfuscated = "md5/" . md5($this->email);

        $userList = $this->userStorage->read();
        foreach ($userList['user'] as $email => $user) {
            if ($email === $this->email) {
                $newPasswordHash = PasswordHash::createHash($email, $this->newPassword);

                $userList['user'][$email]['password'] = $newPasswordHash;
                $userList['user'][$email]['reset_hash'] = null;
                $userList['user'][$email]['reset_hash_created'] = null;

                if ($this->userStorage->write($userList) === true) {
                    $this->view->set('success', true);

                    $this->logger->info("Password reset for user {$obfuscated} successful");
                    return Response::createSuccessful($this->view);
                }

                break;
            }
        }

        $this->logger->error("Fail to write new password for user {$obfuscated} - maybe user not exists");
        throw new \RuntimeException('Fail to reset password');
    }
}
