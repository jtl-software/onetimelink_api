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
use JTL\Onetimelink\View\ViewInterface;
use Monolog\Logger;
use PHPUnit\Framework\TestCase;

/**
 * Class PasswordResetActionTest
 * @package JTL\Onetimelink\Controller\Command
 * @covers \JTL\Onetimelink\Controller\Command\PasswordResetAction
 *
 * @uses \JTL\Onetimelink\Controller\AbstractObservable
 * @uses \JTL\Onetimelink\Header
 * @uses \JTL\Onetimelink\PasswordHash
 * @uses \JTL\Onetimelink\Response
 * @uses \JTL\Onetimelink\User
 * @uses \JTL\Onetimelink\View\PlainView
 */
class PasswordResetActionTest extends TestCase
{

    public function testCanCreatePasswordResetHash()
    {
        $email = uniqid('email', true);
        $password = uniqid('password', true);
        $userList = ['user' => [
            $email => [
                'password' => 'foobar'
            ]
        ]];

        $factoryMock = $this->createMock(Factory::class);

        $userStorageMock = $this->createMock(UserStorage::class);
        $userStorageMock->expects($this->once())->method('read')->willReturn($userList);
        $userStorageMock->expects($this->once())->method('write')->willReturn(true);

        $viewMock = $this->createMock(ViewInterface::class);
        $viewMock->expects($this->once())->method('set')->with('success', true);

        $resetRequest = new PasswordResetAction(
            $email,
            $password,
            $factoryMock,
            $userStorageMock,
            $viewMock,
            $this->createMock(Logger::class)
        );

        $response = $resetRequest->execute();
        $this->assertInstanceOf(Response::class, $response);
    }
}
