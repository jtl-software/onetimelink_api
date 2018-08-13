<?php
/**
 * This File is part of JTL-Software
 *
 * User: rherrgesell
 * Date: 04.08.17
 */

namespace JTL\Onetimelink;

use JTL\Onetimelink\Storage\MetaData;
use PHPUnit\Framework\TestCase;

/**
 * @covers \JTL\Onetimelink\Payload
 *
 * @uses   \JTL\Onetimelink\User
 * @uses   \JTL\Onetimelink\Storage\MetaData
 * @uses   \JTL\Onetimelink\PasswordHash
 */
class PayloadTest extends TestCase
{

    public function testCanGetData()
    {
        /** @var MetaData $metaDataMock */
        $metaDataMock = $this->createMock(MetaData::class);
        $payload = new Payload('testdata', $metaDataMock);

        $this->assertEquals('testdata', $payload->getData());
    }

    public function testCanGetMetaData()
    {
        /** @var MetaData $metaDataMock */
        $metaDataMock = $this->createMock(MetaData::class);
        $payload = new Payload('testdata', $metaDataMock);

        $this->assertInstanceOf(MetaData::class, $payload->getMetaData());
    }

}
