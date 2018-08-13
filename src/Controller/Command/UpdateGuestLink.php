<?php
/**
 * This File is part of JTL-Software
 *
 * User: rherrgesell
 * Date: 08.08.17
 */

namespace JTL\Onetimelink\Controller\Command;


use JTL\Onetimelink\Controller\AbstractObservable;
use JTL\Onetimelink\Controller\CommandInterface;
use JTL\Onetimelink\Exception\DataNotFoundException;
use JTL\Onetimelink\Factory;
use JTL\Onetimelink\OneTimeLink;
use JTL\Onetimelink\Response;
use JTL\Onetimelink\Storage\DatabaseStorage;

class UpdateGuestLink extends AbstractObservable implements CommandInterface
{
    /**
     * @var CreateLink
     */
    private $createLink;

    /**
     * @var string
     */
    private $hash;

    /**
     * @var DatabaseStorage
     */
    private $storage;

    /**
     * @var Factory
     */
    private $factory;

    /**
     * @var \Monolog\Logger
     */
    private $logger;

    /**
     * UpdateGuestLink constructor.
     * @param CreateLink $createLink
     * @param DatabaseStorage $storage
     * @param string $hash
     * @param Factory $factory
     */
    public function __construct(
        CreateLink $createLink,
        DatabaseStorage $storage,
        string $hash,
        Factory $factory
    ) {
        $this->createLink = $createLink;
        $this->hash = $hash;
        $this->storage = $storage;
        $this->factory = $factory;
        $this->logger = $factory->createLogger();

        parent::__construct($factory->getConfig()->createNotifier());
    }

    /**
     * @return Response
     * @throws \RuntimeException
     * @throws DataNotFoundException
     * @throws \Exception
     */
    public function execute(): Response
    {
        $link = $this->storage->readLinkAsBean($this->hash);

        if ($link !== null && $link->is_guest_link) {
            $guestLinkCreator = $this->factory->createUserList()->getUser($link->user);

            $response = $this->createLink->execute();
            if ($response->isSuccessfulCreated()) {
                $userStorage = $this->factory->createUserMetaStorage($guestLinkCreator);

                $otl = $this->createLink->getOneTimeLink();
                if ($otl instanceof OneTimeLink) {
                    $message = $this->factory->getConfig()
                        ->createMessageForGuestLinkResponse(
                            $guestLinkCreator,
                            $otl->getHash(),
                            array_values(array_filter(explode(',', $link->tags), '\strlen'))
                        );
                    $this->notify($message);
                    $userStorage->appendLink($otl->getHash());

                    $this->logger->info("GuestLink/{$this->hash} was filled => new OTL/{$otl->getHash()} created");
                }

                $userStorage->setToDeleted($this->hash);
                $this->storage->deleteLink($this->hash);

                return $response;
            }
        } else {
            throw new DataNotFoundException("GuestLink/{$this->hash} not Found");
        }

        throw new \RuntimeException('Fail to update Guest Link');
    }
}