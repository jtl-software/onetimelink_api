<?php
/**
 * This File is part of JTL-Software
 *
 * User: rherrgesell
 * Date: 23.08.17
 */

namespace JTL\Onetimelink\Notification\Message;

use PHPUnit\Framework\TestCase;

/**
 * @covers \JTL\Onetimelink\Notification\Message\AbstractMessage
 */
class AbstractMessageTest extends TestCase
{

    /**
     * @var AbstractMessage
     */
    protected $message;

    protected $testRecipient = 'foo';
    protected $testSubject = 'subject';
    protected $testMessage = 'message';

    public function setUp(){
        $this->message = new MyMessage($this->testRecipient, $this->testSubject);
        $this->message->setMessage($this->testMessage);
    }

    public function testCanReceiveRecipient()
    {
        $this->assertEquals($this->testRecipient, $this->message->getRecipient());
    }

    public function testCanReceiveSubject()
    {
        $this->assertEquals($this->testSubject, $this->message->getSubject());
    }

    public function testCanReceiveMessage()
    {
        $this->assertEquals($this->testMessage, $this->message->getMessage());
    }
}

class MyMessage extends AbstractMessage
{
    /**
     * @param string $message
     */
    public function setMessage(string $message)
    {
        $this->message = $message;
    }
}