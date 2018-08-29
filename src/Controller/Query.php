<?php
/**
 * This File is part of JTL-Software
 *
 * User: rherrgesell
 * Date: 02.08.17
 */

namespace JTL\Onetimelink\Controller;

use JTL\Onetimelink\Authentication\AuthenticationInterface;
use JTL\Onetimelink\Controller\Query\CheckLogin;
use JTL\Onetimelink\Controller\Query\CheckOneTimeLink;
use JTL\Onetimelink\Controller\Query\CheckPasswordResetHash;
use JTL\Onetimelink\Controller\Query\GetUploadLimits;
use JTL\Onetimelink\Controller\Query\ReadOneTimeLink;
use JTL\Onetimelink\DAO\LinkDAO;
use JTL\Onetimelink\Exception\AuthenticationException;
use JTL\Onetimelink\Exception\InvalidRouteException;
use JTL\Onetimelink\Factory;
use JTL\Onetimelink\Request;
use JTL\Onetimelink\Response;
use JTL\Onetimelink\Storage\DatabaseStorage;
use JTL\Onetimelink\User;
use JTL\Onetimelink\View\JsonView;
use Monolog\Logger;

class Query implements ControllerInterface
{
    /**
     * @var Request
     */
    private $request;
    /**
     * @var Factory
     */
    private $factory;
    /**
     * @var AuthenticationInterface
     */
    private $authentication;

    /**
     * @var DatabaseStorage
     */
    private $storage;

    /**
     * @var Logger
     */
    private $logger;

    /**
     * Controller constructor.
     *
     * @param Request $request
     * @param AuthenticationInterface $authentication
     * @param DatabaseStorage $storage
     * @param Factory $factory
     */
    public function __construct(
        Request $request,
        AuthenticationInterface $authentication,
        DatabaseStorage $storage,
        Factory $factory
    ) {
        $this->request = $request;
        $this->authentication = $authentication;
        $this->storage = $storage;
        $this->factory = $factory;
        $this->logger = $this->factory->createLogger();
    }

    /**
     * @return Response
     * @throws AuthenticationException
     * @throws InvalidRouteException
     */
    public function run(): Response
    {
        $user = $this->authentication->authenticate($this->factory->createUserList());
        $uri = $this->request->getUri();

        switch ($uri) {
            case preg_match('/^\/read\/(\w{9,})(\?view_text=1)?.*$/', $uri, $matches) === 1:
                $hash = $matches[1] ?? null;
                $view = (bool)($matches[2] ?? null);
                $query = new ReadOneTimeLink(
                    $this->storage,
                    $hash,
                    $user,
                    $this->request,
                    $this->factory,
                    $view
                );
                return $query->run();

            case preg_match('/^\/check\/(\w{9,})$/', $uri, $matches) === 1:
                $hash = $matches[1] ?? null;
                $query = new CheckOneTimeLink($this->storage, $hash, new JsonView(), $this->factory->createLogger());
                return $query->run();

            case preg_match('/^\/password-reset-check\/([\w+\/=]+)$/', $uri, $matches) === 1:
                $hash = $matches[1] ?? null;
                $query = new CheckPasswordResetHash($this->factory, $hash, new JsonView());
                return $query->run();

            case preg_match('/^\/upload_limits\/(\w{9,}).*$/', $uri, $matches) === 1:
                $hash = $matches[1] ?? null;
                $guestLinkDAO = LinkDAO::getLinkFromHash($hash);

                if ($guestLinkDAO === null) {
                    throw new \InvalidArgumentException('Guestlink does not exist');
                }

                return (new GetUploadLimits($this->factory, $this->factory->getConfig()->getMaxFileSize()))->run();

            case preg_match('/^\/upload_limits.*$/', $uri) === 1:
                $this->failWhenNotAuthenticated($user, $uri);
                $quota = $user->getQuota();

                if ($quota === 0) {
                    $quota = $this->factory->getConfig()->getDefaultUserQuota();
                }

                $query = new GetUploadLimits(
                    $this->factory,
                    $user->getMaxUploadSize(),
                    false,
                    $quota,
                    $user->getEmail()
                );
                return $query->run();

            case preg_match('/^\/_.*$/', $uri) === 1:
                return (new CheckLogin($user, $this->factory))->run();
        }

        throw new InvalidRouteException();
    }

    /**
     * @param User $user
     * @param $path
     * @throws AuthenticationException
     */
    private function failWhenNotAuthenticated(User $user, $path)
    {
        if (!$user->isAuthenticated()) {
            $this->logger->info("{$user->obfuscatedUsername()} is not allowed to perform operation (POST: {$path}) - authenticated: false");
            throw new AuthenticationException("{$user} is not allowed to perform operation (POST: {$path})");
        }

        if (false === $user->isActive()) {
            $this->logger->info("{$user->obfuscatedUsername()} is inactive and not allowed to perform operation (POST: {$path}) - active: false");
            throw new AuthenticationException("{$user} is inactive");
        }
    }
}
