<?php
/**
 * This File is part of JTL-Software
 *
 * User: mbrandt
 * Date: 22/05/18
 */

namespace JTL\Onetimelink\Controller\Query;


use JTL\Onetimelink\Controller\QueryInterface;
use JTL\Onetimelink\Factory;
use JTL\Onetimelink\Response;
use JTL\Onetimelink\View\ViewInterface;

class CheckPasswordResetHash implements QueryInterface
{

    /**
     * @var Factory
     */
    private $factory;

    /**
     * @var string
     */
    private $hash;

    /**
     * @var ViewInterface
     */
    private $view;

    public function __construct(Factory $factory, string $hash, ViewInterface $view)
    {
        $this->factory = $factory;
        $this->hash = $hash;
        $this->view = $view;
    }

    public function run(): Response
    {
        $userList = json_decode(file_get_contents($this->factory->getConfig()->getUserListPath()), true);
        $this->view->set('valid', false);

        foreach ($userList['user'] as $email => $user) {
            if (isset($user['reset_hash'], $user['reset_hash_created'])) {
                if ($user['reset_hash'] === $this->hash) {
                    $this->view->set('valid', true);
                    $this->view->set('email', $email);
                }
            }
        }

        return Response::createSuccessful($this->view);
    }
}