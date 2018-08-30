<?php
/**
 * This File is part of JTL-Software
 *
 * User: mbrandt
 * Date: 23/04/18
 */

namespace JTL\Onetimelink\Storage;

use JTL\Onetimelink\DAO\AttachmentDAO;
use JTL\Onetimelink\DAO\LinkDAO;
use JTL\Onetimelink\Exception\DataNotFoundException;
use JTL\Onetimelink\Payload;
use JTL\Onetimelink\User;
use RedBeanPHP\OODBBean;
use RedBeanPHP\R;

class DatabaseStorage
{
    const DATETIME_FORMAT = 'Y-m-d H:i:s';

    /** @var LocationDirectory */
    private $directory;

    /**
     * FilesystemStorage constructor.
     *
     * @param LocationDirectory $directory
     */
    public function __construct(LocationDirectory $directory)
    {
        $this->directory = $directory;
    }

    /**
     * @param string $hash
     * @return bool
     * @throws \RuntimeException
     */
    public function mergeChunks(string $hash): bool
    {
        $chunkDir = $this->getDirectory($hash);

        $files = glob($chunkDir . '*');
        $numDataFiles = \count($files);

        /*
         * See https://github.com/23/resumable.js
         * resumableChunkNumber:
         *      The index of the chunk in the current upload. First chunk is 1 (no base-0 counting here).
         */
        for ($i = 1; $i <= $numDataFiles; ++$i) {
            $filename = $chunkDir . $hash . $i;
            $chunkContents = file_get_contents($filename);
            file_put_contents($this->getDataFileLocation($hash), $chunkContents, FILE_APPEND);

            if (file_exists($filename)) {
                unlink($filename);
            }
        }

        $attachment = AttachmentDAO::getAttachmentFromHash($hash);
        if ($attachment !== null) {
            if (($fileSize = filesize($this->getDataFileLocation($hash))) === false) {
                return false;
            }
            $attachment->setIsMerged(true);
            $attachment->setSize($fileSize);
            return $attachment->save() !== false;
        }

        return false;
    }

    public function isMergeDone(string $hash): bool
    {
        $attachment = AttachmentDAO::getAttachmentFromHash($hash);

        if ($attachment === null) {
            return false;
        }

        return $attachment->isMerged();
    }

    /**
     * @param string $hash
     * @param Payload $data
     * @return bool
     */
    public function write(string $hash, Payload $data): bool
    {
        $attachmentResult = $this->createAttachment($hash, $data);
        $dataResult = file_put_contents($this->getDataFileLocation($hash), $data->getData());

        return $dataResult && $attachmentResult;
    }

    /**
     * @param string $hash
     * @param int $chunk
     * @param Payload $data
     * @return bool
     * @throws \RuntimeException
     */
    public function writeChunk(string $hash, int $chunk, Payload $data): bool
    {
        $dataResult = file_put_contents($this->getDataFileLocation($hash . $chunk), $data->getData());
        $attachmentResult = $this->createAttachment($hash, $data);

        return $dataResult && $attachmentResult;
    }

    /**
     * @param string $hash
     * @param array $attachments
     * @param User $user
     * @param array $tags
     * @param bool $isProtectedLink
     * @return bool
     * @throws \Exception
     */
    public function writeLink(
        string $hash,
        array $attachments,
        User $user,
        array $tags = [],
        bool $isProtectedLink = false
    ): bool {
        $attachmentBeans = [];
        /** @var AttachmentDAO $attachment */
        foreach ($attachments as $attachment) {
            $attachmentBeans[] = R::findOne(
                'attachment',
                'hash = ?',
                [$attachment->getHash()]
            );
        }

        $link = new LinkDAO(
            (string)$user,
            $hash,
            false,
            $tags,
            (new \DateTimeImmutable())->format(self::DATETIME_FORMAT),
            $attachmentBeans,
            null,
            $isProtectedLink
        );

        return $link->save();
    }

    /**
     * @param string $hash
     * @param User $user
     * @param array $tags
     * @param bool $isProtected
     * @return bool
     * @throws \Exception
     */
    public function writeGuestLink(
        string $hash,
        User $user,
        array $tags = [],
        bool $isProtected = false
    ): bool {
        $link = R::dispense('link');
        $link->user = (string)$user;
        $link->tags = implode(',', $tags);
        $link->hash = $hash;
        $link->is_guest_link = true;
        $link->created = (new \DateTimeImmutable())->format(self::DATETIME_FORMAT);
        $link->protected = $isProtected;

        return R::store($link) !== false;
    }

