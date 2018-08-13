<?php
/**
 * This File is part of JTL-Software
 *
 * User: mbrandt
 * Date: 30/04/18
 */

namespace JTL\Onetimelink\DAO;

use RedBeanPHP\OODBBean;
use RedBeanPHP\R;

class LinkDAO
{
    /** @var string */
    private $user;

    /** @var array */
    private $tags;

    /** @var string */
    private $hash;

    /** @var bool */
    private $is_guest_link;

    /** @var string */
    private $created;

    /** @var array */
    private $attachments;

    /** @var string|null */
    private $deleted;

    /** @var bool */
    private $is_protected_link;

    public function __construct(
        $user,
        $hash,
        $is_guest_link,
        $tags,
        $created,
        $attachments,
        $deleted = null,
        $is_protected_link = false
    )
    {
        $this->user = $user;
        $this->hash = $hash;
        $this->is_guest_link = $is_guest_link;
        $this->tags = $tags;
        $this->created = $created;
        $this->attachments = $attachments;
        $this->deleted = $deleted;
        $this->is_protected_link = $is_protected_link;
    }

    public function save(): bool
    {
        $link = R::findOne('link', 'hash = ?', [$this->getHash()]);

        if (!$link instanceof OODBBean) {
            $link = R::dispense('link');
        }

        $link->user = $this->getUser();
        $link->tags = implode(',', $this->getTags());
        $link->hash = $this->getHash();
        $link->isGuestLink = $this->isGuestLink();
        $link->created = $this->getCreated();
        $link->deleted = $this->getDeleted();
        $link->protected = $this->isProtectedLink();

        if (!$this->isGuestLink() && \count($this->getAttachments()) > 0) {
            $link->sharedAttachmentList = $this->getAttachments();
        }

        return R::store($link) !== false;
    }

    public function loadDBObject(): ?OODBBean
    {
        $linkBean = R::findOne('link', 'hash = ?', [$this->getHash()]);

        if ($linkBean instanceof OODBBean) {
            return $linkBean;
        }

        return null;
    }

    public static function constructFromDB(OODBBean $linkBean): LinkDAO
    {
        /*
         * Note: The tags are passed through a whole bunch of functions for a reason. Users can supply their own
         * tags and user input shouldn't be trusted anyway. There may be empty tags, so array_filter with the 'strlen'
         * callback gets rid of all empty tags. array_values() is necessary because the array returned from array_filter
         * will be converted to an object when it's passed to json_encode due to the fact that array_filter keeps
         * the array indexes.
         */
        return new self(
            $linkBean->user,
            $linkBean->hash,
            $linkBean->isGuestLink,
            array_values(array_filter(explode(',', $linkBean->tags), '\strlen')),
            $linkBean->created,
            $linkBean->sharedAttachmentList,
            $linkBean->deleted,
            (bool)$linkBean->protected
        );
    }

    public static function getLinkFromHash(string $hash): ?LinkDAO
    {
        $link = R::findOne('link', 'hash = ?', [$hash]);

        if ($link instanceof OODBBean) {
            return new self(
                $link->user,
                $link->hash,
                $link->isGuestLink,
                array_values(array_filter(explode(',', $link->tags), '\strlen')),
                $link->created,
                $link->sharedAttachmentList,
                $link->deleted,
                (bool)$link->protected
            );
        }

        return null;
    }

    /**
     * @return string
     */
    public function getUser(): string
    {
        return $this->user;
    }

    /**
     * @param string $user
     */
    public function setUser(string $user)
    {
        $this->user = $user;
    }

    /**
     * @return array
     */
    public function getTags(): array
    {
        $tags = $this->tags;
        if (\is_string($tags)) {
            $tags = array_values(array_filter(explode(',', $tags), '\strlen'));
        }

        return $tags;
    }

    /**
     * @param array $tags
     */
    public function setTags(array $tags)
    {
        $this->tags = $tags;
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
    public function setHash(string $hash)
    {
        $this->hash = $hash;
    }

    /**
     * @return bool
     */
    public function isGuestLink(): bool
    {
        return $this->is_guest_link;
    }

    /**
     * @param bool $is_guest_link
     */
    public function setIsGuestLink(bool $is_guest_link)
    {
        $this->is_guest_link = $is_guest_link;
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
    public function setCreated(string $created)
    {
        $this->created = $created;
    }

    /**
     * @return array
     */
    public function getAttachments(): array
    {
        return $this->attachments;
    }

    /**
     * @param array $attachments
     */
    public function setAttachments(array $attachments)
    {
        $this->attachments = $attachments;
    }

    /**
     * @return array
     */
    public function toArray(): array
    {
        return [
            'user' => $this->getUser(),
            'hash' => $this->getHash(),
            'is_guest_link' => $this->isGuestLink(),
            'tags' => $this->getTags(),
            'created' => $this->getCreated(),
            'attachments' => $this->getAttachments(),
            'deleted' => $this->getDeleted(),
            'is_protected_link' => $this->isProtectedLink(),
        ];
    }

    /**
     * @return null|string
     */
    public function getDeleted()
    {
        return $this->deleted;
    }

    /**
     * @param null|string $deleted
     */
    public function setDeleted(string $deleted)
    {
        $this->deleted = $deleted;
    }

    /**
     * @return bool
     */
    public function isProtectedLink(): bool
    {
        return $this->is_protected_link;
    }
}