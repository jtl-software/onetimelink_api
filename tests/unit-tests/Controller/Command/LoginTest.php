<?php
/**
 * This File is part of JTL-Software
 *
 * User: rherrgesell
 * Date: 14.08.17
 */

namespace JTL\Onetimelink\Controller\Command;

use JTL\Onetimelink\Authentication\AuthenticationInterface;
use JTL\Onetimelink\Exception\AuthenticationException;
use JTL\Onetimelink\Request;
use JTL\Onetimelink\Session\Session;
use JTL\Onetimelink\Storage\UserStorage;
use JTL\Onetimelink\User;
use JTL\Onetimelink\UserList;
use JTL\Onetimelink\View\JsonView;
use Monolog\Logger;
use PHPUnit\Framework\TestCase;

/**
 * @covers \JTL\Onetimelink\Controller\Command\Login
 *
 * @uses   \JTL\Onetimelink\UserList
 * @uses   \JTL\Onetimelink\Header
 * @uses   \JTL\Onetimelink\Response
 * @uses   \JTL\Onetimelink\PasswordHash
 */
class LoginTest extends TestCase
{

    public function testUserCanLogin()
    {
        $userMock = $this->createMock(User::class);
        $userMock->expects($this->once())
            ->method('isAuthenticated')
            ->willReturn(true);

        $userMock->expects($this->once())
            ->method('isActive')
            ->willReturn(true);

        $userMock->expects($this->once())
            ->method('getEmail')
            ->willReturn('foo@bar.de');

        /** @var AuthenticationInterface|\PHPUnit_Framework_MockObject_MockObject $authMock */
        $authMock = $this->createMock(AuthenticationInterface::class);#
        $authMock->expects($this->once())
            ->method('authenticate')
            ->willReturn($userMock);

        $authMock->expects($this->once())
            ->method('generateAuthToken');

        /** @var Session|\PHPUnit_Framework_MockObject_MockObject $sessionMock */
        $sessionMock = $this->createMock(Session::class);
        $sessionMock->expects($this->once())
            ->method('getSessionId');

        $requestMock = $this->createMock(Request::class);
        $storageMock = $this->createMock(UserStorage::class);

        $userList = new UserList(['foo' => 'bar']);

        $login = new Login(
            $authMock,
            $sessionMock,
            $userList,
            $storageMock,
            $requestMock,
            $this->createMock(JsonView::class),
            $this->createMock(Logger::class)
        );
        $response = $login->execute();

        $this->assertTrue($response->isSuccessful());
    }

    public function testUserFailToLogin()
    {
        $userMock = $this->createMock(User::class);
        $userMock->expects($this->once())
            ->method('isAuthenticated')
            ->willReturn(false);

        /** @var AuthenticationInterface|\PHPUnit_Framework_MockObject_MockObject $authMock */
        $authMock = $this->createMock(AuthenticationInterface::class);#
        $authMock->expects($this->once())
            ->method('authenticate')
            ->willReturn($userMock);

        $authMock->expects($this->never())
            ->method('generateAuthToken');

        $requestMock = $this->createMock(Request::class);
        $storageMock = $this->createMock(UserStorage::class);

        $userList = new UserList(['foo' => 'bar']);

        $login = new Login(
            $authMock,
            $this->createMock(Session::class),
            $userList,
            $storageMock,
            $requestMock,
            $this->createMock(JsonView::class),
            $this->createMock(Logger::class)
        );

        $this->expectException(AuthenticationException::class);
        $login->execute();

    }

    public function testThrowsExceptionIfLoginUserIsInactive()
    {
        $userMock = $this->createMock(User::class);
        $userMock->expects($this->once())
                 ->method('isAuthenticated')
                 ->willReturn(true);

        $userMock->expects($this->once())
                 ->method('isActive')
                 ->willReturn(false);

        /** @var AuthenticationInterface|\PHPUnit_Framework_MockObject_MockObject $authMock */
        $authMock = $this->createMock(AuthenticationInterface::class);#
        $authMock->expects($this->once())
                 ->method('authenticate')
                 ->willReturn($userMock);

        /** @var Session|\PHPUnit_Framework_MockObject_MockObject $sessionMock */
        $sessionMock = $this->createMock(Session::class);
        $requestMock = $this->createMock(Request::class);
        $storageMock = $this->createMock(UserStorage::class);
        $loggerMock = $this->createMock(Logger::class);

        $userList = new UserList(['foo' => 'bar']);

        $login = new Login(
            $authMock,
            $sessionMock,
            $userList,
            $storageMock,
            $requestMock,
            $this->createMock(JsonView::class),
            $loggerMock
        );

        $this->expectException(AuthenticationException::class);
        $login->execute();
    }

