<?php
/**
 * This File is part of JTL-Software
 *
 * User: rherrgesell
 * Date: 04.08.17
 */

namespace JTL\Onetimelink;

use PHPUnit\Framework\TestCase;

/**
 * @covers \JTL\Onetimelink\Header
 * @uses   \JTL\Onetimelink\HeaderSend
 */
class HeaderTest extends TestCase
{

    public function testCanAddInformationToHeader()
    {
        $headerSendMock = $this->createMock(HeaderSend::class);
        $headerSendMock->expects($this->once())
            ->method('send')
            ->with('foo', 'bar');

        $header = new Header($headerSendMock);
        $this->assertInstanceOf(
            Header::class,
            $header->set('foo', 'bar')
        );

        $header->send();
    }

    public function testCanSetLocationHeader()
    {
        $header = new Header();
        $this->assertInstanceOf(Header::class, $header->setLocation('foo'));
    }
}
