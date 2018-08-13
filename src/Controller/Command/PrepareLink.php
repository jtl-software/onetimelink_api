<?php
/**
 * This File is part of JTL-Software
 *
 * User: rherrgesell
 * Date: 02.08.17
 */

namespace JTL\Onetimelink\Controller\Command;


use JTL\Onetimelink\Controller\CommandInterface;
use JTL\Onetimelink\Exception\MissingParameterException;
use JTL\Onetimelink\Factory;
use JTL\Onetimelink\LinkHash;
use JTL\Onetimelink\Payload;
use JTL\Onetimelink\Request;
use JTL\Onetimelink\Response;
use JTL\Onetimelink\Storage\DatabaseStorage;
use JTL\Onetimelink\Storage\MetaData;
use JTL\Onetimelink\User;
use JTL\Onetimelink\View\ViewInterface;

class PrepareLink implements CommandInterface
{
    const PLAIN_DATA_FIELD = 'data';

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
     * @var \Monolog\Logger
     */
    private $logger;

    /**
     * CreateLink constructor.
     * @param DatabaseStorage $storage
     * @param User $user
     * @param Request $request
     * @param Factory $factory
     */
    public function __construct(DatabaseStorage $storage, User $user, Request $request, Factory $factory)
    {
        $this->storage = $storage;
        $this->request = $request;
        $this->view = $factory->createJsonView();
        $this->logger = $factory->createLogger();
        $this->user = $user;
    }

    /**
     * @return Response
     * @throws \RuntimeException
     * @throws MissingParameterException
     */
    public function execute(): Response
    {
        $payload = $this->createPayload();
        $chunkNumber = $this->request->readPost('resumableChunkNumber');
        $totalChunks = $this->request->readPost('resumableTotalChunks');
        $chunkID = $this->request->readPost('resumableIdentifier');
        $hash = LinkHash::create($chunkID);

        if ($this->storage->isMergeDone($hash)) {
            $this->logger->debug("{$hash} is already merged");
            return Response::createSuccessfulCreated($this->view);
        }

        if ($chunkNumber <= $totalChunks && $this->storage->writeChunk($hash, $chunkNumber, $payload)) {
            $this->logger->debug("Process upload chunk {$chunkNumber}/{$totalChunks}", ['user' => (string)$this->user]);
            if ($chunkNumber === $totalChunks) {
                $this->logger->info("File upload ist done - {$totalChunks} chunks uploaded - Hash {$hash}", ['user' => (string)$this->user]);
                $this->storage->mergeChunks($hash);
                $this->logger->info("File upload ist done - {$totalChunks} chunks uploaded - Hash {$hash}", ['user' => (string)$this->user]);
            }
            return Response::createSuccessfulCreated($this->view);
        }

        throw new \RuntimeException('Failed to store Data');
    }

    /**
     * @return Payload
     *
     * @throws MissingParameterException
     * @throws \Exception
     */
    private function createPayload(): Payload
    {
        $contentType = $this->request->readGet('type');
        $filename = $this->request->readPost('resumableFilename');

        $data = $this->request->readPost(self::PLAIN_DATA_FIELD);
        if ($data === null && isset($_FILES['file']['tmp_name'], $_FILES['file']['type'])) {
            $data = file_get_contents($_FILES['file']['tmp_name']);
            $contentType = $_FILES['file']['type'];
        }

        if (empty($data)) {
            throw new MissingParameterException('Missing Data');
        }

        $metaData = new MetaData(
            $contentType ?? 'text/plain',
            $this->user,
            $filename
        );

        return new Payload($data, $metaData);
    }
}

