<?php
/**
 * This File is part of JTL-Software
 *
 * User: rherrgesell
 * Date: 24.08.17
 */

namespace JTL\Onetimelink\Storage;

use JTL\Onetimelink\DAO\LinkDAO;
use JTL\Onetimelink\User;
use RedBeanPHP\R;

/**
 * Class UserMetaStorage
 * @package JTL\Onetimelink\Storage
 */
class UserMetaDatabaseStorage
{
    const DATETIME_FORMAT = 'Y-m-d H:i:s';

    /**
     * @var User
     */
    private $user;

    /**
     * UserMetaStorage constructor.
     *
     * @param User $user
     */
    public function __construct(User $user)
    {
        $this->user = $user;
    }

    /**
     * @param string $hash
     */
    public function appendLink(string $hash)
    {
        $userMeta = R::dispense('usermeta');
        $userMeta->user = (string)$this->user;
        $userMeta->hash = $hash;
        $userMeta->deleted = false;

        R::store($userMeta);
    }

    /**
     * @param string $hash
     * @throws \Exception
     */
    public function setToDeleted(string $hash)
    {
        $meta = R::findOne('usermeta', 'hash = ? AND user = ?', [$hash, (string)$this->user]);

        if ($meta !== null) {
            $meta->deleted = (new \DateTimeImmutable())->format(self::DATETIME_FORMAT);
            R::store($meta);
        }
    }

    public function getLinks()
    {
        $userMetas = R::find('usermeta', 'user = ?', [(string)$this->user]);
        $linkHashes = [];

        foreach ($userMetas as $meta) {
            $linkHashes[] = $meta->hash;
        }

        $allLinks = R::findAll('link');
        $links = [];

        foreach ($allLinks as $key => $link) {
            if (\in_array($link->hash, $linkHashes, true) && \count($link->sharedAttachment) > 0) {
                $linkDao = LinkDAO::constructFromDB($link);
                $links[] = $linkDao->toArray();
            }
        }

        return $links;
    }
}
