<?php
/**
 * This File is part of JTL-Software
 *
 * User: rherrgesell
 * Date: 04.09.17
 */

namespace JTL\Onetimelink\Controller\Command;

use JTL\Onetimelink\Config;
use JTL\Onetimelink\Controller\AbstractObservable;
use JTL\Onetimelink\Controller\CommandInterface;
use JTL\Onetimelink\PasswordHash;
use JTL\Onetimelink\Response;
use JTL\Onetimelink\Storage\UserStorage;
use JTL\Onetimelink\View\ViewInterface;
use Monolog\Logger;

class CreateUser extends AbstractObservable implements CommandInterface
{
    /**
     * @var ViewInterface
     */
    private $view;

    /**
     * @var string
     */
    private $password;

    /**
     * @var string
     */
    private $email;

    /**
     * @var string
     */
    private $mailWhitelistPattern;

    /**
     * @var UserStorage
     */
    private $userStorage;

    /**
     * @var Config
     */
    private $config;
    /**
     * @var Logger
     */
    private $logger;

    /**
     * CreateUser constructor.
     *
     * @param string $email
     * @param string $password
     * @param UserStorage $userStorage
     * @param string $mailWhitelistPattern
     * @param Config $config
     * @param ViewInterface $view
     * @param Logger $logger
     */
    public function __construct(
        string $email,
        string $password,
        UserStorage $userStorage,
        string $mailWhitelistPattern,
        Config $config,
        ViewInterface $view,
        Logger $logger
    ) {
        $this->password = $password;
        if (empty($this->password) || \strlen($this->password) < 8) {
            throw new \InvalidArgumentException('Invalid password (empty, or length < 8 or equals username)');
        }

        $this->email = $email;
        if (!filter_var($this->email, FILTER_VALIDATE_EMAIL)) {
            throw new \InvalidArgumentException('Missing or empty Email');
        }

        $this->view = $view;
        $this->mailWhitelistPattern = $mailWhitelistPattern;
        $this->userStorage = $userStorage;
        $this->config = $config;
        $this->logger = $logger;

        parent::__construct($this->config->createNotifier());
    }

    /**
     * @return Response
     * @throws \Exception
     */
    public function execute(): Response
    {
        $obfuscatedMail = "md5/" . md5($this->email);
        if (!$this->isMailWhitelisted()) {
            $this->logger->notice("Mail {$obfuscatedMail} do no match whitelist pattern {$this->mailWhitelistPattern}");
            return Response::createForbidden();
        }

        $userList = $this->userStorage->read();
        if (isset($userList['user'][$this->email])) {
            $this->logger->notice("Account {$obfuscatedMail} already exists");
            $this->view->set('error', 1);
            $this->view->set('message', 'already exists');
            return new Response($this->view, 400);
        }

        /** old behaviour need to be removed **/
        foreach ($userList['user'] as $name => $user) {
            if (isset($userList['user'][$name]['email']) && $userList['user'][$name]['email'] === $this->email) {
                $this->view->set('error', 1);
                return new Response($this->view, 400);
            }
        }

        $activationHash =  sha1($this->config->getUserActivationSecret() . $this->email);
        $userList['user'][$this->email] = [
            'password' => PasswordHash::createHash($this->email, $this->password),
            'active' => false,
            'created_at' => (new \DateTimeImmutable())->format('c'),
            'activation' => $activationHash
        ];

        if ($this->userStorage->write($userList) === true) {
            $this->notify($this->config->createMessageNewUser($this->email, $activationHash));
            $this->logger->info("Account {$obfuscatedMail} created; activation email send with hash:{$activationHash}");
            return Response::createSuccessfulCreated($this->view);
        }

        $this->logger->error("Fail to write user data for user {$obfuscatedMail}");
        throw new \RuntimeException('Fail to write user data');
    }

    private function isMailWhitelisted(): bool
    {
        return preg_match($this->mailWhitelistPattern, $this->email);
    }
}
