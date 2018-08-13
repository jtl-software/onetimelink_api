<?php
/**
 * This File is part of JTL-Software
 *
 * User: rherrgesell
 * Date: 23.08.17
 */

namespace JTL\Onetimelink\Notification;

use JTL\Onetimelink\Notification\Message\AbstractMessage;
use PHPMailer\PHPMailer\PHPMailer;
use PHPUnit\Framework\TestCase;

/**
 * @covers \JTL\Onetimelink\Notification\Mail
 */
class MailTest extends TestCase
{

    public function testCanSendMessage()
    {

        $phpMailerMock = $this->createMock(PHPMailer::class);
        $phpMailerMock
            ->expects($this->once())
            ->method('send');

        $mail = new Mail($phpMailerMock, 'foo@bar');
        $mail->send($this->createMock(AbstractMessage::class));
    }
}
