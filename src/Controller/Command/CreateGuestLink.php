<?php
/**
 * This File is part of JTL-Software
 *
 * User: rherrgesell
 * Date: 02.08.17
 */

namespace JTL\Onetimelink\Controller\Command;

use JTL\Onetimelink\Controller\CommandInterface;
use JTL\Onetimelink\Factory;
use JTL\Onetimelink\LinkHash;
use JTL\Onetimelink\Request;
use JTL\Onetimelink\Response;
use JTL\Onetimelink\Storage\DatabaseStorage;
use JTL\Onetimelink\Storage\UserMetaDatabaseStorage;
use JTL\Onetimelink\User;
use JTL\Onetimelink\View\ViewInterface;

class CreateGuestLink implements CommandInterface
{

    /**
     * @var User
     */
    private $user;

    /**
     * @var DatabaseStorage
     */
    private $storage;

    /**
     * @var ViewInterface
     */
    private $view;

    /**
     * @var Request
     */
    private $request;

    /**
     * @var UserMetaDatabaseStorage
     */
    private $userMetaStorage;

    /**
     * @var \Monolog\Logger
     */
    private $logger;

    /**
     * CreateGuestLink constructor.
     *
     * @param DatabaseStorage $storage
     * @param User $user
     * @param Request $request
     * @param Factory $factory
     */
    public function __construct(DatabaseStorage $storage, User $user, Request $request, Factory $factory)
    {
        $this->user = $user;
        $this->storage = $storage;
        $this->view = $factory->createJsonView();
        $this->request = $request;

        $this->userMetaStorage = $factory->createUserMetaStorage($this->user);
        $this->logger = $factory->createLogger();
    }

    /**
     * @return Response
     * @throws \RuntimeException
     * @throws \Exception
     */
    public function execute(): Response
    {
        $data = $this->request->readInputAsJson();

        if ($data === null) {
            throw new \RuntimeException('Missing data');
        }

        $links = [];
        $tags = $data['tags'] ?? [];
        $amount = (int)$data['amount'];
        $isProtected = $data['protected'] ?? false;

        $this->logger->info("create {$amount} GuestLink(s)", ["user" => (string)$this->user]);
        for ($i = 0; $i < $amount; ++$i) {
            $hash = LinkHash::createUnique();

            if ($this->storage->writeGuestLink($hash, $this->user, $tags, $isProtected)) {
                $links[] = [
                    'onetimelink' => "/create/$hash",
                    'hash' => $hash,
                ];

                $this->userMetaStorage->appendLink($hash);
                $this->logger->info("New GuestLink/{$hash} created");
            }
        }

        $this->view->set('links', $links);
        return Response::createSuccessfulCreated($this->view);
    }
}
