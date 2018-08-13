<?php
/**
 * This File is part of JTL-Software
 *
 * User: mbrandt
 * Date: 11/05/18
 */

namespace JTL\Onetimelink\Controller\Command;

use JTL\Onetimelink\Exception\MissingParameterException;
use JTL\Onetimelink\Factory;
use JTL\Onetimelink\Request;
use JTL\Onetimelink\Storage\DatabaseStorage;
use JTL\Onetimelink\User;
use JTL\Onetimelink\View\JsonView;
use PHPUnit\Framework\TestCase;

/**
 * Class PrepareLinkTest
 * @package JTL\Onetimelink\Controller\Command
 *
 * @covers \JTL\Onetimelink\Controller\Command\PrepareLink
 *
 * @uses \JTL\Onetimelink\Header
 * @uses \JTL\Onetimelink\LinkHash
 * @uses \JTL\Onetimelink\Payload
 * @uses \JTL\Onetimelink\Response
 * @uses \JTL\Onetimelink\View\JsonView
 * @uses \JTL\Onetimelink\Storage\MetaData
 */
class PrepareLinkTest extends TestCase
{

    public function testCanPrepareLink()
    {
        $contentType = 'text/plain';
        $resumableFilename = 'testfile.txt';
        $resumableIdentifier = uniqid('resumable', true);
        $currentChunk = 1;
        $totalChunks = random_int(1, 10);

        for (; $currentChunk <= $totalChunks; ++$currentChunk) {
            $storageMock = $this->createMock(DatabaseStorage::class);
            $storageMock->expects($this->once())->method('isMergeDone')->willReturn(false);
            $storageMock->expects($this->once())
                        ->method('writeChunk')
                        ->willReturn(true);

            if ($currentChunk === $totalChunks) {
                $storageMock->expects($this->once())->method('mergeChunks');
            }

            $userMock = $this->createMock(User::class);

            $requestMock = $this->createMock(Request::class);
            $requestMock->expects($this->at(0))
                        ->method('readGet')
                        ->with('type')
                        ->willReturn($contentType);

            $requestMock->expects($this->at(1))
                        ->method('readPost')
                        ->with('resumableFilename')
                        ->willReturn($resumableFilename);

            $requestMock->expects($this->at(2))
                        ->method('readPost')
                        ->with(PrepareLink::PLAIN_DATA_FIELD)
                        ->willReturn('random data');

            $requestMock->expects($this->at(3))
                        ->method('readPost')
                        ->with('resumableChunkNumber')
                        ->willReturn($currentChunk);

            $requestMock->expects($this->at(4))
                        ->method('readPost')
                        ->with('resumableTotalChunks')
                        ->willReturn($totalChunks);

            $requestMock->expects($this->at(5))
                        ->method('readPost')
                        ->with('resumableIdentifier')
                        ->willReturn($resumableIdentifier);


            $factoryMock = $this->createMock(Factory::class);
            $factoryMock->expects($this->once())
                        ->method('createJsonView')
                        ->willReturn(new JsonView());

            $prepareLink = new PrepareLink(
                $storageMock,
                $userMock,
                $requestMock,
                $factoryMock
            );

            $prepareLink->execute();
        }
    }

