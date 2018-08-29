<?php
/**
 * This File is part of JTL-Software
 *
 * User: pkanngiesser
 * Date: 24.08.18
 */

namespace JTL\Onetimelink\DAO;

use RedBeanPHP\OODBBean;
use RedBeanPHP\R;

class UploadDAO
{
    /**
     * @var string
     */
    private $token;

    /**
     * @var int
     */
    private $receivedBytes;

    /**
     * @var int
     */
    private $maxUploadSize;

    /**
     * @var bool
     */
    private $done;

    /**
     * @var string
     */
    private $identifier;

    /**
     * @var string
     */
    private $created;

    /**
     * UploadDAO constructor.
     * @param $token
     * @param int $receivedBytes
     * @param int $maxUploadSize
     * @param bool $done
     * @param string|null $identifier
     * @param string|null $created
     */
    public function __construct(
        $token,
        $receivedBytes = 0,
        $maxUploadSize = 0,
        bool $done = false,
        string $identifier = null,
        string $created = null
    ) {
        $this->token = $token;
        $this->receivedBytes = $receivedBytes;
        $this->maxUploadSize = $maxUploadSize;
        $this->done = $done;
        $this->identifier = $identifier;
        $this->created = $created;
    }

    /**
     * @return bool
     */
    public function save() : bool
    {
        $upload = R::findOne('upload', 'token = ?', [$this->token]);

        if (!$upload instanceof OODBBean) {
            $upload = R::dispense('upload');
        }

        $upload->token = $this->getToken();
        $upload->receivedBytes = $this->getReceivedBytes();
        $upload->maxUploadSize = $this->getMaxUploadSize();
        $upload->identifier = $this->getIdentifier();
        $upload->done = $this->isDone();
        $upload->created = $this->getCreated();
        return R::store($upload);
    }

    public function delete() : void
    {
        R::trash($this->loadDBObject());
    }

    /**
     * @param string $token
     */
    public function setToken(string $token)
    {
        $this->token = $token;
    }

    /**
     * @return string
     */
    public function getToken() : string
    {
        return $this->token;
    }

    /**
     * @param int $receivedBytes
     */
    public function setReceivedBytes(int $receivedBytes)
    {
        $this->receivedBytes = $receivedBytes;
    }

    /**
     * @return int
     */
    public function getReceivedBytes() : int
    {
        return $this->receivedBytes;
    }

    /**
     * @param int $maxUploadSize
     */
    public function setMaxUploadSize(int $maxUploadSize)
    {
        $this->maxUploadSize = $maxUploadSize;
    }

    /**
     * @return int
     */
    public function getMaxUploadSize() : int
    {
        return $this->maxUploadSize;
    }

    /**
     * @param bool $done
     */
    public function setDone(bool $done)
    {
        $this->done = $done;
    }

    /**
     * @return bool
     */
    public function isDone() : bool
    {
        return $this->done;
    }

    /**
     * @param string $identifier
     */
    public function setIdentifier(string $identifier)
    {
        $this->identifier = $identifier;
    }

    /**
     * @return string
     */
    public function getIdentifier() : string
    {
        return $this->identifier;
    }

    /**
     * @param string $created
     */
    public function setCreated(string $created)
    {
        $this->created = $created;
    }

    /**
     * @return string
     */
    public function getCreated()
    {
        return $this->created;
    }

    public function loadDBObject(): ?OODBBean
    {
        $uploadBean = R::findOne('upload', 'token = ?', [$this->getToken()]);

        if ($uploadBean instanceof OODBBean) {
            return $uploadBean;
        }

        return null;
    }

    /**
     * @param $token
     * @return UploadDAO|null
     */
    public static function getUploadFromToken($token): ?UploadDAO
    {
        $upload = R::findOne('upload', 'token = ?', [$token]);

        if ($upload instanceof OODBBean) {
            return new self(
                $upload->token,
                $upload->receivedBytes,
                $upload->maxUploadSize,
                $upload->done,
                $upload->identifier,
                $upload->created
            );
        }

        return null;
    }

    /**
     * @param $identifier
     * @return UploadDAO|null
     */
    public static function getUploadFromIdentifier($identifier): ?UploadDAO
    {
        $upload = R::findOne('upload', 'identifier = ?', [$identifier]);

        if ($upload instanceof OODBBean) {
            return new self(
                $upload->token,
                $upload->receivedBytes,
                $upload->maxUploadSize,
                $upload->done,
                $upload->identifier,
                $upload->created
            );
        }

        return null;
    }
}
