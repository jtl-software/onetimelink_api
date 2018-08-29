<?php
/**
 * This File is part of JTL-Software
 *
 * User: rherrgesell
 * Date: 22.08.17
 */

namespace JTL\Onetimelink\Notification;

use JTL\Onetimelink\Notification\Message\AbstractMessage;
use PHPMailer\PHPMailer\PHPMailer;

class Mail implements NotifierInterface
{

    /**
     * @var PHPMailer
     */
    private $phpMailer;

    /**
     * Mail constructor.
     * @param PHPMailer $PHPMailer
     * @param string $from
     * @throws \PHPMailer\PHPMailer\Exception
     */
    public function __construct(PHPMailer $PHPMailer, string $from)
    {
        $this->phpMailer = $PHPMailer;
        $this->phpMailer->setFrom($from);
    }

    /**
     * @param AbstractMessage $message
     * @throws \PHPMailer\PHPMailer\Exception
     */
    public function send(AbstractMessage $message)
    {
        $this->phpMailer->addAddress($message->getRecipient());
        $this->phpMailer->Subject = $message->getSubject();
        $this->phpMailer->Body = $message->getMessage();
        $this->phpMailer->send();
    }
}
