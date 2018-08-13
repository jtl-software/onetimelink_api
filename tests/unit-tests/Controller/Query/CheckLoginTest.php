<?php
/**
 * This File is part of JTL-Software
 *
 * User: rherrgesell
 * Date: 23.08.17
 */

namespace JTL\Onetimelink\Controller\Query;

use JTL\Onetimelink\Factory;
use JTL\Onetimelink\User;
use JTL\Onetimelink\View\JsonView;
use PHPUnit\Framework\TestCase;

/**
 * @covers \JTL\Onetimelink\Controller\Query\CheckLogin
 *
 * @uses \JTL\Onetimelink\Header
 * @uses \JTL\Onetimelink\Response
 */
class CheckLoginTest extends TestCase
{

    public function testSessionIsAlive(): void
    {

        $userMock = $this->createMock(User::class);
        $userMock->expects($this->once())
            ->method('isAuthenticated')
            ->willReturn(true);

        $viewMock = $this->createMock(JsonView::class);
        $viewMock->expects($this->at(0))
            ->method('set')
            ->with('session', 'active');

        $viewMock->expects($this->at(1))
            ->method('set')
            ->with('links', null);

        $factoryMock = $this->createMock(Factory::class);
        $factoryMock->expects($this->once())
            ->method('createJsonView')
            ->willReturn($viewMock);

        $loginCheck = new CheckLogin($userMock, $factoryMock);
        $loginCheck->run();
    }


    public function testSessionInactive(): void
    {

        $userMock = $this->createMock(User::class);
        $userMock->expects($this->once())
            ->method('isAuthenticated')
            ->willReturn(false);

        $viewMock = $this->createMock(JsonView::class);
        $viewMock->expects($this->once())
            ->method('set')
            ->with('session', 'inactive');

        $factoryMock = $this->createMock(Factory::class);
        $factoryMock->expects($this->once())
            ->method('createJsonView')
            ->willReturn($viewMock);

        $loginCheck = new CheckLogin($userMock, $factoryMock);
        $loginCheck->run();
    }
}
