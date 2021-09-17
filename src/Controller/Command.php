<?php
/**
 * This File is part of JTL-Software
 *
 * User: rherrgesell
 * Date: 02.08.17
 */

namespace JTL\Onetimelink\Controller;

use JTL\Onetimelink\Authentication\AuthenticationInterface;
use JTL\Onetimelink\Controller\Command\ActivateUser;
use JTL\Onetimelink\Controller\Command\CreateGuestLink;
use JTL\Onetimelink\Controller\Command\CreateLink;
use JTL\Onetimelink\Controller\Command\CreateUser;
use JTL\Onetimelink\Controller\Command\DeleteLink;
use JTL\Onetimelink\Controller\Command\DeleteUpload;
use JTL\Onetimelink\Controller\Command\GenerateUploadToken;
use JTL\Onetimelink\Controller\Command\Login;
use JTL\Onetimelink\Controller\Command\PasswordResetAction;
use JTL\Onetimelink\Controller\Command\PasswordResetRequest;
use JTL\Onetimelink\Controller\Command\UpdateGuestLink;
use JTL\Onetimelink\Controller\Command\UpdateUser;
use JTL\Onetimelink\Controller\Command\UploadFile;
use JTL\Onetimelink\DAO\LinkDAO;
use JTL\Onetimelink\Exception\AuthenticationException;
use JTL\Onetimelink\Exception\InvalidRouteException;
use JTL\Onetimelink\Factory;
use JTL\Onetimelink\Request;
use JTL\Onetimelink\Response;
use JTL\Onetimelink\Storage\DatabaseStorage;
use JTL\Onetimelink\User;

