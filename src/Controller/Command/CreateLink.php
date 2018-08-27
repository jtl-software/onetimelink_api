<?php
/**
 * This File is part of JTL-Software
 *
 * User: rherrgesell
 * Date: 02.08.17
 */

namespace JTL\Onetimelink\Controller\Command;


use JTL\Onetimelink\Controller\CommandInterface;
use JTL\Onetimelink\DAO\AttachmentDAO;
use JTL\Onetimelink\Factory;
use JTL\Onetimelink\LinkHash;
use JTL\Onetimelink\OneTimeLink;
use JTL\Onetimelink\Payload;
use JTL\Onetimelink\Request;
use JTL\Onetimelink\Response;
use JTL\Onetimelink\Storage\DatabaseStorage;
use JTL\Onetimelink\Storage\MetaData;
use JTL\Onetimelink\Storage\UserMetaDatabaseStorage;
use JTL\Onetimelink\User;
use JTL\Onetimelink\View\ViewInterface;

class CreateLink implements CommandInterface
{
    /**
     * @var DatabaseStorage
     */
    private $storage;

    /**
     * @var Request
     */
    private $request;

    /**
     * @var ViewInterface
     */
    private $view;

    /**
     * @var User
     */
    private $user;

    /**
     * @var OneTimeLink
     */
    private $link;

    /**
     * @var UserMetaDatabaseStorage
     */
    private $userStorage;

    /**
     * @var array
     */
    private $tags;
    /**
     * @var bool
     */
    private $isProtected;

    /**
     * @var \Monolog\Logger
     */
    private $logger;

    /**
     * CreateLink constructor.
     * @param DatabaseStorage $storage
     * @param User $user
     * @param Request $request
     * @param Factory $factory
     * @param array $tags
     * @param bool $isProtected
     */
    public function __construct(
        DatabaseStorage $storage,
        User $user,
        Request $request,
        Factory $factory,
        array $tags = [],
        bool $isProtected = false)
    {
        $this->storage = $storage;
        $this->request = $request;
        $this->view = $factory->createJsonView();

        $this->user = $user;
        $this->userStorage = $factory->createUserMetaStorage($this->user);
        $this->logger = $factory->createLogger();
        $this->tags = $tags;
        $this->isProtected = $isProtected;
    }

    /**
     * @return OneTimeLink|null
     */
    public function getOneTimeLink()
    {
        return $this->link;
    }

    /**
     * @return Response
     * @throws \Exception
     */
    public function execute(): Response
    {
        $data = $this->request->readInputAsJson();

        if ($data === null) {
            throw new \RuntimeException('Missing data');
        }

        $attachmentCount = 0;
        $allowedKeys = ['amount', 'text', 'protected', 'tags'];

        /** @var array $data */
        foreach ($data as $key => $value) {
            if (!\in_array($key, $allowedKeys, true)) {
                ++$attachmentCount;
            }
        }

        if ($attachmentCount > 1) {
            throw new \RuntimeException('Too many attachments. You may only upload one attachment!');
        }

        $tags = $data['tags'] ?? [];

        if (\count($tags) === 0) {
            $tags = $this->tags;
        }

        $hashes = [];
        $attachments = [];

        /** @var array $data */
        foreach ($data as $key => $value) {
            if ($key === 'amount' || $key == 'tags') {
                continue;
            }

            if ($key === 'protected') {
                $this->isProtected = (bool)$value;
                continue;
            }

            if ($key === 'text') {
                if ($value === '') {
                    continue;
                }

                $metaData = new MetaData(
                    'text/plain',
                    $this->user,
                    '-#-TEXTINPUT-#-'
                );
                $payload = new Payload($value, $metaData);
                $hashes[$key] = LinkHash::createUnique();

                if (!$this->storage->write($hashes[$key], $payload)) {
                    throw new \RuntimeException('Could not store link meta data');
                }

                $attachments[$key] = AttachmentDAO::getAttachmentFromHash($hashes[$key]);
            } else {
                $hashes[$key] = LinkHash::create($value);
                $attachments[$key] = AttachmentDAO::getAttachmentFromHash($hashes[$key]);
            }
        }

        $links = [];
        $this->logger->info("create {$data['amount']} OTL(s)", ["user" => (string)$this->user]);
        for ($i = 0; $i < $data['amount']; ++$i) {
            $linkHash = LinkHash::createUnique();
            if ($this->storage->writeLink($linkHash, $attachments, $this->user, $tags, $this->isProtected)) {
                $this->link = new OneTimeLink($linkHash, $this->user);
                $links[] = $this->link->toArray();
                $this->userStorage->appendLink($linkHash);

                $this->logger->info("New OTL/{$this->link->getHash()} created");
            }
        }

        $this->view->set('links', $links);
        return Response::createSuccessfulCreated($this->view);
    }
}

