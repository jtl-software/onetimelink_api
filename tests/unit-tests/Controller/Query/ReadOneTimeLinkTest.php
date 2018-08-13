<?php
/**
 * This File is part of JTL-Software
 *
 * User: rherrgesell
 * Date: 04.08.17
 */

namespace JTL\Onetimelink\Controller\Query;

use JTL\Onetimelink\Config;
use JTL\Onetimelink\Factory;
use JTL\Onetimelink\Request;
use JTL\Onetimelink\Response;
use JTL\Onetimelink\Storage\DatabaseStorage;
use JTL\Onetimelink\User;
use PHPUnit\Framework\TestCase;
use RedBeanPHP\OODBBean;

/**
 * Class OneTimeLinkTest
 * @covers \JTL\Onetimelink\Controller\Query\ReadOneTimeLink
 *
 * @uses   \JTL\Onetimelink\Controller\AbstractObservable
 * @uses   \JTL\Onetimelink\Header
 * @uses   \JTL\Onetimelink\Response
 * @uses   \JTL\Onetimelink\User
 * @uses   \JTL\Onetimelink\View\PlainView
 * @uses   \JTL\Onetimelink\Payload
 * @uses   \JTL\Onetimelink\Storage\MetaData
 * @uses   \JTL\Onetimelink\PasswordHash
 * @uses   \JTL\Onetimelink\DAO\LinkDAO
 */
class ReadOneTimeLinkTest extends TestCase
{

    public function testCanBeRead()
    {
        $attachmentHash = uniqid('attachment', true);
        $linkHash = uniqid('link', true);
        $linkDummy = new \stdClass();
        $linkDummy->tags = '';

        $textAttachmentMock = $this->createMock(OODBBean::class);
        $textAttachmentMock->expects($this->at(0))
            ->method('__get')
            ->with('name')
            ->willReturn('-#-TEXTINPUT-#-');

        $attachmentMock = $this->createMock(OODBBean::class);
        $attachmentMock->expects($this->at(0))
            ->method('__get')
            ->with('name')
            ->willReturn('dummy');

        $attachmentMock->expects($this->at(1))
            ->method('__get')
            ->with('hash')
            ->willReturn('dummy');

        $attachmentMock->expects($this->at(2))
            ->method('__get')
            ->with('hash')
            ->willReturn($attachmentHash);

        $attachmentMock->expects($this->at(3))
            ->method('__get')
            ->with('hash')
            ->willReturn($attachmentHash);

        $attachmentMock->expects($this->at(4))
            ->method('__get')
            ->with('name')
            ->willReturn($attachmentHash);

        $linkMock = $this->createMock(OODBBean::class);
        $linkMock->expects($this->at(0))
            ->method('__get')
            ->with('user')
            ->willReturn('tester');

        $linkMock->expects($this->at(5))
            ->method('__get')
            ->with('sharedAttachmentList')
            ->willReturn([$textAttachmentMock, $attachmentMock]);

        /** @var DatabaseStorage|\PHPUnit_Framework_MockObject_MockObject $storageMock */
        $storageMock = $this->createMock(DatabaseStorage::class);
        $storageMock->expects($this->once())
            ->method('readLinkAsBean')
            ->willReturn($linkMock);

        /** @var Request $requestMock */
        $requestMock = $this->createMock(Request::class);

        $configMock = $this->createMock(Config::class);

        /** @var Factory|\PHPUnit_Framework_MockObject_MockObject $factoryMock */
        $factoryMock = $this->createMock(Factory::class);
        $factoryMock->expects($this->exactly(3))
            ->method('getConfig')
            ->willReturn($configMock);

        $testUser = User::createUserFromString('ronny');

        $otlRead = new ReadOneTimeLink($storageMock, $linkHash, $testUser, $requestMock, $factoryMock, false);

        $response = $otlRead->run();
        $this->assertInstanceOf(Response::class, $response);
    }
}
