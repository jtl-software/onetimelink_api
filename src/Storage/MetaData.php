<?php
/**
 * This File is part of JTL-Software
 *
 * User: rherrgesell
 * Date: 03.08.17
 */

namespace JTL\Onetimelink\Storage;


use JTL\Onetimelink\User;

class MetaData
{

    const IDX_FILE_TYPE = 'filetype';
    const IDX_CREATED_BY_MAIL = 'user_email';
    const IDX_CREATED = 'created';
    const IDX_ORIGINAL_FILE_NAME = 'name';
    const IDX_FILE_SIZE = 'chunk_size';

    /**
     * @var string
     */
    private $fileType;

    /**
     * @var User
     */
    private $user;

    /**
     * @var \DateTimeImmutable
     */
    private $created;

    /**
     * @var string
     */
    private $filename;

    /**
     * @var int
     */
    private $size;

    /**
     * @param array $metaData
     *
     * @return MetaData
     * @throws \Exception
     */
    public static function createFromExistingMetaData(array $metaData): MetaData
    {

        if (!isset($metaData[self::IDX_FILE_TYPE])) {
            throw new \RuntimeException("Missing field " . self::IDX_FILE_TYPE);
        }

        if (!isset($metaData[self::IDX_CREATED_BY_MAIL])) {
            throw new \RuntimeException("Missing field " . self::IDX_CREATED_BY_MAIL);
        }

        if (!isset($metaData[self::IDX_CREATED])) {
            throw new \RuntimeException("Missing field " . self::IDX_CREATED);
        }

        $user = User::createUserFromString($metaData[self::IDX_CREATED_BY_MAIL]);

        $filename = $metaData[self::IDX_ORIGINAL_FILE_NAME] ?? null;

        $fileSize = $metaData[self::IDX_FILE_SIZE] ?? null;

        return new MetaData(
            $metaData[self::IDX_FILE_TYPE],
            $user,
            $filename,
            $fileSize,
            new \DateTimeImmutable($metaData[self::IDX_CREATED])
        );
    }

    /**
     * MetaData constructor.
     * @param string $fileType
     * @param User $user
     * @param string|null $filename
     * @param \DateTimeImmutable|null $created
     * @param int|null $size
     * @throws \Exception
     */
    public function __construct(
        string $fileType,
        User $user,
        string $filename = null,
        int $size = null,
        \DateTimeImmutable $created = null
    ) {
        $this->fileType = $fileType;
        $this->user = $user;
        $this->size = $size;
        $this->filename = $filename;
        if ($this->filename === null) {
            $this->filename = uniqid('file') . '.txt';
        }

        $this->created = $created;
        if ($this->created === null) {
            $this->created = new \DateTimeImmutable();
        }
    }

    /**
     * @return string
     */
    public function getFileType(): string
    {
        return $this->fileType;
    }

    /**
     * @return User
     */
    public function getUser(): User
    {
        return $this->user;
    }

    /**
     * @return \DateTimeImmutable
     */
    public function getCreated(): \DateTimeImmutable
    {
        return $this->created;
    }

    /**
     * @return string
     */
    public function getFilename(): string
    {
        return $this->filename;
    }

    /**
     * @return int
     */
    public function getSize(): int {
        return $this->size;
    }

    /**
     * @return string
     */
    public function toJson(): string
    {
        return json_encode($this->toArray(), JSON_PRETTY_PRINT);
    }

    /**
     * @return array
     */
    public function toArray(): array
    {
        $_ = [
            self::IDX_FILE_TYPE => $this->getFileType(),
            self::IDX_CREATED_BY_MAIL => (string)$this->getUser(),
            self::IDX_ORIGINAL_FILE_NAME => $this->getFilename(),
            self::IDX_CREATED => $this->created->format('c'),
        ];
        return $_;
    }
}