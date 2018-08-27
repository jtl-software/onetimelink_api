<?php
/**
 * This File is part of JTL-Software
 *
 * User: mbrandt
 * Date: 24/08/18
 */

namespace JTL\Onetimelink\Controller\Command;


use JTL\Onetimelink\Factory;
use JTL\Onetimelink\Response;
use JTL\Onetimelink\Storage\DatabaseStorage;
use JTL\Onetimelink\User;

class DeleteLink
{
    /** @var \JTL\Onetimelink\View\JsonView */
    private $view;

    /** @var \Monolog\Logger */
    private $logger;

    /** @var string */
    private $linkHash;

    /** @var string */
    private $auth;

    /** @var DatabaseStorage */
    private $storage;

    /** @var Factory */
    private $factory;

    public function __construct(
        DatabaseStorage $storage,
        Factory $factory,
        string $linkHash,
        string $auth
    )
    {
        $this->view = $factory->createJsonView();
        $this->logger = $factory->createLogger();
        $this->linkHash = $linkHash;
        $this->auth = $auth;
        $this->storage = $storage;
        $this->factory = $factory;
    }

    public function execute(): Response
    {
        $this->logger->info('Requested delete link auth for link hash ' . $this->linkHash . ' with auth '
                            . $this->auth);
        $ownerEmail = $this->storage->getDeleteAuthOwnerEmail($this->linkHash, $this->auth);
        $errorMessage = 'Could not delete link with link hash ' . $this->linkHash . ' and auth '
                        . $this->auth . ' from database.';

        if ($ownerEmail !== '') {
            $owner = User::createUserFromString($ownerEmail);
        } else {
            $this->logger->error("{$errorMessage} No owner email exists");
            return Response::createNotFound();
        }

        if ($this->storage->deleteDeleteAuth($this->linkHash, $this->auth, $owner)) {
            try {
                $userMetaStorage = $this->factory->createUserMetaStorage($owner);
                $this->storage->deleteLink($this->linkHash);
                $userMetaStorage->setToDeleted($this->linkHash);
            } catch (\Exception $e) {
                $this->logger->error("{$errorMessage} Error message: {$e->getMessage()}");
                return Response::createNotFound();
            }
        } else {
            $this->logger->error("{$errorMessage} No such entry exists in the database");
            return Response::createNotFound();
        }

        return Response::createSuccessful($this->view);
    }

}