class Command implements ControllerInterface
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
     * @var \Monolog\Logger
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
        $this->logger = $factory->createLogger();
    }

    /**
     * @return Response
     * @throws \InvalidArgumentException
     * @throws AuthenticationException
     * @throws InvalidRouteException
     * @throws \JTL\Onetimelink\Exception\DataNotFoundException
     * @throws \JTL\Onetimelink\Exception\MissingParameterException
     * @throws \Exception
     */
    public function run(): Response
    {
        $user = $this->authentication->authenticate($this->factory->createUserList());
        $path = $this->request->getPath();

        if ($this->request->isPost()) {
            $requestData = $this->request->readInputAsJson();

            switch ($path) {
                case '/user/add':
                    return (new CreateUser(
                        $requestData['email'],
                        $requestData['password'],
                        $this->factory->createUserStorage(),
                        $this->factory->getConfig()->getMailWhitelistPattern(),
                        $this->factory->getConfig(),
                        $this->factory->createJsonView(),
                        $this->logger
                    ))->execute();

                case preg_match('/^\/user\/activate\/(\w+)$/', $path, $matches) === 1:
                    $hash = $matches[1] ?? null;

                    return (new ActivateUser(
                        $hash,
                        $this->factory->createUserStorage(),
                        $this->factory->getConfig(),
                        $this->factory->createJsonView(),
                        $this->logger
                    ))->execute();

                case '/user/update':
                    $this->failWhenNotAuthenticated($user, $path);
                    return (new UpdateUser(
                        (string)$user,
                        (string)$requestData['oldPassword'],
                        (string)$requestData['newPassword'],
                        $this->factory->createUserStorage(),
                        $this->factory->createJsonView(),
                        $this->logger
                    ))->execute();

                case '/login':
                    return (new Login(
                        $this->authentication,
                        $this->request->getSession(),
                        $this->factory->createUserList(),
                        $this->factory->createUserStorage(),
                        $this->request,
                        $this->factory->createJsonView(),
                        $this->logger
                    ))->execute();

                case '/password-reset-request':
                    return (new PasswordResetRequest(
                        $requestData['email'],
                        $this->factory,
                        $this->factory->createUserStorage(),
                        $this->factory->createUserList(),
                        $this->factory->createJsonView(),
                        $this->logger
                    ))->execute();

                case '/password-reset-action':
                    return (new PasswordResetAction(
                        $requestData['email'],
                        $requestData['newPassword'],
                        $this->factory,
                        $this->factory->createUserStorage(),
                        $this->factory->createJsonView(),
                        $this->logger
                    ))->execute();

                case preg_match('/^\/create\/guest.*$/', $path) === 1:
                    $this->failWhenNotAuthenticated($user, $path);
                    return (new CreateGuestLink(
                        $this->storage,
                        $user,
                        $this->request,
                        $this->factory
                    ))->execute();

                case preg_match('/^\/create\/([0-9a-f]{24})$/', $path, $matches) === 1:
                    $hash = $matches[1] ?? null;
                    $guestLinkDAO = LinkDAO::getLinkFromHash($hash);

                    if ($guestLinkDAO === null) {
                        throw new \InvalidArgumentException('Guestlink does not exist');
                    }

                    $tags = $guestLinkDAO->getTags();
                    $isProtected = $guestLinkDAO->isProtectedLink();

                    if ($guestLinkDAO->getDeleted() !== null) {
                        throw new \InvalidArgumentException('Guestlink has already been used');
                    }

                    $createLink = new CreateLink($this->storage, $user, $this->request, $this->factory, $tags, $isProtected);
                    $guestLink = new UpdateGuestLink($createLink, $this->storage, $hash, $this->factory);
                    return $guestLink->execute();

                case preg_match('/^\/create.*$/', $path) === 1:
                    $this->failWhenNotAuthenticated($user, $path);
                    return (new CreateLink($this->storage, $user, $this->request, $this->factory))
                        ->execute();

                case '/delete_link':
                    return (new DeleteLink(
                        $this->storage,
                        $this->factory,
                        $requestData['linkHash'],
                        $requestData['auth']
                    ))->execute();

                case preg_match('/^\/upload\/([0-9a-f]{24})$/', $path, $matches) === 1:
                    $hash = $matches[1] ?? null;
                    $guestLinkDAO = LinkDAO::getLinkFromHash($hash);
                    if ($guestLinkDAO === null) {
                        throw new \InvalidArgumentException('Guestlink does not exist');
                    }

                    if ($guestLinkDAO->getDeleted() !== null) {
                        throw new \InvalidArgumentException('Guestlink has already been used');
                    }

                    return (new UploadFile($this->storage, $user, $this->request, $this->factory))
                        ->execute();

                case preg_match('/^\/upload.*$/', $path) === 1:
                    return (new UploadFile($this->storage, $user, $this->request, $this->factory))
                        ->execute();

                case preg_match('/^\/request_upload\/([0-9a-f]{24})$/', $path, $matches) === 1:
                    $hash = $matches[1] ?? null;
                    $guestLinkDAO = LinkDAO::getLinkFromHash($hash);
                    if ($guestLinkDAO === null) {
                        throw new \InvalidArgumentException('Guestlink does not exist');
                    }

                    $maxUploadSize = $this->factory->getConfig()->getMaxFileSize();
                    return (new GenerateUploadToken($this->storage, true, $maxUploadSize, $hash))->execute();

                case preg_match('/^\/request_upload.*$/', $path) === 1:
                    $this->failWhenNotAuthenticated($user, $path);
                    $maxUploadSize =  $user->getMaxUploadSize();
                    if ($maxUploadSize === 0) {
                        $maxUploadSize = $this->factory->getConfig()->getMaxFileSize();
                    }

                    return (new GenerateUploadToken(
                        $this->storage,
                        false,
                        $maxUploadSize,
                        $user->getEmail(),
                        $user->getQuota()
                    ))->execute();

                case preg_match('/^\/delete_upload\/([0-9a-f]{24})$/', $path, $matches) === 1:
                    $token = $matches[1] ?? null;
                    return (new DeleteUpload($this->storage, $token))->execute();

            }
        }

        $this->logger->notice("No command defined for path [{$path}]");
        throw new InvalidRouteException("Command not found for {$path}");
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
