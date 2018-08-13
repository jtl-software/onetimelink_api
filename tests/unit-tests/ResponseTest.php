<?php
/**
 * This File is part of JTL-Software
 *
 * User: rherrgesell
 * Date: 04.08.17
 */

namespace JTL\Onetimelink;

use JTL\Onetimelink\View\ViewInterface;
use PHPUnit\Framework\TestCase;

/**
 * @covers \JTL\Onetimelink\Response
 * @uses   \JTL\OnetimeLink\Header
 * @uses   \JTL\Onetimelink\View\PlainView
 */
class ResponseTest extends TestCase
{

    public function testCanCreateSuccessfulResponse()
    {
        /** @var ViewInterface $viewMock */
        $viewMock = $this->createMock(ViewInterface::class);
        $this->assertInstanceOf(Response::class, Response::createSuccessful($viewMock));
    }

    public function testCanCreateSuccessfulCreatedResponse()
    {
        /** @var ViewInterface $viewMock */
        $viewMock = $this->createMock(ViewInterface::class);
        $this->assertInstanceOf(Response::class, Response::createSuccessfulCreated($viewMock));
    }

    public function testCanCreateNotFoundResponse()
    {
        $this->assertInstanceOf(Response::class, Response::createNotFound());
    }

    public function testAddHeaderInformation()
    {
        /** @var ViewInterface $viewMock */
        $viewMock = $this->createMock(ViewInterface::class);

        $headerMock = $this->createMock(Header::class);
        $headerMock->expects($this->once())
            ->method('set')
            ->with('foo', 'bar');

        $response = Response::createSuccessfulCreated($viewMock, $headerMock);
        $this->assertInstanceOf(Response::class, $response->addHeader('foo', 'bar'));
    }

    public function testResponseCanBeSend()
    {
        /** @var ViewInterface $viewMock */
        $viewMock = $this->createMock(ViewInterface::class);

        $headerMock = $this->createMock(Header::class);
        $headerMock->expects($this->once())
            ->method('set');

        $response = Response::createSuccessfulCreated($viewMock, $headerMock);
        $response->sendResponse();
    }

    public function testResponseIsSuccessfulCreated()
    {
        /** @var ViewInterface $viewMock */
        $viewMock = $this->createMock(ViewInterface::class);
        $headerMock = $this->createMock(Header::class);

        $response = new Response($viewMock, 201, $headerMock);
        $this->assertTrue($response->isSuccessfulCreated());

        $response = new Response($viewMock, 500, $headerMock);
        $this->assertFalse($response->isSuccessfulCreated());
    }

    public function testResponseIsSuccessful()
    {
        /** @var ViewInterface $viewMock */
        $viewMock = $this->createMock(ViewInterface::class);
        $headerMock = $this->createMock(Header::class);

        $response = new Response($viewMock, 200, $headerMock);
        $this->assertTrue($response->isSuccessful());

        $response = new Response($viewMock, 500, $headerMock);
        $this->assertFalse($response->isSuccessful());
    }
}
