<?php
/**
 * This File is part of JTL-Software
 *
 * User: mbrandt
 * Date: 27/08/18
 */

namespace JTL\Onetimelink\Controller\Command;

use JTL\Onetimelink\Factory;
use JTL\Onetimelink\Storage\DatabaseStorage;
use JTL\Onetimelink\Storage\UserMetaDatabaseStorage;
use PHPUnit\Framework\TestCase;

/**
 * Class DeleteLinkTest
 * @package JTL\Onetimelink\Controller\Command
 *
 * @covers \JTL\Onetimelink\Controller\Command\DeleteLink
 *
 * @uses   \JTL\Onetimelink\Header
 * @uses   \JTL\Onetimelink\PasswordHash
 * @uses   \JTL\Onetimelink\Response
 * @uses   \JTL\Onetimelink\User
 * @uses   \JTL\Onetimelink\View\PlainView
 */
class DeleteLinkTest extends TestCase
{
    public function testCanCreateDeleteAuth()
    {
        $linkHash = uniqid('hash', true);
        $auth = uniqid('auth', true);
        $owner = 'otl-tester@jtl-software.com';

        $storageMock = $this->createMock(DatabaseStorage::class);
        $storageMock->expects($this->once())->method('getDeleteAuthOwnerEmail')
                    ->with($linkHash, $auth)
                    ->willReturn($owner);
        $storageMock->expects($this->once())->method('deleteDeleteAuth')->willReturn(true);
        $storageMock->expects($this->once())->method('deleteLink')->with($linkHash);

        $metaStorageMock = $this->createMock(UserMetaDatabaseStorage::class);
        $metaStorageMock->expects($this->once())->method('setToDeleted')->with($linkHash);

        $factoryMock = $this->createMock(Factory::class);
        $factoryMock->expects($this->once())->method('createUserMetaStorage')
                    ->with($owner)
                    ->willReturn($metaStorageMock);

        $deleteLink = new DeleteLink($storageMock, $factoryMock, $linkHash, $auth);
        $response = $deleteLink->execute();
        $this->assertTrue($response->isSuccessful());
    }

    public function testFailIfOwnerEmailDoesntExist()
    {
        $linkHash = uniqid('hash', true);
        $auth = uniqid('auth', true);

        $storageMock = $this->createMock(DatabaseStorage::class);
        $storageMock->expects($this->once())->method('getDeleteAuthOwnerEmail')
                    ->with($linkHash, $auth)
                    ->willReturn('');

        $factoryMock = $this->createMock(Factory::class);

        $deleteLink = new DeleteLink($storageMock, $factoryMock, $linkHash, $auth);
        $response = $deleteLink->execute();
        $this->assertFalse($response->isSuccessful());
    }

    public function testFailIfSavingFails()
    {
        $linkHash = uniqid('hash', true);
        $auth = uniqid('auth', true);
        $owner = 'otl-tester@jtl-software.com';

        $storageMock = $this->createMock(DatabaseStorage::class);
        $storageMock->expects($this->once())->method('getDeleteAuthOwnerEmail')
                    ->with($linkHash, $auth)
                    ->willReturn($owner);
        $storageMock->expects($this->once())->method('deleteDeleteAuth')->willReturn(true);

        $factoryMock = $this->createMock(Factory::class);
        $factoryMock->expects($this->once())->method('createUserMetaStorage')
                    ->willThrowException(new \Exception('Exception'));

        $deleteLink = new DeleteLink($storageMock, $factoryMock, $linkHash, $auth);
        $response = $deleteLink->execute();
        $this->assertFalse($response->isSuccessful());
    }

    public function testFailIfNoDeleteAuthExists()
    {
        $linkHash = uniqid('hash', true);
        $auth = uniqid('auth', true);
        $owner = 'otl-tester@jtl-software.com';

        $storageMock = $this->createMock(DatabaseStorage::class);
        $storageMock->expects($this->once())->method('getDeleteAuthOwnerEmail')
                    ->with($linkHash, $auth)
                    ->willReturn($owner);
        $storageMock->expects($this->once())->method('deleteDeleteAuth')->willReturn(false);

        $factoryMock = $this->createMock(Factory::class);

        $deleteLink = new DeleteLink($storageMock, $factoryMock, $linkHash, $auth);
        $response = $deleteLink->execute();
        $this->assertFalse($response->isSuccessful());
    }
}