    public function testUserCanLoginWithUsername()
    {
        $userListObj = new UserList(['foo' => [
            'email' => 'foo@bar.example'
        ]]);

        $userListArr = ['user' => [
            'foo' => [
                'email' => 'foo@bar.example'
            ]]
        ];

        $userMock = $this->createMock(User::class);
        $userMock->expects($this->once())
                 ->method('isAuthenticated')
                 ->willReturn(true);

        $userMock->expects($this->once())
                 ->method('isActive')
                 ->willReturn(true);

        $userMock->expects($this->once())
                 ->method('getEmail')
                 ->willReturn('foo');

        /** @var AuthenticationInterface|\PHPUnit_Framework_MockObject_MockObject $authMock */
        $authMock = $this->createMock(AuthenticationInterface::class);#
        $authMock->expects($this->once())
                 ->method('authenticate')
                 ->willReturn($userMock);

        /** @var Session|\PHPUnit_Framework_MockObject_MockObject $sessionMock */
        $sessionMock = $this->createMock(Session::class);
        $requestMock = $this->createMock(Request::class);
        $requestMock->expects($this->at(0))
                    ->method('readServer')
                    ->with('PHP_AUTH_USER')
                    ->willReturn('foo');

        $requestMock->expects($this->at(1))
                    ->method('readServer')
                    ->with('PHP_AUTH_PW')
                    ->willReturn('easypassword');

        $storageMock = $this->createMock(UserStorage::class);
        $storageMock->expects($this->once())->method('write')->willReturn(true);
        $storageMock->expects($this->once())
                    ->method('read')
                    ->willReturn($userListArr);

        $loggerMock = $this->createMock(Logger::class);


        $login = new Login(
            $authMock,
            $sessionMock,
            $userListObj,
            $storageMock,
            $requestMock,
            $this->createMock(JsonView::class),
            $loggerMock
        );
        $response = $login->execute();

        $this->assertTrue($response->isSuccessful());
    }

    public function testThrowsExceptionOnLoginWithUsernameIfUsernameDoesntExist()
    {
        $userListObj = new UserList(['foo' => [
            'email' => 'foo@bar.example'
        ]]);

        $userMock = $this->createMock(User::class);
        $userMock->expects($this->once())
                 ->method('isAuthenticated')
                 ->willReturn(true);

        $userMock->expects($this->once())
                 ->method('isActive')
                 ->willReturn(true);

        $userMock->expects($this->once())
                 ->method('getEmail')
                 ->willReturn('foo');

        /** @var AuthenticationInterface|\PHPUnit_Framework_MockObject_MockObject $authMock */
        $authMock = $this->createMock(AuthenticationInterface::class);#
        $authMock->expects($this->once())
                 ->method('authenticate')
                 ->willReturn($userMock);

        /** @var Session|\PHPUnit_Framework_MockObject_MockObject $sessionMock */
        $sessionMock = $this->createMock(Session::class);
        $requestMock = $this->createMock(Request::class);
        $requestMock->expects($this->at(0))
                    ->method('readServer')
                    ->with('PHP_AUTH_USER')
                    ->willReturn('bar');

        $requestMock->expects($this->at(1))
                    ->method('readServer')
                    ->with('PHP_AUTH_PW')
                    ->willReturn('easypassword');

        $storageMock = $this->createMock(UserStorage::class);
        $loggerMock = $this->createMock(Logger::class);

        $login = new Login(
            $authMock,
            $sessionMock,
            $userListObj,
            $storageMock,
            $requestMock,
            $this->createMock(JsonView::class),
            $loggerMock
        );

        $this->expectException(AuthenticationException::class);
        $response = $login->execute();
    }


}
