<?php
/**
 * This File is part of JTL-Software
 *
 * User: rherrgesell
 * Date: 04.08.17
 */

namespace JTL\Onetimelink\Authentication;

use JTL\Onetimelink\Exception\AuthenticationException;
use JTL\Onetimelink\Request;
use JTL\Onetimelink\Session\Session;
use JTL\Onetimelink\User;
use JTL\Onetimelink\UserList;
use PHPUnit\Framework\TestCase;

/**
 * @covers \JTL\Onetimelink\Authentication\BasicAuth
 *
 * @uses   \JTL\Onetimelink\User
 * @uses   \JTL\Onetimelink\PasswordHash
 */
class BasicAuthTest extends TestCase
{

    public function testUserCanBeAuthenticatedByHttpBasicAuth()
    {

        $sessionMock = $this->createMock(Session::class);
        $sessionMock->expects($this->once())
            ->method('getAuthenticatedUser')
            ->willReturn(null);

        /** @var Request|\PHPUnit_Framework_MockObject_MockObject $requestMock */
        $requestMock = $this->createMock(Request::class);
        $requestMock->expects($this->any())
            ->method('readServer')
            ->willReturn('foo');

        $requestMock->expects($this->once())
            ->method('getSession')
            ->willReturn($sessionMock);

        /** @var UserList|\PHPUnit_Framework_MockObject_MockObject $userListMock */
        $userListMock = $this->createMock(UserList::class);
        $userListMock->expects($this->once())
            ->method('getUser')
            ->willReturn(User::createUserFromString('foo'));

        $basicAuth = new BasicAuth($requestMock);
        $user = $basicAuth->authenticate($userListMock);

        $this->assertFalse($user->isAnonymous());
        $this->assertEquals('foo', (string)$user);
    }

    public function testUserIsAuthenticatedBySession()
    {

        $sessionMock = $this->createMock(Session::class);
        $sessionMock->expects($this->once())
            ->method('getAuthenticatedUser')
            ->willReturn(User::createUserFromString('foo'));

        /** @var Request|\PHPUnit_Framework_MockObject_MockObject $requestMock */
        $requestMock = $this->createMock(Request::class);
        $requestMock->expects($this->never())
            ->method('readServer');

        $requestMock->expects($this->once())
            ->method('getSession')
            ->willReturn($sessionMock);

        /** @var UserList|\PHPUnit_Framework_MockObject_MockObject $userListMock */
        $userListMock = $this->createMock(UserList::class);
        $userListMock->expects($this->never())
            ->method('getUser');

        $basicAuth = new BasicAuth($requestMock);
        $user = $basicAuth->authenticate($userListMock);

        $this->assertFalse($user->isAnonymous());
        $this->assertEquals('foo', (string)$user);
    }

    public function testAnonymousUserCanBeAuthenticated()
    {
        $sessionMock = $this->createMock(Session::class);
        $sessionMock->expects($this->once())
            ->method('getAuthenticatedUser')
            ->willReturn(null);

        /** @var Request|\PHPUnit_Framework_MockObject_MockObject $requestMock */
        $requestMock = $this->createMock(Request::class);
        $requestMock->expects($this->any())
            ->method('readServer')
            ->willReturn(null);

        $requestMock->expects($this->once())
            ->method('getSession')
            ->willReturn($sessionMock);

        /** @var UserList|\PHPUnit_Framework_MockObject_MockObject $userListMock */
        $userListMock = $this->createMock(UserList::class);
        $userListMock->expects($this->once())
            ->method('getUser')
            ->willReturn(User::createAnonymousUser());

        $basicAuth = new BasicAuth($requestMock);
        $user = $basicAuth->authenticate($userListMock);

        $this->assertTrue($user->isAnonymous());
    }

    public function testGenerateAuthenticationTokenForUser()
    {
        $sessionMock = $this->createMock(Session::class);
        $sessionMock->expects($this->once())
            ->method('setAuthToken');

        /** @var Request|\PHPUnit_Framework_MockObject_MockObject $requestMock */
        $requestMock = $this->createMock(Request::class);
        $requestMock->expects($this->once())
            ->method('getSession')
            ->willReturn($sessionMock);

        /** @var User|\PHPUnit_Framework_MockObject_MockObject $userMock */
        $userMock = $this->createMock(User::class);
        $userMock->expects($this->once())
            ->method('isAuthenticated')
            ->willReturn(true);


        $basicAuth = new BasicAuth($requestMock);
        $token = $basicAuth->generateAuthToken($userMock);

        $this->assertStringStartsWith('at', $token);
        $this->assertTrue(strlen($token) > strlen(BasicAuth::TOKEN_PREFIX));
    }

    public function testGenerateAuthenticationTokenFail()
    {
        $sessionMock = $this->createMock(Session::class);
        $sessionMock->expects($this->never())
            ->method('setAuthToken');

        /** @var Request|\PHPUnit_Framework_MockObject_MockObject $requestMock */
        $requestMock = $this->createMock(Request::class);
        $requestMock->expects($this->never())
            ->method('getSession')
            ->willReturn($sessionMock);

        /** @var User|\PHPUnit_Framework_MockObject_MockObject $userMock */
        $userMock = $this->createMock(User::class);
        $userMock->expects($this->once())
            ->method('isAuthenticated')
            ->willReturn(false);


        $basicAuth = new BasicAuth($requestMock);

        $this->expectException(AuthenticationException::class);
        $basicAuth->generateAuthToken($userMock);
    }
}
