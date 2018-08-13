<?php
/**
 * This File is part of JTL-Software
 *
 * User: rherrgesell
 * Date: 23.08.17
 */

namespace JTL\Onetimelink\Session;

use JTL\Onetimelink\User;
use PHPUnit\Framework\TestCase;

/**
 * @covers \JTL\Onetimelink\Session\Session
 *
 * @uses \JTL\Onetimelink\PasswordHash
 * @uses \JTL\Onetimelink\User
 */
class SessionTest extends TestCase
{

    public function testCanStartNewSession()
    {
        $handlerMock = $this->createMock(PhpSessionHandler::class);
        $handlerMock->expects($this->once())
            ->method('sessionStart');

        $_SESSION = [];

        $this->assertInstanceOf(Session::class, Session::start(null, $handlerMock));
    }

    public function testCanResumeSession()
    {
        $sessionId = uniqid();
        $handlerMock = $this->createMock(PhpSessionHandler::class);
        $handlerMock->expects($this->once())
            ->method('setIdAndStartSession');

        $handlerMock->expects($this->once())
            ->method('getSessionId')
            ->willReturn($sessionId);


        $session = Session::start($sessionId, $handlerMock);
        $this->assertInstanceOf(Session::class, $session);
        $this->assertEquals($sessionId, $session->getSessionId());
    }

    public function testCanReadFromSession()
    {
        $handlerMock = $this->createMock(PhpSessionHandler::class);
        $handlerMock->expects($this->once())
            ->method('getSessionData')
            ->willReturn(['foo' => 'bar']);

        $session = Session::start(null, $handlerMock);
        $this->assertEquals('bar', $session->get('foo'));
    }

    public function testCanWriteToSession()
    {
        $handlerMock = $this->createMock(PhpSessionHandler::class);
        $handlerMock->expects($this->once())
            ->method('getSessionData')
            ->willReturn([]);

        $session = Session::start(null, $handlerMock);
        $this->assertInstanceOf(Session::class, $session->set('foo', 'bar'));
        $this->assertEquals('bar', $session->get('foo'));
    }

    public function testCanWriteAndReadUser()
    {
        $handlerMock = $this->createMock(PhpSessionHandler::class);
        $handlerMock->expects($this->once())
            ->method('getSessionData')
            ->willReturn([]);

        $user = User::createAnonymousUser();
        $session = Session::start(null, $handlerMock);
        $this->assertInstanceOf(Session::class, $session->setAuthToken('token', $user));
        $this->assertEquals($user, $session->getAuthenticatedUser('token'));
    }
}
