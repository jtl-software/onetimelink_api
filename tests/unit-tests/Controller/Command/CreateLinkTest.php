<?php
/**
 * This File is part of JTL-Software
 *
 * User: rherrgesell
 * Date: 04.08.17
 */

namespace JTL\Onetimelink\Controller\Command;

use JTL\Onetimelink\Factory;
use JTL\Onetimelink\Request;
use JTL\Onetimelink\Response;
use JTL\Onetimelink\Storage\DatabaseStorage;
use JTL\Onetimelink\User;
use JTL\Onetimelink\View\JsonView;
use JTL\Onetimelink\View\ViewInterface;
use PHPUnit\Framework\TestCase;
use RedBeanPHP\R;

/**
 * @covers \JTL\Onetimelink\Controller\Command\CreateLink
 *
 * @uses   \JTL\Onetimelink\Header
 * @uses   \JTL\Onetimelink\Payload
 * @uses   \JTL\Onetimelink\Response
 * @uses   \JTL\Onetimelink\Storage\MetaData
 * @uses   \JTL\Onetimelink\User
 * @uses   \JTL\Onetimelink\LinkHash
 * @uses   \JTL\Onetimelink\OneTimeLink
 * @uses   \JTL\Onetimelink\PasswordHash
 * @uses   \JTL\Onetimelink\DAO\AttachmentDAO
 */
class CreateLinkTest extends TestCase
{

    protected function setUp()
    {
        $dsn = 'sqlite:' . __DIR__ . '/../../../../var/db/test.db';
        if (!R::hasDatabase('test')) {
            R::addDatabase('test', $dsn);
        }

        R::selectDatabase('test');

    }

    protected function tearDown()
    {
        R::nuke();
        R::close();
    }

    public function testCanCreateNewPayloadFromTextUpload()
    {
        /** @var DatabaseStorage|\PHPUnit_Framework_MockObject_MockObject $storageMock */
        $storageMock = $this->createMock(DatabaseStorage::class);
        $storageMock->expects($this->once())
            ->method('write')
            ->willReturn(true);

        $storageMock->expects($this->once())
            ->method('writeLink')
            ->willReturn(true);

        /** @var Request|\PHPUnit_Framework_MockObject_MockObject $requestMock */
        $requestMock = $this->createMock(Request::class);
        $requestMock->expects($this->once())
            ->method('readInputAsJson')
            ->willReturn([
                'text' => 'I am a string',
                'amount' => 1
            ]);

        $user = User::createUserFromString('foo');

        /** @var ViewInterface|\PHPUnit_Framework_MockObject_MockObject $viewMock */
        $viewMock = $this->createMock(JsonView::class);

        /** @var Factory|\PHPUnit_Framework_MockObject_MockObject $factoryMock */
        $factoryMock = $this->createMock(Factory::class);
        $factoryMock->expects($this->once())
            ->method('createJsonView')
            ->willReturn($viewMock);

        $createLink = new CreateLink($storageMock, $user, $requestMock, $factoryMock);
        $response = $createLink->execute();

        $this->assertInstanceOf(Response::class, $response);
    }

    public function testCanCreateNewPayloadFromFileUpload()
    {
        /** @var DatabaseStorage|\PHPUnit_Framework_MockObject_MockObject $storageMock */
        $storageMock = $this->createMock(DatabaseStorage::class);
        $storageMock->expects($this->once())
            ->method('writeLink')
            ->willReturn(true);

        $storageMock->expects($this->once())
            ->method('writeLink')
            ->willReturn(true);

        /** @var Request|\PHPUnit_Framework_MockObject_MockObject $requestMock */
        $requestMock = $this->createMock(Request::class);
        $requestMock->expects($this->once())
            ->method('readInputAsJson')
            ->willReturn([
                'file0' => 'uploaded_file.txt',
                'amount' => 1
            ]);

        $user = User::createUserFromString('foo');

        /** @var ViewInterface|\PHPUnit_Framework_MockObject_MockObject $viewMock */
        $viewMock = $this->createMock(JsonView::class);

        /** @var Factory|\PHPUnit_Framework_MockObject_MockObject $factoryMock */
        $factoryMock = $this->createMock(Factory::class);
        $factoryMock->expects($this->once())
            ->method('createJsonView')
            ->willReturn($viewMock);

        $createLink = new CreateLink($storageMock, $user, $requestMock, $factoryMock);
        $response = $createLink->execute();

        $this->assertInstanceOf(Response::class, $response);
    }

