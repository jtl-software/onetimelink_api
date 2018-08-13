<?php
/**
 * This File is part of JTL-Software
 *
 * User: rherrgesell
 * Date: 08.08.17
 */

namespace JTL\Onetimelink\Controller\Command;

use JTL\Onetimelink\Exception\DataNotFoundException;
use JTL\Onetimelink\Factory;
use JTL\Onetimelink\LinkHash;
use JTL\Onetimelink\OneTimeLink;
use JTL\Onetimelink\Response;
use JTL\Onetimelink\Storage\DatabaseStorage;
use JTL\Onetimelink\View\ViewInterface;
use PHPUnit\Framework\TestCase;
use RedBeanPHP\OODBBean;

/**
 * @covers  \JTL\Onetimelink\Controller\Command\UpdateGuestLink
 *
 * @uses    \JTL\Onetimelink\Controller\AbstractObservable
 * @uses    \JTL\Onetimelink\Header
 * @uses    \JTL\Onetimelink\LinkHash
 * @uses    \JTL\Onetimelink\Payload
 * @uses    \JTL\Onetimelink\Response
 * @uses    \JTL\Onetimelink\Storage\MetaData
 * @uses    \JTL\Onetimelink\User
 * @uses    \JTL\Onetimelink\View\PlainView
 * @uses    \JTL\Onetimelink\PasswordHash
 */
class UpdateGuestLinkTest extends TestCase
{

    public function testCanUpdateGuestLink(): void
    {
        /** @var ViewInterface|\PHPUnit_Framework_MockObject_MockObject $viewMock */
        $viewMock = $this->createMock(ViewInterface::class);

        /** @var CreateLink|\PHPUnit_Framework_MockObject_MockObject $createLinkMock */
        $createLinkMock = $this->createMock(CreateLink::class);
        $createLinkMock->expects($this->once())
            ->method('execute')
            ->willReturn(Response::createSuccessfulCreated($viewMock));

        $createLinkMock->expects($this->once())
            ->method('getOneTimeLink')
            ->willReturn($this->createMock(OneTimeLink::class));

        $beanMock = $this->createMock(OODBBean::class);
        $beanMock->expects($this->at(0))
            ->method('__get')
            ->with('is_guest_link')
            ->willReturn(true);

        $beanMock->expects($this->at(1))
            ->method('__get')
            ->with('user')
            ->willReturn('tester');

        /** @var DatabaseStorage|\PHPUnit_Framework_MockObject_MockObject $storageMock */
        $storageMock = $this->createMock(DatabaseStorage::class);
        $storageMock->expects($this->once())
            ->method('readLinkAsBean')
            ->willReturn($beanMock);

        $storageMock->expects($this->once())
            ->method('deleteLink');

        /** @var Factory|\PHPUnit_Framework_MockObject_MockObject $factoryMock */
        $factoryMock = $this->createMock(Factory::class);

        $update = new UpdateGuestLink($createLinkMock, $storageMock, LinkHash::createUnique(), $factoryMock);
        $update->execute();
    }

    public function testThrowExceptionWhenGuestLinkNotExists()
    {
        /** @var CreateLink|\PHPUnit_Framework_MockObject_MockObject $createLinkMock */
        $createLinkMock = $this->createMock(CreateLink::class);

        /** @var DatabaseStorage|\PHPUnit_Framework_MockObject_MockObject $storageMock */
        $storageMock = $this->createMock(DatabaseStorage::class);
        $storageMock->expects($this->once())
            ->method('readLinkAsBean')
            ->willReturn(null);

        /** @var Factory|\PHPUnit_Framework_MockObject_MockObject $factoryMock */
        $factoryMock = $this->createMock(Factory::class);

        $update = new UpdateGuestLink($createLinkMock, $storageMock, LinkHash::createUnique(), $factoryMock);

        $this->expectException(DataNotFoundException::class);
        $update->execute();
    }

    public function testThrowExceptionWhenFailToCreateGuestLink()
    {
        /** @var CreateLink|\PHPUnit_Framework_MockObject_MockObject $createLinkMock */
        $createLinkMock = $this->createMock(CreateLink::class);
        $createLinkMock->expects($this->once())
            ->method('execute')
            ->willReturn(Response::createNotFound());

        $beanMock = $this->createMock(OODBBean::class);
        $beanMock->expects($this->at(0))
            ->method('__get')
            ->with('is_guest_link')
            ->willReturn(true);

        $beanMock->expects($this->at(1))
            ->method('__get')
            ->with('user')
            ->willReturn('tester');

        /** @var DatabaseStorage|\PHPUnit_Framework_MockObject_MockObject $storageMock */
        $storageMock = $this->createMock(DatabaseStorage::class);
        $storageMock->expects($this->once())
            ->method('readLinkAsBean')
            ->willReturn($beanMock);

        /** @var Factory|\PHPUnit_Framework_MockObject_MockObject $factoryMock */
        $factoryMock = $this->createMock(Factory::class);

        $update = new UpdateGuestLink($createLinkMock, $storageMock, LinkHash::createUnique(), $factoryMock);

        $this->expectException(\RuntimeException::class);
        $update->execute();
    }
}
