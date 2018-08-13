<?php
/**
 * This File is part of JTL-Software
 *
 * User: rherrgesell
 * Date: 12.04.18
 */

namespace JTL\Onetimelink\Controller\Command;

use JTL\Onetimelink\Config;
use JTL\Onetimelink\Notification\Message\AbstractMessage;
use JTL\Onetimelink\Storage\UserStorage;
use JTL\Onetimelink\View\ViewInterface;
use Monolog\Logger;
use PHPUnit\Framework\TestCase;

/**
 * Class CreateUserTest
 * @covers \JTL\Onetimelink\Controller\Command\CreateUser
 *
 * @uses \JTL\Onetimelink\Header
 * @uses \JTL\Onetimelink\PasswordHash
 * @uses \JTL\Onetimelink\Response
 * @uses \JTL\Onetimelink\View\PlainView
 * @uses \JTL\Onetimelink\Controller\AbstractObservable
 * @uses \JTL\Onetimelink\View\JsonView
 */
class CreateUserTest extends TestCase
{

    public function testCanCreateNewUser()
    {
        $storageMock = $this->createMock(UserStorage::class);
        $storageMock->expects($this->once())->method("write")->willReturn(true);
        $storageMock->expects($this->once())
            ->method("read")
            ->willReturn(['user' => []]);

        $configMock = $this->createMock(Config::class);
        $configMock->expects($this->once())
            ->method('createMessageNewUser')
            ->willReturn($this->createMock(AbstractMessage::class));

        $createUser = new CreateUser(
            'foo@bar.de',
            'password-is-save',
            $storageMock,
            '/.*/',
            $configMock,
            $this->createMock(ViewInterface::class),
            $this->createMock(Logger::class)
        );
        $result = $createUser->execute();

        $this->assertTrue($result->isSuccessfulCreated());
    }

    public function testThrowExceptionWhenFailToCreateNewUser()
    {
        $storageMock = $this->createMock(UserStorage::class);
        $storageMock->expects($this->once())->method("write")->willReturn(false);
        $storageMock->expects($this->once())
            ->method("read")
            ->willReturn(['user' => []]);

        $configMock = $this->createMock(Config::class);
        $configMock->expects($this->never())
            ->method('createMessageNewUser');

        $createUser = new CreateUser(
            'foo@bar.de',
            'password-is-save',
            $storageMock,
            '/.*/',
            $configMock,
            $this->createMock(ViewInterface::class),
            $this->createMock(Logger::class)
        );

        $this->expectException(\RuntimeException::class);
        $createUser->execute();
    }

    public function testReturnErrorCodeIfUserAlreadyExists()
    {
        $storageMock = $this->createMock(UserStorage::class);
        $storageMock->expects($this->once())->method("read")->willReturn([
            'user' => ['foo@bar.de' => ['...']]
        ]);

        $configMock = $this->createMock(Config::class);

        $viewMock = $this->createMock(ViewInterface::class);
        $viewMock->expects($this->exactly(2))->method('set');

        $createUser = new CreateUser(
            'foo@bar.de',
            'password-is-save',
            $storageMock,
            '/.*/',
            $configMock,
            $viewMock,
            $this->createMock(Logger::class)
        );

        $createUser->execute();
    }

    public function testRequestIsForbiddenWhenMailIsNotWhitelisted()
    {

        $createUser = new CreateUser(
            'foo@bar.de',
            'password-is-save',
            $this->createMock(UserStorage::class),
            '/@example/',
            $this->createMock(Config::class),
            $this->createMock(ViewInterface::class),
            $this->createMock(Logger::class)
        );

        $result = $createUser->execute();

        $this->assertFalse($result->isSuccessful());
    }

    public function testFailWhenPasswordIsToShort()
    {
        $this->expectException(\InvalidArgumentException::class);
        new CreateUser(
            'foo@bar.de',
            '1',
            $this->createMock(UserStorage::class),
            '/@example/',
            $this->createMock(Config::class),
            $this->createMock(ViewInterface::class),
            $this->createMock(Logger::class)
        );
    }

    public function testFailWhenEmailIsInvalid()
    {
        $this->expectException(\InvalidArgumentException::class);
        new CreateUser(
            'invalid.mail',
            '123456789',
            $this->createMock(UserStorage::class),
            '/@example/',
            $this->createMock(Config::class),
            $this->createMock(ViewInterface::class),
            $this->createMock(Logger::class)
        );
    }
}