    public function testCanCreateNewPayloadFromTextAndFileUpload()
    {
        /** @var DatabaseStorage|\PHPUnit_Framework_MockObject_MockObject $storageMock */
        $storageMock = $this->createMock(DatabaseStorage::class);
        $storageMock->expects($this->once())
            ->method('writeLink')
            ->willReturn(true);

        $storageMock->expects($this->once())
            ->method('write')
            ->willReturn(true);

        $storageMock->expects($this->once())
            ->method('writeLink')
            ->willReturn(true);

        /** @var Request|\PHPUnit_Framework_MockObject_MockObject $requestMock */
        $requestMock = $this->createMock(Request::class);
        $requestMock->expects($this->once())
            ->method('readInputAsJson')
            ->willReturn([
                'file0' => 'uploaded_file.txt',
                'text'  => 'uploaded text example',
                'amount' => 1
            ]);

        $user = User::createUserFromString('foo');

        /** @var ViewInterface|\PHPUnit_Framework_MockObject_MockObject $viewMock */
        $viewMock = $this->createMock(JsonView::class);

        /** @var Factory|\PHPUnit_Framework_MockObject_MockObject $factoryMock */
        $factoryMock = $this->createMock(Factory::class);
        $factoryMock->expects($this->once())
            ->method('createJsonView')
            ->willReturn($viewMock);

        $createLink = new CreateLink($storageMock, $user, $requestMock, $factoryMock);
        $response = $createLink->execute();

        $this->assertInstanceOf(Response::class, $response);
    }

    public function testFailWhenNoDataIsSend()
    {
        /** @var DatabaseStorage|\PHPUnit_Framework_MockObject_MockObject $storageMock */
        $storageMock = $this->createMock(DatabaseStorage::class);

        /** @var Request|\PHPUnit_Framework_MockObject_MockObject $requestMock */
        $requestMock = $this->createMock(Request::class);
        $requestMock->expects($this->once())
            ->method('readInputAsJson')
            ->willReturn(null);

        $user = User::createUserFromString('foo');

        /** @var ViewInterface|\PHPUnit_Framework_MockObject_MockObject $viewMock */
        $viewMock = $this->createMock(JsonView::class);

        /** @var Factory|\PHPUnit_Framework_MockObject_MockObject $factoryMock */
        $factoryMock = $this->createMock(Factory::class);
        $factoryMock->expects($this->once())
            ->method('createJsonView')
            ->willReturn($viewMock);

        $createLink = new CreateLink($storageMock, $user, $requestMock, $factoryMock);

        $this->expectException(\RuntimeException::class);

        $createLink->execute();
    }

    public function testFailWhenStorageWriteFail()
    {
        /** @var DatabaseStorage|\PHPUnit_Framework_MockObject_MockObject $storageMock */
        $storageMock = $this->createMock(DatabaseStorage::class);
        $storageMock->expects($this->once())
            ->method('write')
            ->willReturn(false);

        /** @var Request|\PHPUnit_Framework_MockObject_MockObject $requestMock */
        $requestMock = $this->createMock(Request::class);
        $requestMock->expects($this->once())
            ->method('readInputAsJson')
            ->willReturn(['text' => 'I am a string']);

        $requestMock->expects($this->once())
            ->method('readInputAsJson')
            ->willReturn(['amount' => 1]);

        $user = User::createUserFromString('foo');

        /** @var ViewInterface|\PHPUnit_Framework_MockObject_MockObject $viewMock */
        $viewMock = $this->createMock(JsonView::class);

        /** @var Factory|\PHPUnit_Framework_MockObject_MockObject $factoryMock */
        $factoryMock = $this->createMock(Factory::class);
        $factoryMock->expects($this->once())
            ->method('createJsonView')
            ->willReturn($viewMock);

        $createLink = new CreateLink($storageMock, $user, $requestMock, $factoryMock);

        $this->expectException(\RuntimeException::class);

        $createLink->execute();
    }
}
