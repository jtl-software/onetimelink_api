<?php
/**
 * This File is part of JTL-Software
 *
 * User: mbrandt
 * Date: 22/05/18
 */

namespace JTL\Onetimelink\Controller\Command;

use JTL\Onetimelink\Factory;
use JTL\Onetimelink\Response;
use JTL\Onetimelink\Storage\UserStorage;
use JTL\Onetimelink\User;
use JTL\Onetimelink\UserList;
use JTL\Onetimelink\View\ViewInterface;
use Monolog\Logger;
use PHPUnit\Framework\TestCase;

/**
 * Class PasswordResetRequestTest
 * @package JTL\Onetimelink\Controller\Command
 * @covers \JTL\Onetimelink\Controller\Command\PasswordResetRequest
 *
 * @uses \JTL\Onetimelink\Controller\AbstractObservable
 * @uses \JTL\Onetimelink\Header
 * @uses \JTL\Onetimelink\PasswordHash
 * @uses \JTL\Onetimelink\Response
 * @uses \JTL\Onetimelink\User
 * @uses \JTL\Onetimelink\View\PlainView
 */
class PasswordResetRequestTest extends TestCase
{

    public function testCanCreatePasswordResetHash()
    {
        $email = uniqid('email');
        $userList = ['user' => [
            $email => [
                'password' => 'foobar'
            ]
        ]];

        $factoryMock = $this->createMock(Factory::class);

        $userStorageMock = $this->createMock(UserStorage::class);
        $userStorageMock->expects($this->once())->method('read')->willReturn($userList);
        $userStorageMock->expects($this->once())->method('write')->willReturn(true);

        $userMock = $this->createMock(User::class);
        $userMock->expects($this->once())->method('equals')->willReturn(false);
        $userMock->expects($this->exactly(2))->method('getEmail')->willReturn($email);

        $userListMock = $this->createMock(UserList::class);
        $userListMock->expects($this->once())->method('getUser')->willReturn($userMock);

        $viewMock = $this->createMock(ViewInterface::class);
        $viewMock->expects($this->once())->method('set');

        $resetRequest = new PasswordResetRequest(
            $email,
            $factoryMock,
            $userStorageMock,
            $userListMock,
            $viewMock,
            $this->createMock(Logger::class)
        );

        $response = $resetRequest->execute();
        $this->assertInstanceOf(Response::class, $response);
    }
}
