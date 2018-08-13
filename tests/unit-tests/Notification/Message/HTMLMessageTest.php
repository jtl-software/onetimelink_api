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
 * @covers \JTL\Onetimelink\Notification\Message\HTMLMessage
 *
 * @uses   \JTL\Onetimelink\Notification\Message\AbstractMessage
 */
class HTMLMessageTest extends TestCase
{

    public function testCanGetMessage()
    {
        $data = new \stdClass();
        $data->needle = uniqid();
        $htmlMessage = new HTMLMessage(
            'foo@bar', 'test', __DIR__ . '/testMessageTemplate.php', $data
        );

        $this->assertContains($data->needle, $htmlMessage->getMessage());
    }

    public function testFailWhenTemplateNotExists()
    {
        $htmlMessage = new HTMLMessage(
            'foo@bar', 'test', '/irgendwasmiteinhörnern.php', new \stdClass()
        );

        $this->expectException(\RuntimeException::class);
        $htmlMessage->getMessage();
    }
}
