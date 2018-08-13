<?php
/**
 * This File is part of JTL-Software
 *
 * User: mbrandt
 * Date: 18/05/18
 */

namespace JTL\Onetimelink\Controller\Command;


use JTL\Onetimelink\Controller\AbstractObservable;
use JTL\Onetimelink\Controller\CommandInterface;
use JTL\Onetimelink\Factory;
use JTL\Onetimelink\Response;
use JTL\Onetimelink\Storage\UserStorage;
use JTL\Onetimelink\User;
use JTL\Onetimelink\UserList;
use JTL\Onetimelink\View\ViewInterface;
use Monolog\Logger;

class PasswordResetRequest extends AbstractObservable implements CommandInterface
{

    /**
     * @var string
     */
    private $email;

    /**
     * @var UserStorage
     */
    private $storage;

    /**
     * @var UserList
     */
    private $userList;

    /**
     * @var ViewInterface
     */
    private $view;

    /**
     * @var Factory
     */
    private $factory;
    /**
     * @var Logger
     */
    private $logger;

    /**
     * PasswordResetRequest constructor.
     * @param string $email
     * @param Factory $factory
     * @param UserStorage $storage
     * @param UserList $userList
     * @param ViewInterface $view
     * @param Logger $logger
     */
    public function __construct(
        string $email,
        Factory $factory,
        UserStorage $storage,
        UserList $userList,
        ViewInterface $view,
        Logger $logger
    ) {
        $this->email = $email;
        $this->storage = $storage;
        $this->userList = $userList;
        $this->view = $view;
        $this->factory = $factory;
        $this->logger = $logger;

        parent::__construct($factory->getConfig()->createNotifier());
    }

    public function execute(): Response
    {
        $hash = base64_encode(bin2hex(random_bytes(64)));
        $this->view->set('reset_hash', $hash);
        $obfuscated = "md5/" . md5($this->email);

        $requestUser = $this->userList->getUser($this->email);
        if ($requestUser->equals(User::createAnonymousUser())) {
            $this->logger->info("Unknown user {$this->email} try to reset password");

            //always return a successful response; to avoid sniffing user emails in OTL System
            return Response::createSuccessful($this->view);
        }

        $userList = $this->storage->read();
        foreach ($userList['user'] as $email => $user) {
            if ($email === $requestUser->getEmail()) {

                $created = (new \DateTimeImmutable())->format('Y-m-d H:i:s');
                $userList['user'][$email]['reset_hash'] = $hash;
                $userList['user'][$email]['reset_hash_created'] = $created;
                break;
            }
        }

        if ($this->storage->write($userList)) {

            $message = $this->factory->getConfig()
                ->createMessageForPasswordReset(
                    $requestUser->getEmail(),
                    $hash
                );
            $this->notify($message);


            $this->logger->info("Send password reset mail to User {$obfuscated} - reset hash is {$hash}");
            return Response::createSuccessful($this->view);
        }

        $this->logger->error("Could not store user password hash for user {$obfuscated}");
        throw new \RuntimeException("Could not store reset password hash for user");
    }
}