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
use JTL\Onetimelink\Response;
use JTL\Onetimelink\Storage\UserStorage;
use JTL\Onetimelink\View\ViewInterface;
use Monolog\Logger;

class ActivateUser extends AbstractObservable implements CommandInterface
{
    /**
     * @var ViewInterface
     */
    private $view;

    /**
     * @var UserStorage
     */
    private $userStorage;

    /**
     * @var Config
     */
    private $config;

    /**
     * @var string
     */
    private $activationHash;
    /**
     * @var Logger
     */
    private $logger;

    /**
     * CreateUser constructor.
     *
     * @param string $activationHash
     * @param UserStorage $userStorage
     * @param Config $config
     * @param ViewInterface $view
     * @param Logger $logger
     */
    public function __construct(
        string $activationHash,
        UserStorage $userStorage,
        Config $config,
        ViewInterface $view,
        Logger $logger
    ) {
        $this->activationHash = $activationHash;

        if (empty($this->activationHash)) {
            throw new \InvalidArgumentException('Missing or empty activation hash');
        }

        $this->view = $view;
        $this->userStorage = $userStorage;
        $this->config = $config;
        $this->activationHash = $activationHash;
        $this->logger = $logger;

        parent::__construct($this->config->createNotifier());
    }

    /**
     * @return Response
     * @throws \Exception
     */
    public function execute(): Response
    {
        $userList = $this->userStorage->read();
        $email = null;

        foreach ($userList['user'] as $name => $user) {
            if ($user['activation'] === $this->activationHash) {
                $this->logger->info("Hash {$this->activationHash} found => activate user {$name}");
                $userList['user'][$name]['active'] = true;
                $userList['user'][$name]['activation'] = null;
                $email = $name;
                break;
            }
        }

        if ($email !== null && $this->userStorage->write($userList) === true) {
            $this->notify($this->config->createMessageActivateUser($email));
            return Response::createSuccessful($this->view);
        }

        $this->logger->error("Fail to write user date when activate user");
        throw new \RuntimeException('Fail to write user data');
    }
}
