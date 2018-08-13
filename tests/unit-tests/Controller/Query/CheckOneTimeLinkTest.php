<?php
/**
 * This File is part of JTL-Software
 *
 * User: rherrgesell
 * Date: 23.08.17
 */

namespace JTL\Onetimelink\Controller\Query;

use JTL\Onetimelink\Response;
use JTL\Onetimelink\Storage\DatabaseStorage;
use JTL\Onetimelink\View\JsonView;
use Monolog\Logger;
use PHPUnit\Framework\TestCase;
use RedBeanPHP\OODBBean;

/**
 * @covers \JTL\Onetimelink\Controller\Query\CheckOneTimeLink
 *
 * @uses \JTL\Onetimelink\Header
 * @uses \JTL\Onetimelink\PasswordHash
 * @uses \JTL\Onetimelink\Response
 * @uses \JTL\Onetimelink\Storage\MetaData
 * @uses \JTL\Onetimelink\User
 */
class CheckOneTimeLinkTest extends TestCase
{

    public function testOTLIsActive()
    {
        $beanMock = $this->createMock(OODBBean::class);
        $beanMock->expects($this->at(3))
            ->method('__get')
            ->with('sharedAttachmentList')
            ->willReturn([]);

        /** @var DatabaseStorage|\PHPUnit_Framework_MockObject_MockObject $storageMock */
        $storageMock = $this->createMock(DatabaseStorage::class);
        $storageMock->expects($this->once())
            ->method('readLinkAsBean')
            ->willReturn($beanMock);

        /** @var JsonView|\PHPUnit_Framework_MockObject_MockObject $viewMock */
        $viewMock = $this->createMock(JsonView::class);
        $viewMock->expects($this->at(0))
            ->method('set')
            ->with('alive', true);

        $check = new CheckOneTimeLink($storageMock, 'hash', $viewMock, $this->createMock(Logger::class));
        $response = $check->run();

        $this->assertInstanceOf(Response::class, $response);
    }

    public function testOTLInactive()
    {
        /** @var DatabaseStorage|\PHPUnit_Framework_MockObject_MockObject $storageMock */
        $storageMock = $this->createMock(DatabaseStorage::class);
        $storageMock->expects($this->once())
            ->method('readLinkAsBean')
            ->willReturn(null);

        /** @var JsonView|\PHPUnit_Framework_MockObject_MockObject $viewMock */
        $viewMock = $this->createMock(JsonView::class);
        $viewMock->expects($this->at(0))
            ->method('set')
            ->with('alive', false);

        $check = new CheckOneTimeLink($storageMock, 'hash', $viewMock, $this->createMock(Logger::class));
        $response = $check->run();

        $this->assertInstanceOf(Response::class, $response);
    }

}
