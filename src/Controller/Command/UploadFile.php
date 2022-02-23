<?php
/**
 * This File is part of JTL-Software
 *
 * User: pkanngiesser
 * Date: 24.08.18
 */

namespace JTL\Onetimelink\Controller\Command;

use JTL\Onetimelink\Config;
use JTL\Onetimelink\Controller\CommandInterface;
use JTL\Onetimelink\DAO\ChunkDAO;
use JTL\Onetimelink\DAO\UploadDAO;
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

class UploadFile implements CommandInterface
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
     * @var Config
     */
    private $config;

    /**
     * UploadFile constructor.
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
        $this->config = $factory->getConfig();
    }

    /**
     * @return Response
     * @throws MissingParameterException
     */
    public function execute(): Response
    {
        $chunkSize = $this->config->getChunkSize();
        $maxFileSize = $this->config->getMaxFileSize();

        $payload = $this->createPayload();

        $chunkNumber = $this->request->readPost('resumableChunkNumber');
        $totalChunks = $this->request->readPost('resumableTotalChunks');
        $uploadToken = $this->request->readPost('uploadToken');

        $uploadDAO = UploadDAO::getUploadFromToken($uploadToken);

        if ($uploadDAO === null) {
            throw new \RuntimeException('Invalid upload token');
        }

        if ($uploadDAO->isDone()) {
            throw new \RuntimeException('Token already expired');
        }

        $hash = LinkHash::create($uploadToken);

        if ($payload->getMetaData()->getSize() > $chunkSize) {
            throw new \RuntimeException('Failed to store Data. Chunk too big');
        }

        if ($this->storage->isMergeDone($hash)) {
            $this->logger->debug("{$hash} is already merged");
            return Response::createSuccessfulCreated($this->view);
        }

        $receivedBytes = $uploadDAO->getReceivedBytes();
        $maxUploadSize = $uploadDAO->getMaxUploadSize();
        $receivedBytes += $payload->getMetaData()->getSize();
        if ($receivedBytes > $maxFileSize) {
            throw new \RuntimeException('Received too many chunks. File size limit hit');
        }

        if ($maxUploadSize !== 0 && $receivedBytes > $maxUploadSize) {
            throw new \RuntimeException('Quota has been exceeded. Upload cancelled.');
        }

        if ($chunkNumber <= $totalChunks && $this->storage->writeChunk($hash, $chunkNumber, $payload)) {
            $uploadChunk = new ChunkDAO($uploadToken, $chunkNumber);
            $uploadChunk->save();
            $uploadDAO->setReceivedBytes($receivedBytes);
            $uploadDAO->setReceivedChunks($uploadDAO->getReceivedChunks() + 1);
            $uploadDAO->save();
            $this->logger->debug("Process upload chunk {$chunkNumber}/{$totalChunks}", ['user' => (string)$this->user]);
            $this->logger->info("UPLOAD PROGRESS++++:    {$uploadDAO->getReceivedChunks()} - {$totalChunks}");
            if ($uploadChunk->hasCompletedAllChunks($uploadToken, $totalChunks)) {
                $this->logger->info("File upload is done - {$totalChunks} chunks uploaded - Hash {$hash}", ['user' => (string)$this->user]);
                $this->storage->mergeChunks($hash);
                $this->logger->info("File upload is done - {$totalChunks} chunks uploaded - Hash {$hash}", ['user' => (string)$this->user]);
                $uploadDAO->setDone(true);
                $uploadDAO->save();
            }
            return Response::createSuccessful($this->view);
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
        $size = 0;
        if ($data === null && isset($_FILES['file']['tmp_name'], $_FILES['file']['type'])) {
            $data = file_get_contents($_FILES['file']['tmp_name']);
            $contentType = $_FILES['file']['type'];
            $size = $_FILES['file']['size'];
        } elseif ($data !== null) {
            if (is_string($data)) {
                $size = strlen($data);
            } elseif (is_resource($data)) {
                $fstat = fstat($data);

                if (is_array($fstat)) {
                    $size = $fstat['size'] ?? 1;
                }
            }
        }

        if (empty($data)) {
            throw new MissingParameterException('Missing Data');
        }

        $metaData = new MetaData(
            $contentType ?? 'text/plain',
            $this->user,
            $filename,
            $size
        );

        return new Payload($data, $metaData);
    }
}
