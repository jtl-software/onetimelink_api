<?php
/**
 * This File is part of JTL-Software
 *
 * User: rherrgesell
 * Date: 08.08.17
 */

namespace JTL\Onetimelink;

use JTL\Onetimelink\View\ViewInterface;

class OneTimeLink
{

    /**
     * @var string
     */
    private $hash;

    /**
     * @var User
     */
    private $user;

    /**
     * OneTimeLink constructor.
     *
     * @param string $hash
     * @param array $user
     * @internal param User $payloads
     * @internal param null|string $filename
     * @internal param string $contentType
     */
    public function __construct(
        string $hash,
        User $user
    ) {
        $this->hash = $hash;
        $this->user = $user;
    }

    /**
     * @return string
     */
    public function createLinkUri(): string
    {
        return '/read/' . $this->getHash();
    }

    /**
     * @param ViewInterface $view
     */
    public function appendDataToView(ViewInterface $view)
    {
        $view
            ->set('onetimelink', $this->createLinkUri())
            ->set('hash', $this->hash)
            ->set('user', (string)$this->getUser());
    }

    /**
     * @return string
     */
    public function getHash(): string
    {
        return $this->hash;
    }

    /**
     * @return User|null
     */
    public function getUser(): User
    {
        return $this->user;
    }

    /**
     * @return array
     */
    public function toArray(): array
    {
        return [
            'onetimelink' => $this->createLinkUri(),
            'hash' => $this->hash,
        ];
    }
}
