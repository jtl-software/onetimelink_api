<?php
/**
 * This File is part of JTL-Software
 *
 * User: mbrandt
 * Date: 13/08/18
 */

namespace JTL\Onetimelink\Controller\Command;

use JTL\Onetimelink\PasswordHash;
use JTL\Onetimelink\Storage\UserStorage;
use JTL\Onetimelink\View\ViewInterface;
use Monolog\Logger;
use PHPUnit\Framework\TestCase;

/**
 * Class UpdateUserTest
 * @package JTL\Onetimelink\Controller\Command
 *
 * @covers \JTL\Onetimelink\Controller\Command\UpdateUser
 *
 * @uses   \JTL\Onetimelink\Header
 * @uses   \JTL\Onetimelink\PasswordHash
 * @uses   \JTL\Onetimelink\Response
 */
class UpdateUserTest extends TestCase
{
    public function testCanUpdateUser()
    {
        $user = uniqid('user', true);
        $oldPassword = uniqid('oldpw', true);
        $newPassword = uniqid('newpw', true);
        $userList = ['user' => [
            $user => [
                'password' => PasswordHash::createHash($user, $oldPassword),
            ],
        ]];

        $userStorageMock = $this->createMock(UserStorage::class);
        $userStorageMock->expects($this->once())
                        ->method('read')
                        ->willReturn($userList);

        $userStorageMock->expects($this->once())
                        ->method('write')
                        ->willReturn(true);

        $viewMock = $this->createMock(ViewInterface::class);
        $loggerMock = $this->createMock(Logger::class);

        $updateUser = new UpdateUser($user,
            $oldPassword,
            $newPassword,
            $userStorageMock,
            $viewMock,
            $loggerMock
        );

        $updateUser->execute();
    }

    public function testThrowsExceptionWhenPasswordsAreIdentical()
    {
        $user = uniqid('user', true);
        $oldPassword = uniqid('oldpw', true);
        $newPassword = $oldPassword;

        $userStorageMock = $this->createMock(UserStorage::class);

        $viewMock = $this->createMock(ViewInterface::class);
        $loggerMock = $this->createMock(Logger::class);

        $this->expectException(\InvalidArgumentException::class);
        new UpdateUser($user,
            $oldPassword,
            $newPassword,
            $userStorageMock,
            $viewMock,
            $loggerMock
        );
    }

    public function testThrowsExceptionWhenOldPasswordDoesntMatch()
    {
        $user = uniqid('user', true);
        $oldPassword = uniqid('oldpw', true);
        $newPassword = uniqid('newpw', true);
        $userList = ['user' => [
            $user => [
                'password' => PasswordHash::createHash($user, uniqid('oldpw', true)),
            ],
        ]];

        $userStorageMock = $this->createMock(UserStorage::class);
        $userStorageMock->expects($this->once())
                        ->method('read')
                        ->willReturn($userList);

        $viewMock = $this->createMock(ViewInterface::class);
        $loggerMock = $this->createMock(Logger::class);

        $updateUser = new UpdateUser($user,
            $oldPassword,
            $newPassword,
            $userStorageMock,
            $viewMock,
            $loggerMock
        );

        $this->expectException(\RuntimeException::class);
        $updateUser->execute();
    }

    public function testThrowsExceptionWhenStorageNotWritable()
    {
        $user = uniqid('user', true);
        $oldPassword = uniqid('oldpw', true);
        $newPassword = uniqid('newpw', true);
        $userList = ['user' => [
            $user => [
                'password' => PasswordHash::createHash($user, $oldPassword),
            ],
        ]];

        $userStorageMock = $this->createMock(UserStorage::class);
        $userStorageMock->expects($this->once())
                        ->method('read')
                        ->willReturn($userList);

        $userStorageMock->expects($this->once())
                        ->method('write')
                        ->willReturn(false);

        $viewMock = $this->createMock(ViewInterface::class);
        $loggerMock = $this->createMock(Logger::class);

        $updateUser = new UpdateUser($user,
            $oldPassword,
            $newPassword,
            $userStorageMock,
            $viewMock,
            $loggerMock
        );

        $this->expectException(\RuntimeException::class);
        $updateUser->execute();
    }
}
