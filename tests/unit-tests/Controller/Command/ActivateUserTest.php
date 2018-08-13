<?php
/**
 * This File is part of JTL-Software
 *
 * User: rherrgesell
 * Date: 7/6/18
 */

namespace JTL\Onetimelink\Controller\Command;


use JTL\Onetimelink\Config;
use JTL\Onetimelink\Response;
use JTL\Onetimelink\Storage\UserStorage;
use JTL\Onetimelink\View\ViewInterface;
use Monolog\Logger;
use PHPUnit\Framework\TestCase;

/**
 * @covers \JTL\Onetimelink\Controller\Command\ActivateUser
 *
 * @uses \JTL\Onetimelink\Controller\AbstractObservable
 * @uses \JTL\Onetimelink\Header
 * @uses \JTL\Onetimelink\Response
 */
class ActivateUserTest extends TestCase
{

    public function testCanActivateUser()
    {
        $hash = "f00derBAr";

        $userStorageMock = $this->createMock(UserStorage::class);
        $userStorageMock->expects($this->once())
            ->method("read")
            ->willReturn([
               'user' => [
                   "foo@bar.de" => [
                       "activation" => "unknown",
                       "active" => false
                   ],
                   "bar@foo.de" => [
                       "activation" => $hash,
                       "active" => false
                   ]
               ]
            ]);

        $userStorageMock->expects($this->once())
            ->method("write")
            ->with([
                'user' => [
                    "foo@bar.de" => [
                        "activation" => "unknown",
                        "active" => false
                    ],
                    "bar@foo.de" => [
                        "activation" => null,
                        "active" => true
                    ]
                ]
            ])
            ->willReturn(true);

        $configMock = $this->createMock(Config::class);
        $viewMock = $this->createMock(ViewInterface::class);

        $cmd = new ActivateUser(
            $hash,
            $userStorageMock,
            $configMock,
            $viewMock,
            $this->createMock(Logger::class)
        );

        $response = $cmd->execute();

        $this->assertInstanceOf(Response::class, $response);
        $this->assertTrue($response->isSuccessful());
    }

    public function testFailWhenUserCanNotBeActivated() {
        $hash = "f00derBAr";

        $userStorageMock = $this->createMock(UserStorage::class);
        $userStorageMock->expects($this->once())
            ->method("read")
            ->willReturn([
                'user' => [
                    "foo@bar.de" => [
                        "activation" => "unknown",
                        "active" => false
                    ],
                    "bar@foo.de" => [
                        "activation" => $hash,
                        "active" => false
                    ]
                ]
            ]);

        $userStorageMock->expects($this->once())
            ->method("write")
            ->willReturn(false);

        $configMock = $this->createMock(Config::class);
        $viewMock = $this->createMock(ViewInterface::class);

        $cmd = new ActivateUser(
            $hash,
            $userStorageMock,
            $configMock,
            $viewMock,
            $this->createMock(Logger::class)
        );

        $this->expectException(\RuntimeException::class);
        $cmd->execute();
    }

    public function testFailWhenUserActivationHashIsEmpty() {

        $hash = "";

        $userStorageMock = $this->createMock(UserStorage::class);
        $configMock = $this->createMock(Config::class);
        $viewMock = $this->createMock(ViewInterface::class);

        $this->expectException(\InvalidArgumentException::class);
        new ActivateUser(
            $hash,
            $userStorageMock,
            $configMock,
            $viewMock,
            $this->createMock(Logger::class)
        );
    }
}
