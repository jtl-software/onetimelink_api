<?php
/**
 * This File is part of JTL-Software
 *
 * User: rherrgesell
 * Date: 08.08.17
 */

namespace JTL\Onetimelink\Notification;


use JTL\Onetimelink\Notification\Message\AbstractMessage;

interface NotifierInterface
{
    public function send(AbstractMessage $message);
}