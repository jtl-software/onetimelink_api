<?php
/**
 * This File is part of JTL-Software
 *
 * User: rherrgesell
 * Date: 11.08.17
 */

namespace JTL\Onetimelink\Controller\Command;


use JTL\Onetimelink\Authentication\AuthenticationInterface;
use JTL\Onetimelink\Controller\CommandInterface;
use JTL\Onetimelink\Exception\AuthenticationException;
use JTL\Onetimelink\PasswordHash;
use JTL\Onetimelink\Request;
use JTL\Onetimelink\Response;
use JTL\Onetimelink\Session\Session;
use JTL\Onetimelink\Storage\UserStorage;
use JTL\Onetimelink\UserList;
use JTL\Onetimelink\View\JsonView;
use Monolog\Logger;

class Login implements CommandInterface
{

    /**
     * @var AuthenticationInterface
     */
    private $auth;

    /**
     * @var JsonView
     */
    private $view;

    /**
     * @var Session
     */
    private $session;

    /**
     * @var UserList
     */
    private $userList;

    /**
     * @var UserStorage
     */
    private $storage;

    /**
     * @var Request
     */
    private $request;
    /**
     * @var Logger
     */
    private $logger;

    /**
     * Login constructor.
     * @param AuthenticationInterface $auth
     * @param Session $session
     * @param UserList $userList
     * @param UserStorage $storage
     * @param Request $request
     * @param JsonView $view
     * @param Logger $logger
     */
    public function __construct(
        AuthenticationInterface $auth,
        Session $session,
        UserList $userList,
        UserStorage $storage,
        Request $request,
        JsonView $view,
        Logger $logger
    )
    {
        $this->auth = $auth;
        $this->view = $view;
        $this->session = $session;
        $this->userList = $userList;
        $this->storage = $storage;
        $this->request = $request;
        $this->logger = $logger;
    }

    /**
     * @return Response
     * @throws \RuntimeException
     * @throws AuthenticationException
     */
    public function execute(): Response
    {
        $user = $this->auth->authenticate($this->userList);

        if (!$user->isAuthenticated()) {
            $this->logger->info("Fail to authenticate user {$user}");
            throw new AuthenticationException("Fail to authenticate '{$user}'");
        }

        if (!$user->isActive()) {
            $this->logger->info("Fail to authenticate user {$user} - user is inactive");
            throw new AuthenticationException('inactive');
        }

        /** old behaviour must be removed soon */
        if (!filter_var($user->getEmail(), FILTER_VALIDATE_EMAIL)) {
            $username = (string)$this->request->readServer('PHP_AUTH_USER');
            $password = (string)$this->request->readServer('PHP_AUTH_PW');
            $userList = $this->storage->read();

            if (isset($userList['user'][$username])) {
                $userEmail = $userList['user'][$username]['email'];
                $userList['user'][$userEmail] = [
                    'password' => PasswordHash::createHash($userEmail, $password),
                    'active' => true,
                    'created_at' => (new \DateTimeImmutable())->format('c'),
                ];

                unset($userList['user'][$username]);

                if ($this->storage->write($userList) === true) {
                    $this->view->set('login_with_email', true);

                    $this->logger->info(
                        "User logged in with username '{$username}' instead of email. 
                        Replacing username with email '{$userEmail}'"
                    );
                    return Response::createSuccessful($this->view);
                }
            }

            $this->logger->info("User '{$username}' attempted login with username. No email found for user");
            throw new AuthenticationException('Login failed');
        }

        $this->view
            ->set('authtoken', $this->auth->generateAuthToken($user))
            ->set('authuser', (string)$user)
            ->set('session', $this->session->getSessionId());

        $this->logger->info("Login successful for user {$user} - enjoy!");
        return Response::createSuccessful($this->view);
    }
}