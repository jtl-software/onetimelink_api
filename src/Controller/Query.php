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
use JTL\Onetimelink\Controller\Query\ReadOneTimeLink;
use JTL\Onetimelink\Exception\InvalidRouteException;
use JTL\Onetimelink\Factory;
use JTL\Onetimelink\Request;
use JTL\Onetimelink\Response;
use JTL\Onetimelink\Storage\DatabaseStorage;
use JTL\Onetimelink\View\JsonView;

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
    )
    {
        $this->request = $request;
        $this->authentication = $authentication;
        $this->storage = $storage;
        $this->factory = $factory;
    }

    /**
     * @return Response
     *
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

            case preg_match('/^\/_.*$/', $uri) === 1:
                return (new CheckLogin($user, $this->factory))->run();
        }

        throw new InvalidRouteException();
    }
}