    public function testDoesNotProcessAlreadyMergedChunk()
    {
        $contentType = 'text/plain';
        $resumableFilename = 'testfile.txt';
        $resumableIdentifier = uniqid('resumable', true);

        $storageMock = $this->createMock(DatabaseStorage::class);
        $storageMock->expects($this->once())->method('isMergeDone')->willReturn(true);
        $storageMock->expects($this->never())
                    ->method('writeChunk');

        $storageMock->expects($this->never())->method('mergeChunks');

        $userMock = $this->createMock(User::class);

        $requestMock = $this->createMock(Request::class);
        $requestMock->expects($this->at(0))
                    ->method('readGet')
                    ->with('type')
                    ->willReturn($contentType);

        $requestMock->expects($this->at(1))
                    ->method('readPost')
                    ->with('resumableFilename')
                    ->willReturn($resumableFilename);

        $requestMock->expects($this->at(2))
                    ->method('readPost')
                    ->with(PrepareLink::PLAIN_DATA_FIELD)
                    ->willReturn('random data');

        $requestMock->expects($this->at(3))
                    ->method('readPost')
                    ->with('resumableChunkNumber')
                    ->willReturn(1);

        $requestMock->expects($this->at(4))
                    ->method('readPost')
                    ->with('resumableTotalChunks')
                    ->willReturn(1);

        $requestMock->expects($this->at(5))
                    ->method('readPost')
                    ->with('resumableIdentifier')
                    ->willReturn($resumableIdentifier);


        $factoryMock = $this->createMock(Factory::class);
        $factoryMock->expects($this->once())
                    ->method('createJsonView')
                    ->willReturn(new JsonView());

        $prepareLink = new PrepareLink(
            $storageMock,
            $userMock,
            $requestMock,
            $factoryMock
        );

        $prepareLink->execute();
    }

    public function testThrowExceptionWhenNoDataExists()
    {
        $contentType = 'text/plain';
        $resumableFilename = 'testfile.txt';

        $storageMock = $this->createMock(DatabaseStorage::class);

        $userMock = $this->createMock(User::class);

        $requestMock = $this->createMock(Request::class);
        $requestMock->expects($this->at(0))
                    ->method('readGet')
                    ->with('type')
                    ->willReturn($contentType);

        $requestMock->expects($this->at(1))
                    ->method('readPost')
                    ->with('resumableFilename')
                    ->willReturn($resumableFilename);

        $requestMock->expects($this->at(2))
                    ->method('readPost')
                    ->with(PrepareLink::PLAIN_DATA_FIELD)
                    ->willReturn(null);

        $factoryMock = $this->createMock(Factory::class);
        $factoryMock->expects($this->once())
                    ->method('createJsonView')
                    ->willReturn(new JsonView());

        $prepareLink = new PrepareLink(
            $storageMock,
            $userMock,
            $requestMock,
            $factoryMock
        );

        $this->expectException(MissingParameterException::class);
        $prepareLink->execute();
    }

    public function testThrowExceptionWhenWritingChunkFails()
    {
        $contentType = 'text/plain';
        $resumableFilename = 'testfile.txt';
        $resumableIdentifier = uniqid('resumable', true);
        $currentChunk = 1;
        $totalChunks = 1;

        $storageMock = $this->createMock(DatabaseStorage::class);
        $storageMock->expects($this->once())->method('isMergeDone')->willReturn(false);
        $storageMock->expects($this->once())
                    ->method('writeChunk')
                    ->willReturn(false);

        $storageMock->expects($this->never())->method('mergeChunks');

        $userMock = $this->createMock(User::class);

        $requestMock = $this->createMock(Request::class);
        $requestMock->expects($this->at(0))
                    ->method('readGet')
                    ->with('type')
                    ->willReturn($contentType);

        $requestMock->expects($this->at(1))
                    ->method('readPost')
                    ->with('resumableFilename')
                    ->willReturn($resumableFilename);

        $requestMock->expects($this->at(2))
                    ->method('readPost')
                    ->with(PrepareLink::PLAIN_DATA_FIELD)
                    ->willReturn('random data');

        $requestMock->expects($this->at(3))
                    ->method('readPost')
                    ->with('resumableChunkNumber')
                    ->willReturn($currentChunk);

        $requestMock->expects($this->at(4))
                    ->method('readPost')
                    ->with('resumableTotalChunks')
                    ->willReturn($totalChunks);

        $requestMock->expects($this->at(5))
                    ->method('readPost')
                    ->with('resumableIdentifier')
                    ->willReturn($resumableIdentifier);


        $factoryMock = $this->createMock(Factory::class);
        $factoryMock->expects($this->once())
                    ->method('createJsonView')
                    ->willReturn(new JsonView());

        $prepareLink = new PrepareLink(
            $storageMock,
            $userMock,
            $requestMock,
            $factoryMock
        );

        $this->expectException(\RuntimeException::class);
        $prepareLink->execute();
    }

}