    /**
     * @param string $hash
     * @return null|OODBBean
     */
    public function readLinkAsBean(string $hash)
    {
        $link = R::findOne('link', 'hash = ?', [$hash]);

        if ($link instanceof OODBBean) {
            return $link;
        }

        return null;
    }

    /**
     * @param string $hash
     * @return Payload
     * @throws \Exception
     */
    public function readAttachment(string $hash): Payload
    {
        $attachment = AttachmentDAO::getAttachmentFromHash($hash);
        return new Payload('', MetaData::createFromExistingMetaData($attachment->toArray()));
    }

    /**
     * @param string $hash
     * @throws DataNotFoundException
     */
    public function deleteAttachment(string $hash): void
    {
        unlink($this->getAttachmentLocation($hash));
    }

    /**
     * @param string $hash
     * @return string
     * @throws \RuntimeException
     * @throws DataNotFoundException
     * @throws \Exception
     */
    public function getAttachmentLocation(string $hash): string
    {
        if (!file_exists($this->getDataFileLocation($hash))) {
            throw new DataNotFoundException(
                "No Data exists '{$this->getDataFileLocation($hash)}'"
            );
        }

        return $this->getDataFileLocation($hash);
    }

    /**
     * @param string $hash
     * @throws \Exception
     */
    public function deleteLink(string $hash)
    {
        $link = R::findOne('link', 'hash = ?', [$hash]);

        if ($link === null) {
            return;
        }

        $link->deleted = (new \DateTimeImmutable())->format(self::DATETIME_FORMAT);
        R::store($link);
    }

    /**
     * @param string $linkHash
     * @param string $auth
     * @param string $owner
     * @return bool
     */
    public function createDeleteAuth(string $linkHash, string $auth, string $owner): bool
    {
        $deleteAuth = R::findOne('deleteauth', 'hash = ? and auth = ? and owner = ?', [
            $linkHash,
            $auth,
            $owner
        ]);

        if ($deleteAuth === null) {
            $deleteAuth = R::dispense('deleteauth');
        }

        $deleteAuth->hash = $linkHash;
        $deleteAuth->auth = $auth;
        $deleteAuth->owner = $owner;

        return R::store($deleteAuth) !== false;
    }

    /**
     * @param string $linkHash
     * @param string $auth
     * @return string
     */
    public function getDeleteAuthOwnerEmail(string $linkHash, string $auth): string
    {
        $deleteAuth = R::findOne('deleteauth', 'hash = ? and auth = ?', [
            $linkHash,
            $auth,
        ]);

        if ($deleteAuth instanceof OODBBean) {
            return $deleteAuth->owner;
        }

        return '';
    }

    /**
     * @param string $linkHash
     * @param string $auth
     * @param string $owner
     * @return bool
     */
    public function deleteDeleteAuth(string $linkHash, string $auth, string $owner): bool
    {
        $deleteAuth = R::findOne('deleteauth', 'hash = ? and auth = ? and owner = ?', [
            $linkHash,
            $auth,
            $owner
        ]);

        if ($deleteAuth instanceof OODBBean) {
            R::trash($deleteAuth);
            return true;
        }

        return false;
    }

    /**
     * Create attachment if it doesn't exist otherwise do nothing
     *
     * @param string $hash
     * @param Payload $data
     * @return bool
     */
    private function createAttachment(string $hash, Payload $data): bool
    {
        $attachment = AttachmentDAO::getAttachmentFromHash($hash);

        if ($attachment !== null) {
            return true;
        }

        $meta = $data->getMetaData()->toArray();
        $attachment = R::dispense('attachment');

        foreach ($meta as $key => $value) {
            $attachment[$key] = $value;
        }

        $attachment['hash'] = $hash;

        return R::store($attachment) !== false;
    }

    /**
     * @param string $hash
     * @return string
     * @throws \RuntimeException
     */
    private function getDirectory(string $hash): string
    {
        $directory = (string)$this->directory . substr($hash, 0, 2);
        if (!is_dir($directory)) {
            if (!mkdir($directory) && !is_dir($directory)) {
                throw new \RuntimeException(sprintf('Directory "%s" was not created', $directory));
            }
        }

        return $directory . '/';
    }

    /**
     * @param string $hash
     * @return string
     * @throws \RuntimeException
     */
    private function getDataFileLocation(string $hash): string
    {
        return $this->getDirectory($hash) . $hash;
    }
}
