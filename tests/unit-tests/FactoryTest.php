<?php
/**
 * This File is part of JTL-Software
 *
 * User: rherrgesell
 * Date: 04.08.17
 */

namespace JTL\Onetimelink;

use JTL\Onetimelink\Controller\Command;
use JTL\Onetimelink\Controller\Query;
use Monolog\Processor\UidProcessor;
use PHPUnit\Framework\TestCase;

/**
 * @covers \JTL\Onetimelink\Factory
 *
 * @uses   \JTL\Onetimelink\Controller\Query
 * @uses   \JTL\Onetimelink\Controller\Command
 */
class FactoryTest extends TestCase
{

    public function testCanCreateQueryController()
    {
        $factory = new Factory($this->buildConfigMock(true), $this->createMock(UidProcessor::class));
        $this->assertInstanceOf(Query::class, $factory->createController());
    }

    public function testCanCreateCommandController()
    {
        $factory = new Factory($this->buildConfigMock(false), $this->createMock(UidProcessor::class));
        $this->assertInstanceOf(Command::class, $factory->createController());
    }

    /**
     * @param bool $isHTTPGetCall
     * @return Config|\PHPUnit_Framework_MockObject_MockObject
     */
    private function buildConfigMock(bool $isHTTPGetCall)
    {
        /** @var Config|\PHPUnit_Framework_MockObject_MockObject $configMock */
        $requestMock = $this->createMock(Request::class);
        $requestMock->expects($this->once())
            ->method('isGet')
            ->willReturn($isHTTPGetCall);

        /** @var Config|\PHPUnit_Framework_MockObject_MockObject $configMock */
        $configMock = $this->createMock(Config::class);
        $configMock->expects($this->once())
            ->method('createRequest')
            ->willReturn($requestMock);

        return $configMock;
    }
}
