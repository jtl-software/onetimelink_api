<?php
/**
 * This File is part of JTL-Software
 *
 * User: rherrgesell
 * Date: 08.08.17
 */

namespace JTL\Onetimelink\Controller\Command;

use JTL\Onetimelink\Factory;
use JTL\Onetimelink\Request;
use JTL\Onetimelink\Storage\DatabaseStorage;
use JTL\Onetimelink\User;
use JTL\Onetimelink\View\JsonView;
use JTL\Onetimelink\View\ViewInterface;
use PHPUnit\Framework\TestCase;

/**
 * @covers \JTL\Onetimelink\Controller\Command\CreateGuestLink
 *
 * @uses   \JTL\Onetimelink\Header
 * @uses   \JTL\Onetimelink\LinkHash
 * @uses   \JTL\Onetimelink\Payload
 * @uses   \JTL\Onetimelink\Response
 * @uses   \JTL\Onetimelink\Storage\MetaData
 * @uses   \JTL\Onetimelink\User
 * @uses   \JTL\Onetimelink\PasswordHash
 */
class CreateGuestLinkTest extends TestCase
{

    public function testCanSuccessfulCreateGuestLink(): void
    {
        /** @var DatabaseStorage|\PHPUnit_Framework_MockObject_MockObject $storageMock */
        $storageMock = $this->createMock(DatabaseStorage::class);
        $storageMock->expects($this->once())
            ->method('writeGuestLink')
            ->willReturn(true);

        $user = User::createUserFromString('tester');

        /** @var Request|\PHPUnit_Framework_MockObject_MockObject $requestMock */
        $requestMock = $this->createMock(Request::class);
        $requestMock->expects($this->at(0))
            ->method('readInputAsJson')
            ->willReturn(['amount' => 1]);


        /** @var ViewInterface|\PHPUnit_Framework_MockObject_MockObject $view */
        $view = $this->createMock(JsonView::class);
        $view->expects($this->at(0))
            ->method('set')
            ->with('links');


        /** @var Factory|\PHPUnit_Framework_MockObject_MockObject $factoryMock */
        $factoryMock = $this->createMock(Factory::class);
        $factoryMock->expects($this->once())
            ->method('createJsonView')
            ->willReturn($view);

        $createGuestLink = new CreateGuestLink($storageMock, $user, $requestMock, $factoryMock);
        $createGuestLink->execute();
    }
}
