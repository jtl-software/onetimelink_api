<?php
/**
 * This File is part of JTL-Software
 *
 * User: rherrgesell
 * Date: 02.08.17
 */

namespace JTL\Onetimelink;

use JTL\Onetimelink\Controller\Command;
use JTL\Onetimelink\Controller\ControllerInterface;
use JTL\Onetimelink\Controller\Query;
use JTL\Onetimelink\Monolog\IdentifyProcessor;
use JTL\Onetimelink\Storage\UserMetaDatabaseStorage;
use JTL\Onetimelink\Storage\UserStorage;
use JTL\Onetimelink\View\JsonView;
use Monolog\Formatter\LineFormatter;
use Monolog\Handler\NullHandler;
use Monolog\Handler\StreamHandler;
use Monolog\Logger;
use Monolog\Processor\MemoryUsageProcessor;
use Monolog\Processor\UidProcessor;

class Factory
{

    /**
     * @var Config
     */
    private $config;

    /**
     * @var UserMetaDatabaseStorage
     */
    private $userMetaStorage;

    /**
     * @var Logger
     */
    private $logger;


    /**
     * Factory constructor.
     * @param Config $config
     */
    public function __construct(Config $config)
    {
        $this->config = $config;
    }

    /**
     * @return Config
     */
    public function getConfig(): Config
    {
        return $this->config;
    }

    /**
     * @return ControllerInterface
     */
    public function createController(): ControllerInterface
    {
        $request = $this->config->createRequest();
        if ($request->isGet()) {
            return new Query(
                $request,
                $this->config->createAuthenticationMethod(),
                $this->config->createStorage(),
                $this
            );
        }

        return new Command(
            $request,
            $this->config->createAuthenticationMethod(),
            $this->config->createStorage(),
            $this
        );
    }

    /**
     * @param User $user
     * @return UserMetaDatabaseStorage
     */
    public function createUserMetaStorage(User $user): UserMetaDatabaseStorage
    {
        $_ = $this->userMetaStorage[(string)$user] ?? null;
        if ($_ instanceof UserMetaDatabaseStorage) {
            return $_;
        }

        $this->userMetaStorage[(string)$user] =
            new UserMetaDatabaseStorage($user);

        return $this->userMetaStorage[(string)$user];
    }

    /**
     * @return UserStorage
     */
    public function createUserStorage(): UserStorage
    {
        return (new UserStorage($this->getConfig()->getUserListPath()));
    }

    /**
     * @return UserList
     */
    public function createUserList(): UserList
    {
        $userList = $this->createUserStorage()->read();
        return new UserList($userList['user'], $userList['admin'] ?? []);
    }

    public function createJsonView(): JsonView
    {
        return new JsonView();
    }

    public function createLogger(): Logger
    {
        if ($this->logger instanceof Logger) {
            return $this->logger;
        }

        $this->logger = new Logger('otlapi');
        try {
            $logPath = $this->getConfig()->getLogFilePath();
            $streamHandler = new StreamHandler($logPath, Logger::DEBUG);
            $streamHandler->setFormatter(new LineFormatter($this->getConfig()->getLogFormat(), "Y-m-dTH:i:s:u"));

            $this->logger->pushHandler($streamHandler);
            $this->logger->pushProcessor(new UidProcessor());
            $this->logger->pushProcessor(new MemoryUsageProcessor());
            $this->logger->pushProcessor(new IdentifyProcessor());
        } catch (\Exception $e) {
            $this->logger->pushHandler(new NullHandler());
        }

        return $this->logger;
    }
}
