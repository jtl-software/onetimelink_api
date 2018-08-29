<?php
/**
 * This File is part of JTL-Software
 *
 * User: rherrgesell
 * Date: 22.08.17
 */

namespace JTL\Onetimelink\Controller;

use JTL\Onetimelink\Notification\Message\AbstractMessage;
use JTL\Onetimelink\Notification\NotifierInterface;
use JTL\Onetimelink\Notification\ObservableInterface;

abstract class AbstractObservable implements ObservableInterface
{

    /**
     * @var NotifierInterface
     */
    private $notifier;

    /**
     * AbstractObservableCommand constructor.
     * @param NotifierInterface $notifier
     */
    public function __construct(NotifierInterface $notifier)
    {
        $this->setNotifier($notifier);
    }

    /**
     * @param AbstractMessage $message
     */
    public function notify(AbstractMessage $message)
    {
        $this->notifier->send($message);
    }

    /**
     * @param NotifierInterface $notifier
     */
    public function setNotifier(NotifierInterface $notifier)
    {
        $this->notifier = $notifier;
    }
}
