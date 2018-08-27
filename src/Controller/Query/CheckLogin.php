<?php
/**
 * This File is part of JTL-Software
 *
 * User: rherrgesell
 * Date: 16.08.17
 */

namespace JTL\Onetimelink\Controller\Query;

use JTL\Onetimelink\Controller\QueryInterface;
use JTL\Onetimelink\Factory;
use JTL\Onetimelink\Response;
use JTL\Onetimelink\User;
use JTL\Onetimelink\View\JsonView;
use Monolog\Logger;

class CheckLogin implements QueryInterface
{
    /**
     * @var User
     */
    private $user;

    /**
     * @var JsonView
     */
    private $view;

    /**
     * @var \JTL\Onetimelink\Storage\UserMetaStorage
     */
    private $userMataStorage;

    /**
     * @var Logger
     */
    private $logger;

    /**
     * CheckLogin constructor.
     *
     * @param User $user
     * @param Factory $factory
     */
    public function __construct(User $user, Factory $factory)
    {
        $this->user = $user;
        $this->view = $factory->createJsonView();
        $this->userMataStorage = $factory->createUserMetaStorage($this->user);
        $this->logger = $factory->createLogger();
    }

    public function run(): Response
    {
        if ($this->user->isAuthenticated()) {
            $this->view->set('session', 'active');
            $this->view->set('links', $this->userMataStorage->getLinks());
            $this->logger->debug('Session activated for user ' . $this->user->getEmail());
        } else {
            $this->view->set('session', 'inactive');
            $this->logger->info('Session deactivated');
        }
        return Response::createSuccessful($this->view);
    }
}