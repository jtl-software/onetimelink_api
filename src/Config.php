<?php
/**
 * This File is part of JTL-Software
 *
 * User: rherrgesell
 * Date: 03.08.17
 */

namespace JTL\Onetimelink;


use JTL\Onetimelink\Authentication\AuthenticationInterface;
use JTL\Onetimelink\Notification\Message\AbstractMessage;
use JTL\Onetimelink\Notification\NotifierInterface;
use JTL\Onetimelink\Storage\DatabaseStorage;
use JTL\Onetimelink\Storage\LocationDirectory;

class Config
{

    /**
     * @var array
     */
    private $config;

    /**
     * @var Request
     */
    private $requestObject;

    /**
     * @var AuthenticationInterface
     */
    private $authObject;

    /**
     * @var DatabaseStorage
     */
    private $storageObject;

    /**
     * @param string $configFilePath
     * @param bool $loadDefault
     * @return Config
     */
    public static function createFromFilePath(string $configFilePath, bool $loadDefault = true)
    {
        if (!file_exists($configFilePath)) {
            throw new \RuntimeException("$configFilePath not exits");
        }

        $config = require $configFilePath;
        if (!is_array($config)) {
            throw new \RuntimeException("Config file is not a valid json file");
        }

        if ($loadDefault && $configFilePath != self::getDefaultConfigFilePath()) {
            $defaultConfig = require self::getDefaultConfigFilePath();
            foreach ($config as $key => $value) {
                $defaultConfig[$key] = $value;
            }
            $config = $defaultConfig;
        }

        return new Config($config);
    }

    /**
     * @return string
     */
    public static function getConfigPathFromEnvironment(): string
    {
        $path = getenv('ENVIRONMENT_CONFIG_PATH');
        if (!$path) {
            $path = self::getDefaultConfigFilePath();
        }
        return $path;
    }

    /**
     * @return string
     */
    private static function getDefaultConfigFilePath(): string
    {
        return __DIR__ . '/../config/config.dist.php';
    }

    /**
     * Config constructor.
     * @param array $config
     */
    private function __construct(array $config)
    {
        $this->config = $config;
    }

    /**
     * @return Request
     */
    public function createRequest(): Request
    {
        if ($this->requestObject instanceof Request) {
            return $this->requestObject;
        }
        $this->requestObject = $this->config['request']();
        return $this->requestObject;
    }

    /**
     * @return AuthenticationInterface
     */
    public function createAuthenticationMethod(): AuthenticationInterface
    {
        if ($this->authObject instanceof AuthenticationInterface) {
            return $this->authObject;
        }
        $this->authObject = $this->config['auth']($this->createRequest());
        return $this->authObject;
    }

    /**
     * @return DatabaseStorage
     */
    public function createStorage(): DatabaseStorage
    {
        if ($this->storageObject instanceof DatabaseStorage) {
            return $this->storageObject;
        }
        $this->storageObject = $this->config['storage']($this->getStoragePath());
        return $this->storageObject;
    }

    /**
     * @return LocationDirectory
     */
    public function getStoragePath(): LocationDirectory
    {
        $path = $this->config['storage-directory'];
        return new LocationDirectory($path);
    }

    /**
     * @return UserList
     */
    public function createUserList(): UserList
    {
        $path = $this->getUserListPath();
        $userList = json_decode(file_get_contents($path), true);

        if (!isset($userList['user'])) {
            throw new \RuntimeException('No user list provided in user file "' . $path . '"');
        }

        return new UserList($userList['user'], $userList['admin'] ?? []);
    }

    /**
     * @return string
     */
    public function getUserListPath(): string
    {
        if (!file_exists($this->config['user'])) {
            throw new \RuntimeException('Can not load User List');
        }
        return $this->config['user'];
    }

    /**
     * @return NotifierInterface
     */
    public function createNotifier(): NotifierInterface
    {
        return $this->config['notifier']($this->config['from']);
    }

    /**
     * @param User $user
     * @param string $oneTimeHash
     * @param array $tags
     *
     * @return AbstractMessage
     */
    public function createMessageForGuestLinkResponse(
        User $user,
        string $oneTimeHash,
        array $tags = []
    ): AbstractMessage {
        $oneTimeLink = $this->getUserInterfaceHost() . '/#/r' . $oneTimeHash;
        return $this->config['message-guest-link-response']($user, $oneTimeLink, $tags);
    }

    /**
     * @param string $email
     * @param string $activationLink
     *
     * @return AbstractMessage
     */
    public function createMessageNewUser(
        string $email,
        string $activationLink
    ): AbstractMessage {

        $activationLink = $this->getUserInterfaceHost() . '/#/user/activate/' . $activationLink;
        return $this->config['activate-user']($email, $activationLink);
    }

    /**
     * @param string $email
     * @return AbstractMessage
     */
    public function createMessageActivateUser(
        string $email
    ): AbstractMessage {

        return $this->config['activated-user']($email);
    }

    /**
     * @param User $user
     * @param array $tags
     *
     * @param string $ip
     * @param string $useragent
     * @return AbstractMessage
     */
    public function createMessageForLinkDeleted(
        User $user,
        array $tags = [],
        string $ip,
        string $useragent
    ): AbstractMessage {
        return $this->config['message-link-deleted']($user, $tags, $ip, $useragent);
    }

    public function createMessageForPasswordReset(
        string $email,
        string $hash
    ): AbstractMessage
    {
        $resetLink = $this->getUserInterfaceHost() . '/#/password/reset/' . $hash;
        return $this->config['message-password-reset']($email, $hash, $resetLink);
    }


    /**
     * @return string
     */
    public function getUserInterfaceHost(): string
    {
        return $this->config['user-interface-host'];
    }

    /**
     * @return string
     */
    public function getMailWhitelistPattern(): string
    {
        return $this->config['allow-user-with-mail'];
    }

    public function getTempDir(): string
    {
        return $this->config['tmp_dir'];
    }

    public function getDatabaseDSN(): string
    {
        return $this->config['db_dsn'];
    }

    public function getConsoleDatabaseDSN(): string
    {
        return $this->config['console_db_dsn'];
    }

    public function getConsoleStoragePath(): string
    {
        return $this->config['console-storage-directory'];
    }

    public function getConsoleTempPath(): string
    {
        return $this->config['console-tmp-dir'];
    }

    public function getTimeZone()
    {
        return $this->config['timezone'] ?? null;
    }

    public function getLogFilePath(): string
    {
        return $this->config['log-file-path'];
    }

    public function getLogFormat()
    {
        return $this->config['log-format'];
    }

    public function getUserActivationSecret(): string
    {
        return $this->config['user-activation-secret'];
    }
}