<?php
/**
 * This File is part of JTL-Software
 *
 * User: mbrandt
 * Date: 27/04/18
 */

namespace JTL\Onetimelink\DAO;

use RedBeanPHP\OODBBean;
use RedBeanPHP\R;

class AttachmentDAO
{
    /** @var string */
    private $userEmail;

    /** @var string */
    private $created;

    /** @var string|null */
    private $deleted;

    /** @var string */
    private $fileType;

    /** @var string */
    private $fileName;

    /** @var string */
    private $hash;

    /** @var bool */
    private $isMerged;

    /** @var integer */
    private $size;

    /**
     * AttachmentDAO constructor.
     * @param $email
     * @param $created
     * @param $deleted
     * @param $fileType
     * @param $fileName
     * @param $hash
     * @param $isMerged
     * @param int $size
     */
    public function __construct(
        $email,
        $created,
        $deleted,
        $fileType,
        $fileName,
        $hash,
        $isMerged,
        $size = 0
    ) {
        $this->userEmail = $email;
        $this->created = $created;
        $this->deleted = $deleted;
        $this->fileType = $fileType;
        $this->fileName = $fileName;
        $this->hash = $hash;
        $this->isMerged = $isMerged;
        $this->size = $size;
    }

    /**
     * @return bool
     */
    public function save(): bool
    {
        $attachment = R::findOne('attachment', 'hash = ?', [$this->getHash()]);

        if (!$attachment instanceof OODBBean) {
            $attachment = R::dispense('attachment');
        }

        $attachment->userEmail = $this->getUserEmail();
        $attachment->created = $this->getCreated();
        $attachment->deleted = $this->getDeleted() ?? null;
        $attachment->filetype = $this->getFileType();
        $attachment->name = $this->getFileName();
        $attachment->hash = $this->getHash();
        $attachment->isMerged = $this->isMerged();
        $attachment->size = $this->getSize();

        return R::store($attachment) !== false;
    }

    public function delete(): void
    {
        R::trash($this->loadDBObject());
    }

    /**
     * @return null|OODBBean
     */
    public function loadDBObject(): ?OODBBean
    {
        $attachmentBean = R::findOne('attachment', 'hash = ?', [$this->getHash()]);

        if ($attachmentBean instanceof OODBBean) {
            return $attachmentBean;
        }

        return null;
    }

    /**
     * @param string $hash
     * @return AttachmentDAO|null
     */
    public static function getAttachmentFromHash(string $hash): ?AttachmentDAO
    {
        $attachment = R::findOne('attachment', 'hash = ?', [$hash]);

        if ($attachment instanceof OODBBean) {
            return new self(
                $attachment->userEmail,
                $attachment->created,
                $attachment->deleted,
                $attachment->filetype,
                $attachment->name,
                $attachment->hash,
                (bool)$attachment->isMerged,
                $attachment->size
            );
        }

        return null;
    }

    /**
     * @return string|null
     */
    public function getDeleted(): ?string
    {
        return $this->deleted;
    }

    /**
     * @param string $deleted
     */
    public function setDeleted(string $deleted): void
    {
        $this->deleted = $deleted;
    }

    /**
     * @return string
     */
    public function getUserEmail(): string
    {
        return $this->userEmail;
    }

    /**
     * @param string $userEmail
     */
    public function setUserEmail(string $userEmail): void
    {
        $this->userEmail = $userEmail;
    }

    /**
     * @return string
     */
    public function getCreated(): string
    {
        return $this->created;
    }

    /**
     * @param string $created
     */
    public function setCreated(string $created): void
    {
        $this->created = $created;
    }

    /**
     * @return string
     */
    public function getFileType(): string
    {
        return $this->fileType;
    }

    /**
     * @param string $fileType
     */
    public function setFileType(string $fileType): void
    {
        $this->fileType = $fileType;
    }

    /**
     * @return string
     */
    public function getFileName(): string
    {
        return $this->fileName;
    }

    /**
     * @param string $fileName
     */
    public function setFileName(string $fileName): void
    {
        $this->fileName = $fileName;
    }

    /**
     * @return string
     */
    public function getHash(): string
    {
        return $this->hash;
    }

    /**
     * @param string $hash
     */
    public function setHash(string $hash): void
    {
        $this->hash = $hash;
    }

    /**
     * @return bool
     */
    public function isMerged(): bool
    {
        return $this->isMerged;
    }

    /**
     * @param bool $isMerged
     */
    public function setIsMerged(bool $isMerged): void
    {
        $this->isMerged = $isMerged;
    }

    /**
     * @return int
     */
    public function getSize(): int
    {
        return $this->size;
    }

    /**
     * @param int $size
     */
    public function setSize(int $size): void
    {
        $this->size = $size;
    }

    /**
     * @return array
     */
    public function toArray(): array
    {
        return [
            'user_email' => $this->getUserEmail(),
            'created' => $this->getCreated(),
            'deleted' => $this->getDeleted(),
            'filetype' => $this->getFileType(),
            'name' => $this->getFileName(),
            'hash' => $this->getHash(),
            'merged' => $this->isMerged(),
            'size' => $this->getSize(),
        ];
    }
}
